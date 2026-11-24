<?php

namespace App\Http\Controllers\PublicPortal;

use App\Enums\MessageAuthor;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyToTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketTrackingController extends Controller
{
    public function __construct(protected TicketService $tickets) {}

    public function form(): View
    {
        return view('public.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'regex:/^TKT-\d{4}-\d{4,}$/i'],
            'email' => ['required', 'email'],
        ]);

        $ticket = Ticket::query()
            ->where('reference', strtoupper($validated['reference']))
            ->whereRaw('lower(requester_email) = ?', [mb_strtolower($validated['email'])])
            ->first();

        if (! $ticket) {
            throw ValidationException::withMessages([
                'reference' => __('portal.track.not_found'),
            ]);
        }

        return redirect()->to($this->tickets->trackingUrl($ticket));
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'category',
            'assignee',
            'csat',
            'messages' => fn ($query) => $query->where('is_internal', false)->with('attachments')->orderBy('created_at'),
        ]);

        return view('public.ticket', [
            'ticket' => $ticket,
            'trackingUrl' => $this->tickets->trackingUrl($ticket),
            'csatUrl' => $ticket->isResolved() && ! $ticket->csat ? $this->tickets->csatUrl($ticket) : null,
            'maxFiles' => (int) config('helpdesk.attachments.max_files', 5),
        ]);
    }

    public function reply(ReplyToTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->addMessage($ticket, [
            'author_type' => MessageAuthor::Requester,
            'author_name' => $ticket->requester_name,
            'author_email' => $ticket->requester_email,
            'body' => $request->string('message')->trim()->toString(),
            'source' => 'portal',
        ], $request->file('attachments', []), false);

        return redirect()->back()->with('status', __('portal.ticket.reply_sent'));
    }
}
