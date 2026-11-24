<?php

namespace Tests\Feature;

use App\Enums\MessageAuthor;
use App\Enums\TicketStatus;
use App\Mail\TicketReplyMail;
use App\Mail\TicketSolvedMail;
use App\Models\Tag;
use App\Services\TicketService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentWorkspaceTest extends TestCase
{
    public function test_agent_can_reply_claim_and_save_an_internal_note(): void
    {
        $agent = $this->staff('agent');
        $ticket = $this->makeTicket(['subject' => 'Workspace ticket']);

        $this->actingAs($agent)->post('/app/tickets/'.$ticket->reference.'/reply', [
            'body' => 'Thank you for the details. We are investigating now.',
        ])->assertRedirect();

        $ticket->refresh();

        $this->assertSame($agent->id, $ticket->assigned_to);
        $this->assertNotNull($ticket->first_responded_at);
        Mail::assertSent(TicketReplyMail::class);

        $this->actingAs($agent)->post('/app/tickets/'.$ticket->reference.'/note', [
            'body' => 'Internal: check the storage quota for this tenant.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'is_internal' => true,
            'body' => 'Internal: check the storage quota for this tenant.',
        ]);

        $this->get(app(TicketService::class)->trackingUrl($ticket))
            ->assertDontSee('check the storage quota');
    }

    public function test_agent_can_update_status_priority_tags_and_solve(): void
    {
        $agent = $this->staff('agent');
        $ticket = $this->makeTicket(['assigned_to' => $agent->id]);
        $tag = Tag::create(['slug' => 'billing', 'name_ar' => 'فواتير', 'name_en' => 'Billing', 'color' => 'teal']);

        $this->actingAs($agent)->patch('/app/tickets/'.$ticket->reference, [
            'status' => 'solved',
            'priority' => 'high',
            'assigned_to' => $agent->id,
            'tags' => [$tag->id],
        ])->assertRedirect();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Solved, $ticket->status);
        $this->assertSame('high', $ticket->priority->value);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertTrue($ticket->tags->contains($tag->id));
        Mail::assertSent(TicketSolvedMail::class);
    }

    public function test_customers_can_reopen_a_solved_ticket_by_replying(): void
    {
        $ticket = $this->makeTicket();
        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);

        $this->post(app(TicketService::class)->trackingUrl($ticket->fresh()), [
            'message' => 'The issue is still happening.',
        ])->assertRedirect();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_canned_replies_are_managed_by_supervisors_only(): void
    {
        $supervisor = $this->staff('supervisor');
        $agent = $this->staff('agent');

        $payload = [
            'title_ar' => 'رد ترحيبي',
            'title_en' => 'Welcome reply',
            'body_ar' => 'شكرًا لتواصلك معنا، سنتابع طلبك قريبًا.',
            'body_en' => 'Thank you for contacting us; we will follow up shortly.',
            'is_active' => '1',
        ];

        $this->actingAs($supervisor)->post('/app/canned-replies', $payload)->assertRedirect();
        $this->assertDatabaseHas('canned_replies', ['title_en' => 'Welcome reply']);

        $this->actingAs($agent)->post('/app/canned-replies', $payload)->assertForbidden();
        $this->actingAs($agent)->get('/app/canned-replies')->assertOk()->assertSee('Welcome reply');
    }

    public function test_queue_filters_return_only_matching_tickets(): void
    {
        $agent = $this->staff('agent');
        $other = $this->staff('agent');

        $this->makeTicket(['subject' => 'Urgent visible ticket', 'priority' => 'urgent', 'assigned_to' => $agent->id]);
        $this->makeTicket(['subject' => 'Normal visible ticket', 'priority' => 'normal', 'assigned_to' => $agent->id]);
        $this->makeTicket(['subject' => 'Hidden foreign ticket', 'priority' => 'urgent', 'assigned_to' => $other->id]);

        $this->actingAs($agent)->get('/app/tickets?priority=urgent&status=open')
            ->assertOk()
            ->assertSee('Urgent visible ticket')
            ->assertDontSee('Normal visible ticket')
            ->assertDontSee('Hidden foreign ticket');
    }

    public function test_ticket_search_matches_reference_and_requester(): void
    {
        $agent = $this->staff('agent');

        $ticket = $this->makeTicket([
            'subject' => 'Printer is offline',
            'requester_name' => 'Mona Search',
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($agent)->get('/app/tickets?q='.$ticket->reference)
            ->assertOk()->assertSee('Printer is offline');

        $this->actingAs($agent)->get('/app/tickets?q=Mona')
            ->assertOk()->assertSee('Printer is offline');
    }

    public function test_public_reply_from_the_agent_is_visible_to_the_customer(): void
    {
        $agent = $this->staff('agent');
        $ticket = $this->makeTicket();

        app(TicketService::class)->addMessage($ticket, [
            'author_type' => MessageAuthor::Agent,
            'author_name' => $agent->name,
            'author_email' => $agent->email,
            'body' => 'This public answer should be visible.',
            'user' => $agent,
        ], [], false);

        $this->get(app(TicketService::class)->trackingUrl($ticket))
            ->assertSee('This public answer should be visible.');
    }
}
