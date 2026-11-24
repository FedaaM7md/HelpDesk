<?php

namespace App\Services;

use App\Enums\MessageAuthor;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Models\InboxMessage;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Support\EmlParser;
use Illuminate\Support\Facades\File;
use Throwable;

class EmailInboxImporter
{
    /** @var array<string, true> */
    protected array $seenMessageIds = [];

    public function __construct(
        protected EmlParser $parser,
        protected TicketService $tickets,
        protected AttachmentService $attachments,
    ) {}

    /**
     * @return array{new:int, replies:int, duplicates:int, rejected:int, failed:int, attachments:int, skipped:int, rows:array<int, array<string,string>>}
     */
    public function importDirectory(string $directory, bool $dryRun = false): array
    {
        $summary = $this->emptySummary();

        if (! File::isDirectory($directory)) {
            return $summary;
        }

        $files = collect(File::files($directory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'eml')
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        foreach ($files as $file) {
            $this->importFile($file->getPathname(), $dryRun, $summary);
        }

        return $summary;
    }

    /**
     * @return array{new:int, replies:int, duplicates:int, rejected:int, failed:int, attachments:int, skipped:int, rows:array<int, array<string,string>>}
     */
    public function importFile(string $path, bool $dryRun = false, ?array &$summary = null): array
    {
        $summary ??= $this->emptySummary();
        $fileName = basename($path);

        try {
            $raw = File::get($path);
            $email = $this->parser->parse($raw);
        } catch (Throwable $exception) {
            $summary['failed']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => 'failed', 'detail' => $exception->getMessage()];

            if (! $dryRun) {
                $this->record(
                    messageId: 'failed-'.sha1($fileName.$exception->getMessage()),
                    fileName: $fileName,
                    from: null,
                    subject: null,
                    status: 'failed',
                    error: $exception->getMessage(),
                );
            }

            return $summary;
        }

        $messageId = $email['message_id'] ?: 'generated-'.sha1($raw).'@helpdesk.local';

        if (isset($this->seenMessageIds[$messageId]) || InboxMessage::query()->where('message_id', $messageId)->exists()) {
            $summary['duplicates']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => 'duplicate', 'detail' => $messageId];

            return $summary;
        }

        $this->seenMessageIds[$messageId] = true;

        if (! $email['from_email']) {
            $summary['failed']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => 'failed', 'detail' => __('app.email.no_sender')];

            if (! $dryRun) {
                $this->record($messageId, $fileName, null, $email['subject'], 'failed', __('app.email.no_sender'));
            }

            return $summary;
        }

        $ticket = $this->resolveThread($email);

        if ($ticket && strcasecmp($ticket->requester_email, $email['from_email']) !== 0) {
            $summary['rejected']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => 'rejected', 'detail' => __('app.email.sender_mismatch')];

            if (! $dryRun) {
                $this->record(
                    $messageId,
                    $fileName,
                    $email['from_email'],
                    $email['subject'],
                    'rejected',
                    __('app.email.sender_mismatch'),
                    $ticket->id,
                );
            }

            return $summary;
        }

        if ($dryRun) {
            $ticket ? $summary['replies']++ : $summary['new']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => $ticket ? 'would-reply' : 'would-create', 'detail' => $ticket?->reference ?? $this->cleanSubject($email['subject'])];

            return $summary;
        }

        if ($ticket) {
            $message = $this->tickets->addMessage($ticket, [
                'author_type' => MessageAuthor::Requester,
                'author_name' => $email['from_name'] ?: $email['from_email'],
                'author_email' => $email['from_email'],
                'body' => $email['text'] !== '' ? $email['text'] : __('app.email.empty_body'),
                'message_id' => $email['message_id'],
                'in_reply_to' => $email['in_reply_to'],
                'source' => 'email',
            ], [], false);

            $skipped = $this->storeEmailAttachments($ticket, $message, $email['attachments'], $summary);
            $summary['replies']++;
            $summary['rows'][] = ['file' => $fileName, 'status' => 'reply', 'detail' => $ticket->reference];

            $this->record(
                $messageId,
                $fileName,
                $email['from_email'],
                $email['subject'],
                'imported',
                $skipped ? __('app.email.skipped_attachments', ['files' => implode(', ', $skipped)]) : null,
                $ticket->id,
            );

            return $summary;
        }

        $category = TicketCategory::query()->active()->first();
        $subject = $this->cleanSubject($email['subject']);

        $ticket = $this->tickets->create([
            'subject' => $subject !== '' ? $subject : __('app.email.no_subject'),
            'description' => $email['text'] !== '' ? $email['text'] : __('app.email.empty_body'),
            'requester_name' => $email['from_name'] ?: $email['from_email'],
            'requester_email' => $email['from_email'],
            'requester_locale' => $this->detectLocale($email['text'].' '.$email['subject']),
            'category_id' => $category?->id,
            'priority' => TicketPriority::Normal,
            'channel' => TicketChannel::Email,
            'message_id' => $email['message_id'],
            'in_reply_to' => $email['in_reply_to'],
        ], [], null, true);

        $firstMessage = $ticket->messages()->first();
        $skipped = $this->storeEmailAttachments($ticket, $firstMessage, $email['attachments'], $summary);

        $summary['new']++;
        $summary['rows'][] = ['file' => $fileName, 'status' => 'created', 'detail' => $ticket->reference];

        $this->record(
            $messageId,
            $fileName,
            $email['from_email'],
            $email['subject'],
            'imported',
            $skipped ? __('app.email.skipped_attachments', ['files' => implode(', ', $skipped)]) : null,
            $ticket->id,
        );

        return $summary;
    }

    /**
     * @param  array<int, array{filename:string, mime_type:string, content:string}>  $attachments
     * @return array<int, string>
     */
    protected function storeEmailAttachments(Ticket $ticket, ?TicketMessage $message, array $attachments, array &$summary): array
    {
        $skipped = [];
        $maxFiles = max(1, (int) config('helpdesk.attachments.max_files', 5));

        foreach (array_slice($attachments, 0, $maxFiles) as $attachment) {
            $stored = $this->attachments->storeContents(
                $ticket,
                $message,
                $attachment['filename'],
                $attachment['mime_type'],
                $attachment['content'],
                'email',
            );

            if ($stored) {
                $summary['attachments']++;
            } else {
                $summary['skipped']++;
                $skipped[] = $attachment['filename'];
            }
        }

        if (count($attachments) > $maxFiles) {
            $summary['skipped'] += count($attachments) - $maxFiles;
        }

        return $skipped;
    }

    /**
     * @param  array{subject:string, in_reply_to:?string, references:array<int,string>}  $email
     */
    protected function resolveThread(array $email): ?Ticket
    {
        if (preg_match('/TKT-\d{4}-\d{4,}/i', $email['subject'], $matches)) {
            $ticket = Ticket::query()->where('reference', strtoupper($matches[0]))->first();

            if ($ticket) {
                return $ticket;
            }
        }

        $candidates = array_filter(array_merge([$email['in_reply_to']], $email['references']));

        foreach ($candidates as $messageId) {
            $message = TicketMessage::query()
                ->where('message_id', trim((string) $messageId, '<> '))
                ->first();

            if ($message) {
                return $message->ticket;
            }
        }

        return null;
    }

    protected function cleanSubject(string $subject): string
    {
        $subject = preg_replace('/^\s*((re|fw|fwd|رد|إعادة توجيه|اعاده توجيه)\s*:\s*)+/iu', '', $subject) ?? $subject;
        $subject = preg_replace('/\bTKT-\d{4}-\d{4,}\b/i', '', $subject) ?? $subject;

        return trim(preg_replace('/\s+/', ' ', $subject) ?? $subject);
    }

    protected function detectLocale(string $text): string
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) ? 'ar' : 'en';
    }

    protected function record(
        string $messageId,
        string $fileName,
        ?string $from,
        ?string $subject,
        string $status,
        ?string $error = null,
        ?int $ticketId = null,
    ): void {
        InboxMessage::updateOrCreate(
            ['message_id' => $messageId],
            [
                'file_name' => $fileName,
                'from_email' => $from,
                'subject' => $subject ? mb_substr($subject, 0, 250) : null,
                'ticket_id' => $ticketId,
                'status' => $status,
                'error' => $error,
                'processed_at' => now(),
            ],
        );
    }

    /**
     * @return array{new:int, replies:int, duplicates:int, rejected:int, failed:int, attachments:int, skipped:int, rows:array<int, array<string,string>>}
     */
    protected function emptySummary(): array
    {
        return [
            'new' => 0,
            'replies' => 0,
            'duplicates' => 0,
            'rejected' => 0,
            'failed' => 0,
            'attachments' => 0,
            'skipped' => 0,
            'rows' => [],
        ];
    }
}
