<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_guests_are_redirected_from_the_workspace(): void
    {
        $this->get('/app/tickets')->assertRedirect(route('login'));
        $this->get('/app/reports')->assertRedirect(route('login'));
    }

    public function test_agents_cannot_open_administration_pages(): void
    {
        $agent = $this->staff('agent');

        $this->actingAs($agent)->get('/app/users')->assertForbidden();
        $this->actingAs($agent)->get('/app/reports')->assertForbidden();
        $this->actingAs($agent)->get('/app/sla')->assertForbidden();
        $this->actingAs($agent)->get('/app/tags')->assertForbidden();
        $this->actingAs($agent)->get('/app/settings/business-hours')->assertForbidden();
    }

    public function test_agent_only_sees_unassigned_or_own_tickets(): void
    {
        $agent = $this->staff('agent');
        $other = $this->staff('agent');

        $unassigned = $this->makeTicket(['subject' => 'Unassigned visible']);
        $own = $this->makeTicket(['subject' => 'Own ticket', 'assigned_to' => $agent->id]);
        $foreign = $this->makeTicket(['subject' => 'Foreign ticket', 'assigned_to' => $other->id]);

        $this->actingAs($agent)->get('/app/tickets/'.$unassigned->reference)->assertOk();
        $this->actingAs($agent)->get('/app/tickets/'.$own->reference)->assertOk();
        $this->actingAs($agent)->get('/app/tickets/'.$foreign->reference)->assertForbidden();

        $this->actingAs($agent)->get('/app/tickets')
            ->assertSee('Unassigned visible')
            ->assertSee('Own ticket')
            ->assertDontSee('Foreign ticket');
    }

    public function test_agent_can_claim_an_unassigned_ticket_but_not_assign_it_to_someone_else(): void
    {
        $agent = $this->staff('agent');
        $other = $this->staff('agent');
        $ticket = $this->makeTicket(['subject' => 'Claimable']);

        $this->actingAs($agent)->patch('/app/tickets/'.$ticket->reference, [
            'status' => 'open',
            'priority' => 'normal',
            'assigned_to' => $other->id,
        ])->assertForbidden();

        $this->actingAs($agent)->patch('/app/tickets/'.$ticket->reference, [
            'status' => 'open',
            'priority' => 'normal',
            'assigned_to' => $agent->id,
        ])->assertRedirect();

        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }

    public function test_supervisor_can_assign_and_view_reports_but_not_manage_users(): void
    {
        $supervisor = $this->staff('supervisor');
        $agent = $this->staff('agent');
        $ticket = $this->makeTicket(['subject' => 'Assign me']);

        $this->actingAs($supervisor)->get('/app/reports')->assertOk();
        $this->actingAs($supervisor)->get('/app/users')->assertForbidden();

        $this->actingAs($supervisor)->patch('/app/tickets/'.$ticket->reference, [
            'status' => 'open',
            'priority' => 'high',
            'assigned_to' => $agent->id,
        ])->assertRedirect();

        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }

    public function test_administrator_can_open_team_management(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get('/app/users')->assertOk();
        $this->actingAs($admin)->get('/app/sla')->assertOk();
        $this->actingAs($admin)->get('/app/settings/business-hours')->assertOk();
    }

    public function test_inactive_users_are_signed_out_of_the_workspace(): void
    {
        $inactive = $this->staff('agent', ['is_active' => false]);

        $this->actingAs($inactive)->get(route('app.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_only_can_delete_tickets(): void
    {
        $agent = $this->staff('agent');
        $admin = $this->staff('admin');
        $ticket = $this->makeTicket();

        $this->actingAs($agent)->delete('/app/tickets/'.$ticket->reference)->assertForbidden();
        $this->actingAs($admin)->delete('/app/tickets/'.$ticket->reference)->assertRedirect();
        $this->assertNull($ticket->fresh());
    }
}
