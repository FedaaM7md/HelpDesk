<?php

namespace Tests\Unit;

use App\Services\SlaCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    public function test_business_hours_skip_closed_days_and_evenings(): void
    {
        $this->configureBusinessHours();
        $calculator = app(SlaCalculator::class);

        // Thursday 16:00 + 2h = 60 minutes Thursday + 60 minutes Sunday 09:00.
        $due = $calculator->dueAt(Carbon::parse('2026-01-01 16:00:00', 'UTC'), 120, true);

        $this->assertTrue($due->equalTo(Carbon::parse('2026-01-04 10:00:00', 'UTC')), $due->toDateTimeString());
    }

    public function test_start_before_opening_moves_to_opening_time(): void
    {
        $this->configureBusinessHours();
        $calculator = app(SlaCalculator::class);

        $due = $calculator->dueAt(Carbon::parse('2026-01-05 07:00:00', 'UTC'), 60, true);

        $this->assertTrue($due->equalTo(Carbon::parse('2026-01-05 10:00:00', 'UTC')), $due->toDateTimeString());
    }

    public function test_weekend_is_skipped(): void
    {
        $this->configureBusinessHours();
        $calculator = app(SlaCalculator::class);

        // Friday is closed.
        $due = $calculator->dueAt(Carbon::parse('2026-01-02 10:00:00', 'UTC'), 60, true);

        $this->assertTrue($due->equalTo(Carbon::parse('2026-01-04 10:00:00', 'UTC')), $due->toDateTimeString());
    }

    public function test_targets_can_run_around_the_clock(): void
    {
        $this->configureBusinessHours();
        $calculator = app(SlaCalculator::class);

        $due = $calculator->dueAt(Carbon::parse('2026-01-02 23:00:00', 'UTC'), 90, false);

        $this->assertTrue($due->equalTo(Carbon::parse('2026-01-03 00:30:00', 'UTC')), $due->toDateTimeString());
    }

    public function test_without_business_hours_it_falls_back_to_calendar_minutes(): void
    {
        $calculator = app(SlaCalculator::class);

        $due = $calculator->dueAt(Carbon::parse('2026-01-01 10:00:00', 'UTC'), 60, true);

        $this->assertTrue($due->equalTo(Carbon::parse('2026-01-01 11:00:00', 'UTC')), $due->toDateTimeString());
    }
}
