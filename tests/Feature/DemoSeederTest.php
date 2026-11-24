<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\CsatRating;
use App\Models\KbArticle;
use App\Models\Ticket;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    public function test_demo_seeder_creates_realistic_isp_data(): void
    {
        $this->seed();

        $total = Ticket::query()->count();
        $arabic = Ticket::query()->where('requester_locale', 'ar')->count();

        $this->assertGreaterThanOrEqual(24, $total);
        $this->assertGreaterThanOrEqual(0.85, $arabic / max(1, $total));

        $this->assertDatabaseHas('ticket_categories', ['slug' => 'outage']);
        $this->assertDatabaseHas('ticket_categories', ['slug' => 'speed']);
        $this->assertDatabaseHas('ticket_categories', ['slug' => 'router']);

        $this->assertGreaterThanOrEqual(8, KbArticle::query()->where('status', 'published')->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@demo.helpdesk.test', 'is_demo' => true]);
    }

    public function test_demo_seeder_produces_realistic_sla_states_and_csat(): void
    {
        $this->seed();

        $solved = Ticket::query()
            ->whereIn('status', [TicketStatus::Solved->value, TicketStatus::Closed->value])
            ->count();

        $this->assertGreaterThanOrEqual(40, $solved);
        $this->assertGreaterThanOrEqual(40, CsatRating::query()->count());

        $unresolved = Ticket::query()
            ->whereIn('status', TicketStatus::unresolvedValues())
            ->get();

        $breached = $unresolved->filter(fn (Ticket $ticket) => $ticket->isSlaBreached())->count();

        $this->assertGreaterThanOrEqual(1, $breached);
        $this->assertLessThanOrEqual(
            (int) ceil($unresolved->count() * 0.15),
            $breached,
            'Only about 10% of active tickets should be breached.',
        );

        $this->assertGreaterThan(3, (float) CsatRating::query()->avg('score'));
        $this->assertLessThanOrEqual(5, (float) CsatRating::query()->avg('score'));
        $this->assertGreaterThanOrEqual(
            10,
            CsatRating::query()->where('submitted_at', '>=', now()->subDays(30))->count(),
            'The 30-day satisfaction card needs real recent ratings.',
        );
    }
}
