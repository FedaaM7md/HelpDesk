<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Services\TicketService;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SignedLinkTest extends TestCase
{
    public function test_unsigned_and_tampered_ticket_links_are_rejected(): void
    {
        $ticket = $this->makeTicket();

        $this->get('/t/'.$ticket->reference)->assertForbidden();

        $signed = URL::signedRoute('portal.ticket.show', ['ticket' => $ticket->reference]);
        $this->get($signed)->assertOk();

        $tampered = preg_replace('/(signature=)[^&]+/', '$1deadbeef', $signed);
        $this->get($tampered)->assertForbidden();
    }

    public function test_satisfaction_link_requires_a_valid_unexpired_signature(): void
    {
        $ticket = $this->makeTicket();
        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);

        $ticket->refresh();

        $valid = URL::temporarySignedRoute('portal.csat.show', now()->addDay(), ['ticket' => $ticket->reference]);
        $expired = URL::temporarySignedRoute('portal.csat.show', now()->subMinute(), ['ticket' => $ticket->reference]);

        $this->get($valid)->assertOk();
        $this->get($expired)->assertForbidden();
        $this->get('/csat/'.$ticket->reference)->assertForbidden();
    }

    public function test_customer_can_submit_a_satisfaction_rating_once(): void
    {
        $ticket = $this->makeTicket();
        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);
        $ticket->refresh();

        $url = URL::temporarySignedRoute('portal.csat.show', now()->addDay(), ['ticket' => $ticket->reference]);

        $this->post($url, [
            'score' => 5,
            'comment' => 'Fast and clear support.',
        ])->assertRedirect();

        $this->assertDatabaseHas('csat_ratings', [
            'ticket_id' => $ticket->id,
            'score' => 5,
            'comment' => 'Fast and clear support.',
        ]);

        $this->post($url, ['score' => 1])->assertRedirect();
        $this->assertDatabaseCount('csat_ratings', 1);
    }

    public function test_csat_is_not_available_before_the_ticket_is_solved(): void
    {
        $ticket = $this->makeTicket();

        $url = URL::temporarySignedRoute('portal.csat.show', now()->addDay(), ['ticket' => $ticket->reference]);

        $this->get($url)->assertNotFound();
    }

    public function test_csat_rejects_out_of_range_scores(): void
    {
        $ticket = $this->makeTicket();
        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);
        $ticket->refresh();

        $url = URL::temporarySignedRoute('portal.csat.show', now()->addDay(), ['ticket' => $ticket->reference]);

        $this->from($url)->post($url, ['score' => 9])->assertSessionHasErrors('score');
        $this->assertSame(0, $ticket->csat()->count());
    }
}
