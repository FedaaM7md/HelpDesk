<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CsatController extends Controller
{
    public function __construct(protected TicketService $tickets) {}

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->isResolved(), 404);

        return view('public.csat', [
            'ticket' => $ticket->load('csat'),
        ]);
    }

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->isResolved(), 404);

        if ($ticket->csat()->exists()) {
            return redirect()->back()->with('status', __('portal.csat.already'));
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->tickets->recordCsat($ticket, (int) $validated['score'], $validated['comment'] ?? null);

        return redirect()->back()->with('status', __('portal.csat.thanks'));
    }
}
