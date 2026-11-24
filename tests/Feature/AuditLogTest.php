<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    public function test_creating_a_team_member_writes_an_audit_entry(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->post('/app/users', [
            'name' => 'New Agent',
            'email' => 'new-agent@example.test',
            'role' => 'agent',
            'locale' => 'ar',
            'password' => 'Str0ngPass!',
            'password_confirmation' => 'Str0ngPass!',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'user_id' => $admin->id,
        ]);
    }

    public function test_only_administrators_can_open_the_audit_log(): void
    {
        $agent = $this->staff('agent');
        $supervisor = $this->staff('supervisor');
        $admin = $this->staff('admin');

        $this->actingAs($agent)->get('/app/audit')->assertForbidden();
        $this->actingAs($supervisor)->get('/app/audit')->assertForbidden();
        $this->actingAs($admin)->get('/app/audit')->assertOk();
    }

    public function test_audit_log_page_lists_recent_entries(): void
    {
        $admin = $this->staff('admin');

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'sla.updated',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/app/audit')
            ->assertOk()
            ->assertSee(__('app.audit.actions.sla.updated'));
    }
}
