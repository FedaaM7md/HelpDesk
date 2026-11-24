<?php

namespace App\Services;

use App\Enums\MessageAuthor;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketReplyMail;
use App\Mail\TicketSolvedMail;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class TicketService
{
    public function __construct(
        protected SlaService $sla,
        protected AttachmentService $attachments,
    ) {}

    public function create(array $data, array $files = [], ?User $actor = null, bool $notify = true): Ticket
    {
        $ticket = DB::transaction(function () use ($data, $files, $actor) {
            $ticket = new Ticket([
                'reference' => $this->nextReference(),
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => TicketStatus::Open,
                'priority' => $data['priority'] ?? TicketPriority::Normal,
                'channel' => $data['channel'] ?? TicketChannel::Portal,
                'category_id' => $data['category_id'] ?? null,
                'requester_name' => $data['requester_name'],
                'requester_email' => mb_strtolower($data['requester_email']),
                'requester_locale' => $data['requester_locale'] ?? app()->getLocale(),
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $actor?->id,
                'last_reply_at' => now(),
            ]);

            $ticket->created_at = now();
            $ticket->updated_at = now();
            $ticket->save();

            $this->sla->apply($ticket);
            $ticket->save();

            $message = $this->createMessage($ticket, [
                'author_type' => MessageAuthor::Requester,
                'author_name' => $ticket->requester_name,
                'author_email' => $ticket->requester_email,
                'body' => $ticket->description,
                'source' => $ticket->channel->value,
                'message_id' => $data['message_id'] ?? null,
                'in_reply_to' => $data['in_reply_to'] ?? null,
            ], $actor);

            $this->attachments->storeUploaded($ticket, $message, $files, $ticket->channel->value, $actor);

            return $ticket;
        });

        if ($notify) {
            $this->sendCreatedMail($ticket);
        }

        return $ticket->fresh();
    }

    public function addMessage(Ticket $ticket, array $data, array $files = [], bool $notify = true): TicketMessage
    {
        $message = DB::transaction(function () use ($ticket, $data, $files) {
            $authorType = $data['author_type'];

            $message = $this->createMessage($ticket, $data, $data['user'] ?? null);
            $this->attachments->storeUploaded(
                $ticket,
                $message,
                $files,
                $data['source'] ?? 'web',
                $data['user'] ?? null,
            );

            if ($authorType === MessageAuthor::Requester) {
                $ticket->last_reply_at = now();

                if (in_array($ticket->status, [TicketStatus::Pending, TicketStatus::Solved, TicketStatus::Closed], true)) {
                    $ticket->status = TicketStatus::Open;
                    $ticket->resolved_at = null;
                    $ticket->closed_at = null;
                }
            }

            if ($authorType === MessageAuthor::Agent && ! ($data['is_internal'] ?? false)) {
                $this->sla->registerFirstResponse($ticket);

                if ($ticket->status->isResolved()) {
                    $ticket->status = TicketStatus::Open;
                    $ticket->resolved_at = null;
                    $ticket->closed_at = null;
                }
            }

            $ticket->save();

            return $message;
        });

        if ($notify && $message->author_type === MessageAuthor::Agent && ! $message->is_internal) {
            $this->sendReplyMail($ticket->fresh(), $message);
        }

        return $message->load('attachments');
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, ?User $actor = null): Ticket
    {
        $sendCsat = false;

        DB::transaction(function () use ($ticket, $status, $actor, &$sendCsat) {
            $previous = $ticket->status;

            if ($status === TicketStatus::Solved) {
                $this->sla->registerResolution($ticket);
                $sendCsat = $previous !== TicketStatus::Solved && ! $ticket->csat()->exists();
            } elseif ($status === TicketStatus::Closed) {
                if (! $ticket->resolved_at) {
                    $this->sla->registerResolution($ticket);
                }
                $ticket->closed_at = now();
            } else {
                $ticket->resolved_at = null;
                $ticket->closed_at = null;
            }

            $ticket->status = $status;
            $ticket->save();

            if ($previous !== $status) {
                $this->systemNote($ticket, __('app.activity.status_changed', [
                    'from' => $previous->label(),
                    'to' => $status->label(),
                    'actor' => $actor?->name ?? __('app.system'),
                ]), $actor);
            }
        });

        if ($sendCsat) {
            $this->sendSolvedMail($ticket->fresh());
        }

        return $ticket->fresh();
    }

    public function changePriority(Ticket $ticket, TicketPriority $priority, ?User $actor = null): Ticket
    {
        if ($ticket->priority === $priority) {
            return $ticket;
        }

        $previous = $ticket->priority;
        $ticket->priority = $priority;
        $this->sla->apply($ticket, $ticket->created_at);
        $ticket->save();

        $this->systemNote($ticket, __('app.activity.priority_changed', [
            'from' => $previous->label(),
            'to' => $priority->label(),
            'actor' => $actor?->name ?? __('app.system'),
        ]), $actor);

        return $ticket->fresh();
    }

    public function assign(Ticket $ticket, ?User $assignee, ?User $actor = null): Ticket
    {
        if ($ticket->assigned_to === $assignee?->id) {
            return $ticket;
        }

        $ticket->assigned_to = $assignee?->id;
        $ticket->save();

        $this->systemNote($ticket, __('app.activity.assigned', [
            'name' => $assignee?->name ?? __('app.unassigned'),
            'actor' => $actor?->name ?? __('app.system'),
        ]), $actor);

        return $ticket->fresh();
    }

    public function recordCsat(Ticket $ticket, int $score, ?string $comment = null): void
    {
        $ticket->csat()->create([
            'score' => $score,
            'comment' => $comment,
            'submitted_at' => now(),
        ]);
    }

    public function systemNote(Ticket $ticket, string $body, ?User $actor = null): TicketMessage
    {
        return TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'author_type' => MessageAuthor::System,
            'author_name' => __('app.system'),
            'author_email' => null,
            'body' => $body,
            'is_internal' => true,
            'source' => 'system',
        ]);
    }

    public function trackingUrl(Ticket $ticket): string
    {
        return URL::temporarySignedRoute(
            'portal.ticket.show',
            now()->addDays((int) config('helpdesk.tracking_link_days', 90)),
            ['ticket' => $ticket->reference],
        );
    }

    public function csatUrl(Ticket $ticket): string
    {
        return URL::temporarySignedRoute(
            'portal.csat.show',
            now()->addDays((int) config('helpdesk.csat_link_days', 30)),
            ['ticket' => $ticket->reference],
        );
    }

    protected function createMessage(Ticket $ticket, array $data, ?User $actor): TicketMessage
    {
        return TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'author_type' => $data['author_type'],
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'body' => $data['body'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
            'message_id' => $data['message_id'] ?? null,
            'in_reply_to' => $data['in_reply_to'] ?? null,
            'source' => $data['source'] ?? 'web',
        ]);
    }

    protected function nextReference(): string
    {
        $prefix = config('helpdesk.reference_prefix', 'TKT').'-'.now()->format('Y').'-';
        $last = Ticket::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $last ? ((int) mb_substr($last, mb_strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected function sendCreatedMail(Ticket $ticket): void
    {
        Mail::to($ticket->requester_email)
            ->locale($ticket->requester_locale ?: 'ar')
            ->send(new TicketCreatedMail($ticket, $this->trackingUrl($ticket)));
    }

    protected function sendReplyMail(Ticket $ticket, TicketMessage $message): void
    {
        Mail::to($ticket->requester_email)
            ->locale($ticket->requester_locale ?: 'ar')
            ->send(new TicketReplyMail($ticket, $message, $this->trackingUrl($ticket)));
    }

    protected function sendSolvedMail(Ticket $ticket): void
    {
        Mail::to($ticket->requester_email)
            ->locale($ticket->requester_locale ?: 'ar')
            ->send(new TicketSolvedMail($ticket, $this->csatUrl($ticket)));
    }
}
