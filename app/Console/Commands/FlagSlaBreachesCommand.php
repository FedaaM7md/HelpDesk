<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class FlagSlaBreachesCommand extends Command
{
    protected $signature = 'helpdesk:flag-sla-breaches';

    protected $description = 'Flag open tickets that have passed their first-response or resolution SLA target';

    public function handle(SlaService $sla): int
    {
        $counts = $sla->flagBreaches();

        $this->info(__('app.sla.flagged', $counts));

        return self::SUCCESS;
    }
}
