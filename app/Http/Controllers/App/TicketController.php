<?php

namespace App\Http\Controllers\App;

use App\Enums\MessageAuthor;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\CannedReply;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(protected TicketService $tickets) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        $filters = $request->only(['q', 'status', 'priority', 'category', 'tag', 'assignee', 'sla', 'sort']);

        $base = Ticket::query()->visibleTo($user);

        $statusCounts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tickets = (clone $base)
            ->with(['category', 'assignee', 'tags'])
            ->filter($filters)
            ->paginate(15)
            ->withQueryString();

        return view('app.tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'categories' => TicketCategory::query()->active()->get(),
            'tags' => Tag::query()->orderBy('name_en')->get(),
            'agents' => $user->can('assign-tickets') ? User::query()->active()->orderBy('name')->get() : collect([$user]),
            'unresolvedTotal' => collect(TicketStatus::unresolvedValues())->sum(fn ($status) => (int) ($statusCounts[$status] ?? 0)),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        return view('app.tickets.create', [
            'categories' => TicketCategory::query()->active()->get(),
            'agents' => $request->user()->can('assign-tickets') ? User::query()->active()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $user = $request->user();
        $assignedTo = $request->integer('assigned_to') ?: null;

        if ($assignedTo !== null && ! $user->can('assign-tickets') && $assignedTo !== $user->id) {
            abort(403);
        }

        $ticket = $this->tickets->create([
            'subject' => $request->string('subject')->trim()->toString(),
            'description' => $request->string('message')->trim()->toString(),
            'requester_name' => $request->string('requester_name')->trim()->toString(),
            'requester_email' => $request->string('requester_email')->toString(),
            'requester_locale' => app()->getLocale(),
            'category_id' => $request->integer('category_id') ?: null,
            'priority' => TicketPriority::from($request->input('priority', TicketPriority::Normal->value)),
            'channel' => TicketChannel::Agent,
            'assigned_to' => $assignedTo,
        ], $request->file('attachments', []), $user, true);

        return redirect()
            ->route('app.tickets.show', $ticket)
            ->with('status', __('app.tickets.created'));
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'category',
            'assignee',
            'creator',
            'csat',
            'tags',
            'messages.user',
            'messages.attachments',
        ]);

        $user = $request->user();

        return view('app.tickets.show', [
            'ticket' => $ticket,
            'cannedReplies' => CannedReply::query()->active()->get(),
            'tags' => Tag::query()->orderBy('name_en')->get(),
            'agents' => $user->can('assign-tickets') ? User::query()->active()->orderBy('name')->get() : collect([$user]),
            'canAssign' => $user->can('assign-tickets'),
            'trackingUrl' => $this->tickets->trackingUrl($ticket),
            'csatUrl' => $ticket->isResolved() ? $this->tickets->csatUrl($ticket) : null,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:'.config('helpdesk.attachments.max_files')],
            'attachments.*' => [
                'file',
                'max:'.config('helpdesk.attachments.max_kilobytes'),
                'mimes:'.implode(',', config('helpdesk.attachments.extensions')),
            ],
        ]);

        if ($ticket->assigned_to === null) {
            $this->tickets->assign($ticket, $request->user(), $request->user());
        }

        $this->tickets->addMessage($ticket, [
            'author_type' => MessageAuthor::Agent,
            'author_name' => $request->user()->name,
            'author_email' => $request->user()->email,
            'body' => $validated['body'],
            'source' => 'agent',
            'user' => $request->user(),
        ], $request->file('attachments', []), true);

        return back()->with('status', __('app.tickets.reply_sent'));
    }

    public function note(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        $this->tickets->addMessage($ticket, [
            'author_type' => MessageAuthor::Agent,
            'author_name' => $request->user()->name,
            'author_email' => $request->user()->email,
            'body' => $validated['body'],
            'is_internal' => true,
            'source' => 'agent',
            'user' => $request->user(),
        ], [], false);

        return back()->with('status', __('app.tickets.note_saved'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $user = $request->user();
        $assignee = isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null;

        if ($assignee !== $ticket->assigned_to) {
            $canAssignAnyone = $user->can('assign-tickets');
            $claiming = $ticket->assigned_to === null && $assignee === $user->id;

            if (! $canAssignAnyone && ! $claiming) {
                abort(403, __('app.tickets.assign_forbidden'));
            }
        }

        if ($ticket->assigned_to === null && ! $user->can('assign-tickets')) {
            $assignee = $user->id;
        }

        $this->tickets->changePriority($ticket, TicketPriority::from($validated['priority']), $user);
        $this->tickets->assign($ticket, $assignee ? User::find($assignee) : null, $user);
        $ticket->tags()->sync($validated['tags'] ?? []);

        if (TicketStatus::from($validated['status']) !== $ticket->status) {
            $this->tickets->changeStatus($ticket, TicketStatus::from($validated['status']), $user);
        }

        return back()->with('status', __('app.tickets.updated'));
    }

    public function solve(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->tickets->changeStatus($ticket, TicketStatus::Solved, $request->user());

        return back()->with('status', __('app.tickets.solved'));
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->tickets->changeStatus($ticket, TicketStatus::Closed, $request->user());

        return back()->with('status', __('app.tickets.closed'));
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->tickets->changeStatus($ticket, TicketStatus::Open, $request->user());

        return back()->with('status', __('app.tickets.reopened'));
    }

    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->assigned_to !== null) {
            return back()->withErrors(['assign' => __('app.tickets.already_assigned')]);
        }

        $this->tickets->assign($ticket, $request->user(), $request->user());

        return back()->with('status', __('app.tickets.claimed'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $disk = Storage::disk(config('helpdesk.attachments.disk'));

        foreach ($ticket->attachments as $attachment) {
            $disk->delete($attachment->path);
        }

        $reference = $ticket->reference;
        $ticket->delete();

        return redirect()
            ->route('app.tickets.index')
            ->with('status', __('app.tickets.deleted', ['reference' => $reference]));
    }
}
