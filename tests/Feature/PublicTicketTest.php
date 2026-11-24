<?php

namespace Tests\Feature;

use App\Enums\MessageAuthor;
use App\Enums\TicketStatus;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketReplyMail;
use App\Mail\TicketSolvedMail;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicTicketTest extends TestCase
{
    public function test_a_visitor_can_submit_a_ticket_and_receive_a_signed_tracking_link(): void
    {
        $category = $this->category();

        $response = $this->post('/submit', [
            'requester_name' => 'Sara Tester',
            'requester_email' => 'Sara.Tester@Example.Test',
            'subject' => 'Cannot access my invoices',
            'category_id' => $category->id,
            'message' => 'I cannot open the invoice page since this morning. Please help.',
        ]);

        $ticket = Ticket::query()->firstOrFail();

        $this->assertSame('sara.tester@example.test', $ticket->requester_email);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame($category->id, $ticket->category_id);
        $this->assertMatchesRegularExpression('/^TKT-\d{4}-\d{4}$/', $ticket->reference);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame(MessageAuthor::Requester, $ticket->messages()->first()->author_type);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/t/'.$ticket->reference, $location);

        $this->get($location)->assertOk()->assertSee($ticket->reference)->assertSee('Cannot access my invoices');

        Mail::assertSent(TicketCreatedMail::class);
    }

    public function test_public_reply_reopens_a_solved_ticket(): void
    {
        $this->configureSla();
        $ticket = $this->makeTicket(['subject' => 'Still broken']);

        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);
        $ticket->refresh();
        $this->assertTrue($ticket->isResolved());

        $url = app(TicketService::class)->trackingUrl($ticket);

        $this->post($url, [
            'message' => 'The problem came back after the update.',
        ])->assertRedirect();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertSame(2, $ticket->messages()->where('author_type', 'requester')->count());
        $this->assertNotNull($ticket->last_reply_at);
    }

    public function test_internal_notes_are_hidden_from_the_public_ticket_page(): void
    {
        $ticket = $this->makeTicket();
        $agent = $this->staff();

        app(TicketService::class)->addMessage($ticket, [
            'author_type' => MessageAuthor::Agent,
            'author_name' => $agent->name,
            'author_email' => $agent->email,
            'body' => 'Private internal note: investigate database index.',
            'is_internal' => true,
            'user' => $agent,
        ], [], false);

        $this->get(app(TicketService::class)->trackingUrl($ticket))
            ->assertOk()
            ->assertDontSee('Private internal note');
    }

    public function test_tracking_form_rejects_a_non_matching_email(): void
    {
        $ticket = $this->makeTicket();

        $this->post('/track', [
            'reference' => $ticket->reference,
            'email' => 'someone-else@example.test',
        ])->assertSessionHasErrors('reference');
    }

    public function test_transactional_emails_render_in_both_locales(): void
    {
        $ticket = $this->makeTicket();
        $service = app(TicketService::class);

        $created = new TicketCreatedMail($ticket, $service->trackingUrl($ticket));
        $reply = new TicketReplyMail($ticket, $ticket->messages()->first(), $service->trackingUrl($ticket));
        $solved = new TicketSolvedMail($ticket, $service->csatUrl($ticket));

        foreach (['ar', 'en'] as $locale) {
            $this->assertStringContainsString($ticket->reference, $created->locale($locale)->render());
            $this->assertStringContainsString($ticket->reference, $reply->locale($locale)->render());
            $this->assertStringContainsString($ticket->reference, $solved->locale($locale)->render());
        }
    }

    public function test_tracking_form_redirects_to_the_signed_ticket(): void
    {
        $ticket = $this->makeTicket();

        $response = $this->post('/track', [
            'reference' => strtolower($ticket->reference),
            'email' => $ticket->requester_email,
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/t/'.$ticket->reference, $response->headers->get('Location'));

        $this->get($response->headers->get('Location'))->assertOk();
    }
}
