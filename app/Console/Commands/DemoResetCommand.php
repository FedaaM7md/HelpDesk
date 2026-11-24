<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Reset without a confirmation prompt}';

    protected $description = 'Reseed the demo dataset. Only runs while demo mode is enabled.';

    public function handle(): int
    {
        if (! (bool) config('helpdesk.demo')) {
            $this->warn('Demo mode is disabled (DEMO_MODE=false). Nothing was reset.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && $this->input->isInteractive()) {
            if (! $this->confirm('Reset all demo data? Tickets, users and settings will be reseeded.')) {
                $this->info('Reset cancelled.');

                return self::SUCCESS;
            }
        }

        $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->info('Demo dataset reseeded.');

        return self::SUCCESS;
    }
}
