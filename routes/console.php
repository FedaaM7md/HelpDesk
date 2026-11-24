<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('helpdesk:flag-sla-breaches')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('helpdesk:import-inbox')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Demo data is reseeded automatically. The command no-ops when DEMO_MODE=false.
Schedule::command('demo:reset --force')
    ->hourly()
    ->withoutOverlapping();
