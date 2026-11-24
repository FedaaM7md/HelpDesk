<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\CarbonInterface;

class SlaService
{
    public function __construct(protected SlaCalculator $calculator) {}

    /**
     * Fallback targets (in minutes) used when no active policy exists.
     *
     * @var array<string, array{0:int,1:int}>
     */
    public const FALLBACK = [
        'urgent' => [60, 480],
        'high' => [240, 1440],
        'normal' => [480, 2880],
        'low' => [1440, 4320],
    ];

    public function apply(Ticket $ticket, ?CarbonInterface $from = null): Ticket
    {
        $policy = SlaPolicy::forPriority($ticket->priority);
        $useBusinessHours = $policy?->use_business_hours ?? true;
        $start = $from ?? $ticket->created_at ?? now();

        [$firstResponse, $resolution] = $policy
            ? [$policy->first_response_minutes, $policy->resolution_minutes]
            : self::FALLBACK[$ticket->priority->value];

        $ticket->first_response_due_at = $this->calculator->dueAt($start, $firstResponse, $useBusinessHours);
        $ticket->resolution_due_at = $this->calculator->dueAt($start, $resolution, $useBusinessHours);

        return $ticket;
    }

    public function firstResponseTarget(TicketPriority $priority): int
    {
        return SlaPolicy::forPriority($priority)?->first_response_minutes
            ?? self::FALLBACK[$priority->value][0];
    }

    public function resolutionTarget(TicketPriority $priority): int
    {
        return SlaPolicy::forPriority($priority)?->resolution_minutes
            ?? self::FALLBACK[$priority->value][1];
    }

    /**
     * Flag every ticket that is past an SLA target and not yet flagged.
     *
     * @return array{first_response:int, resolution:int}
     */
    public function flagBreaches(): array
    {
        $now = now();
        $firstResponse = 0;
        $resolution = 0;

        Ticket::query()
            ->unresolved()
            ->whereNull('first_responded_at')
            ->whereNull('first_response_breached_at')
            ->whereNotNull('first_response_due_at')
            ->where('first_response_due_at', '<=', $now)
            ->select('id')
            ->chunkById(200, function ($tickets) use ($now, &$firstResponse) {
                $firstResponse += Ticket::whereIn('id', $tickets->pluck('id'))
                    ->update(['first_response_breached_at' => $now, 'updated_at' => $now]);
            });

        Ticket::query()
            ->unresolved()
            ->whereNull('resolved_at')
            ->whereNull('resolution_breached_at')
            ->whereNotNull('resolution_due_at')
            ->where('resolution_due_at', '<=', $now)
            ->select('id')
            ->chunkById(200, function ($tickets) use ($now, &$resolution) {
                $resolution += Ticket::whereIn('id', $tickets->pluck('id'))
                    ->update(['resolution_breached_at' => $now, 'updated_at' => $now]);
            });

        return ['first_response' => $firstResponse, 'resolution' => $resolution];
    }

    public function registerFirstResponse(Ticket $ticket): void
    {
        if ($ticket->first_responded_at) {
            return;
        }

        $ticket->first_responded_at = now();

        if ($ticket->first_response_due_at && now()->greaterThan($ticket->first_response_due_at)) {
            $ticket->first_response_breached_at ??= now();
        }
    }

    public function registerResolution(Ticket $ticket): void
    {
        $ticket->resolved_at ??= now();

        if ($ticket->resolution_due_at && $ticket->resolved_at->greaterThan($ticket->resolution_due_at)) {
            $ticket->resolution_breached_at ??= now();
        }
    }
}
