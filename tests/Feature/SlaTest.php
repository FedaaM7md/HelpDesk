<?php

namespace Tests\Feature;

use App\Enums\MessageAuthor;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Services\TicketService;
use Carbon\Carbon;
use Tests\TestCase;

class SlaTest extends TestCase
{
    public function test_policy_targets_are_applied_per_priority(): void
    {
        $this->configureSla(false);

        $high = $this->makeTicket(['priority' => TicketPriority::High]);
        $urgent = $this->makeTicket(['priority' => TicketPriority::Urgent]);

        $this->assertTrue($high->first_response_due_at->equalTo($high->created_at->copy()->addMinutes(240)));
        $this->assertTrue($high->resolution_due_at->equalTo($high->created_at->copy()->addMinutes(1440)));
        $this->assertTrue($urgent->first_response_due_at->equalTo($urgent->created_at->copy()->addMinutes(60)));
    }

    public function test_business_hours_are_used_when_the_policy_requests_them(): void
    {
        $this->configureSla(true);
        $this->configureBusinessHours();

        Carbon::setTestNow(Carbon::parse('2026-01-01 16:00:00', 'UTC'));

        $ticket = $this->makeTicket(['priority' => TicketPriority::Normal]);

        // 60 minutes on Thursday + 420 minutes on Sunday from 09:00 = Sunday 16:00.
        $this->assertTrue(
            $ticket->first_response_due_at->equalTo(Carbon::parse('2026-01-04 16:00:00', 'UTC')),
            $ticket->first_response_due_at->toDateTimeString(),
        );
    }

    public function test_command_flags_overdue_tickets_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 12:00:00', 'UTC'));

        $overdue = $this->makeTicket(['subject' => 'Overdue ticket']);
        $overdue->forceFill([
            'first_response_due_at' => now()->subHour(),
            'resolution_due_at' => now()->addHours(4),
        ])->save();

        $healthy = $this->makeTicket(['subject' => 'Healthy ticket']);
        $healthy->forceFill([
            'first_response_due_at' => now()->addHour(),
            'resolution_due_at' => now()->addHours(4),
        ])->save();

        $resolved = $this->makeTicket(['subject' => 'Resolved ticket']);
        $resolved->forceFill([
            'first_response_due_at' => now()->subHours(2),
            'status' => TicketStatus::Solved,
            'resolved_at' => now()->subHour(),
            'first_responded_at' => now()->subHours(2),
        ])->save();

        $this->artisan('helpdesk:flag-sla-breaches')->assertExitCode(0);

        $this->assertNotNull($overdue->fresh()->first_response_breached_at);
        $this->assertNull($overdue->fresh()->resolution_breached_at);
        $this->assertNull($healthy->fresh()->first_response_breached_at);
        $this->assertNull($resolved->fresh()->first_response_breached_at);
    }

    public function test_responding_after_the_target_records_a_breach(): void
    {
        $this->configureSla(false);

        $ticket = $this->makeTicket();
        $agent = $this->staff();

        $ticket->forceFill(['first_response_due_at' => now()->subMinutes(5)])->save();

        app(TicketService::class)->addMessage($ticket, [
            'author_type' => MessageAuthor::Agent,
            'author_name' => $agent->name,
            'author_email' => $agent->email,
            'body' => 'A late but useful reply.',
            'user' => $agent,
        ], [], false);

        $ticket->refresh();

        $this->assertNotNull($ticket->first_responded_at);
        $this->assertNotNull($ticket->first_response_breached_at);
    }

    public function test_solving_after_the_resolution_target_records_a_breach(): void
    {
        $ticket = $this->makeTicket();
        $ticket->forceFill(['resolution_due_at' => now()->subHour()])->save();

        app(TicketService::class)->changeStatus($ticket, TicketStatus::Solved);

        $ticket->refresh();

        $this->assertNotNull($ticket->resolved_at);
        $this->assertNotNull($ticket->resolution_breached_at);
    }
}
