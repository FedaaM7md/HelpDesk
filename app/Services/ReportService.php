<?php

namespace App\Services;

use App\Models\CsatRating;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @param  array{agent?:int|string|null, priority?:string|null}  $filters
     * @return array<string, mixed>
     */
    public function build(Carbon $from, Carbon $to, array $filters = [], ?User $viewer = null): array
    {
        $timezone = (string) config('helpdesk.timezone', 'UTC');
        $start = $from->copy()->setTimezone($timezone)->startOfDay()->utc();
        $end = $to->copy()->setTimezone($timezone)->endOfDay()->utc();

        $viewer ??= auth()->user();

        $base = Ticket::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['agent'] ?? null, fn ($query, $agent) => $query->where('assigned_to', (int) $agent));

        $tickets = (clone $base)->get();

        $firstResponseMinutes = $tickets
            ->filter(fn (Ticket $ticket) => $ticket->first_responded_at !== null)
            ->map(fn (Ticket $ticket) => max(0, (int) round($ticket->created_at->diffInMinutes($ticket->first_responded_at))))
            ->values();

        $resolutionMinutes = $tickets
            ->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null)
            ->map(fn (Ticket $ticket) => max(0, (int) round($ticket->created_at->diffInMinutes($ticket->resolved_at))))
            ->values();

        $responded = $tickets->filter(fn (Ticket $ticket) => $ticket->first_responded_at !== null && $ticket->first_response_due_at !== null);
        $firstCompliant = $responded->filter(fn (Ticket $ticket) => $ticket->first_responded_at->lessThanOrEqualTo($ticket->first_response_due_at))->count();

        $resolved = $tickets->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null && $ticket->resolution_due_at !== null);
        $resolutionCompliant = $resolved->filter(fn (Ticket $ticket) => $ticket->resolved_at->lessThanOrEqualTo($ticket->resolution_due_at))->count();

        $csatQuery = CsatRating::query()
            ->whereBetween('submitted_at', [$start, $end])
            ->when($viewer, fn ($query) => $query->whereHas('ticket', fn ($q) => $q->visibleTo($viewer)))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->whereHas('ticket', fn ($q) => $q->where('priority', $priority)))
            ->when($filters['agent'] ?? null, fn ($query, $agent) => $query->whereHas('ticket', fn ($q) => $q->where('assigned_to', (int) $agent)));

        $csatScores = (clone $csatQuery)->pluck('score');

        $distribution = collect(range(1, 5))->mapWithKeys(fn ($score) => [
            $score => (clone $csatQuery)->where('score', $score)->count(),
        ])->all();

        $perAgent = $this->perAgent($viewer, $start, $end, $filters);

        return [
            'start' => $start,
            'end' => $end,
            'localFrom' => $from->copy()->setTimezone($timezone)->startOfDay(),
            'localTo' => $to->copy()->setTimezone($timezone)->startOfDay(),
            'total' => $tickets->count(),
            'unresolved' => $tickets->filter(fn (Ticket $ticket) => $ticket->status->isUnresolved())->count(),
            'solved' => $tickets->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null)->count(),
            'avg_first_response' => $firstResponseMinutes->isEmpty() ? null : (int) round($firstResponseMinutes->avg()),
            'avg_resolution' => $resolutionMinutes->isEmpty() ? null : (int) round($resolutionMinutes->avg()),
            'first_response_compliance' => $responded->isEmpty() ? null : (int) round($firstCompliant / $responded->count() * 100),
            'resolution_compliance' => $resolved->isEmpty() ? null : (int) round($resolutionCompliant / $resolved->count() * 100),
            'first_response_sample' => $responded->count(),
            'resolution_sample' => $resolved->count(),
            'breached' => $tickets->filter(fn (Ticket $ticket) => $ticket->isSlaBreached())->count(),
            'csat_average' => $csatScores->isEmpty() ? null : round((float) $csatScores->avg(), 2),
            'csat_count' => $csatScores->count(),
            'csat_distribution' => $distribution,
            'status_breakdown' => $tickets->countBy(fn (Ticket $ticket) => $ticket->status->value)->all(),
            'priority_breakdown' => $tickets->countBy(fn (Ticket $ticket) => $ticket->priority->value)->all(),
            'trend' => $this->trend($tickets, $from, $to, $timezone),
            'per_agent' => $perAgent,
        ];
    }

    /**
     * @param  array{agent?:int|string|null, priority?:string|null}  $filters
     * @return array<int, array<string, mixed>>
     */
    protected function perAgent(User $viewer, Carbon $start, Carbon $end, array $filters): array
    {
        $agents = User::query()
            ->whereIn('role', ['agent', 'supervisor'])
            ->where('is_active', true)
            ->when($filters['agent'] ?? null, fn ($query, $agent) => $query->whereKey((int) $agent))
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($agents as $agent) {
            $tickets = Ticket::query()
                ->where('assigned_to', $agent->id)
                ->whereBetween('created_at', [$start, $end])
                ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
                ->get();

            $first = $tickets
                ->filter(fn (Ticket $ticket) => $ticket->first_responded_at !== null)
                ->map(fn (Ticket $ticket) => max(0, (int) round($ticket->created_at->diffInMinutes($ticket->first_responded_at))));

            $resolution = $tickets
                ->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null)
                ->map(fn (Ticket $ticket) => max(0, (int) round($ticket->created_at->diffInMinutes($ticket->resolved_at))));

            $csat = CsatRating::query()
                ->whereHas('ticket', fn ($query) => $query->where('assigned_to', $agent->id)->whereBetween('created_at', [$start, $end]))
                ->whereBetween('submitted_at', [$start, $end])
                ->avg('score');

            $rows[] = [
                'id' => $agent->id,
                'name' => $agent->name,
                'job_title' => $agent->job_title,
                'assigned' => $tickets->count(),
                'open' => $tickets->filter(fn (Ticket $ticket) => $ticket->status->isUnresolved())->count(),
                'solved' => $tickets->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null)->count(),
                'avg_first_response' => $first->isEmpty() ? null : (int) round($first->avg()),
                'avg_resolution' => $resolution->isEmpty() ? null : (int) round($resolution->avg()),
                'csat' => $csat !== null ? round((float) $csat, 2) : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return array<int, array<string, mixed>>
     */
    protected function trend(Collection $tickets, Carbon $from, Carbon $to, string $timezone): array
    {
        $created = $tickets->groupBy(fn (Ticket $ticket) => $ticket->created_at->copy()->setTimezone($timezone)->toDateString())->map->count();
        $solved = $tickets
            ->filter(fn (Ticket $ticket) => $ticket->resolved_at !== null)
            ->groupBy(fn (Ticket $ticket) => $ticket->resolved_at->copy()->setTimezone($timezone)->toDateString())
            ->map->count();

        $rows = [];
        $cursor = $from->copy()->setTimezone($timezone)->startOfDay();
        $last = $to->copy()->setTimezone($timezone)->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->toDateString();

            $rows[] = [
                'date' => $key,
                'label' => $cursor->copy()->translatedFormat('d M'),
                'created' => (int) ($created[$key] ?? 0),
                'solved' => (int) ($solved[$key] ?? 0),
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        $this->putCsvRow($handle, ['agent', 'assigned', 'open', 'solved', 'avg_first_response_minutes', 'avg_resolution_minutes', 'csat']);

        foreach ($report['per_agent'] as $row) {
            $this->putCsvRow($handle, [
                $row['name'],
                $row['assigned'],
                $row['open'],
                $row['solved'],
                $row['avg_first_response'] ?? '',
                $row['avg_resolution'] ?? '',
                $row['csat'] ?? '',
            ]);
        }

        $this->putCsvRow($handle, []);
        $this->putCsvRow($handle, ['metric', 'value']);
        $this->putCsvRow($handle, ['total', $report['total']]);
        $this->putCsvRow($handle, ['unresolved', $report['unresolved']]);
        $this->putCsvRow($handle, ['solved', $report['solved']]);
        $this->putCsvRow($handle, ['avg_first_response_minutes', $report['avg_first_response'] ?? '']);
        $this->putCsvRow($handle, ['avg_resolution_minutes', $report['avg_resolution'] ?? '']);
        $this->putCsvRow($handle, ['first_response_compliance_percent', $report['first_response_compliance'] ?? '']);
        $this->putCsvRow($handle, ['resolution_compliance_percent', $report['resolution_compliance'] ?? '']);
        $this->putCsvRow($handle, ['csat_average', $report['csat_average'] ?? '']);

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".$csv;
    }

    /**
     * Prevent spreadsheet formula injection while keeping values readable.
     *
     * @param  resource  $handle
     * @param  array<int, mixed>  $row
     */
    protected function putCsvRow($handle, array $row): void
    {
        fputcsv($handle, array_map(function ($value) {
            if (! is_string($value)) {
                return $value;
            }

            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$value;
            }

            return $value;
        }, $row));
    }
}
