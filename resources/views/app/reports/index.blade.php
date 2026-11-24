@extends('layouts.app')

@section('title', __('reports.title'))

@section('content')
    @php
        $maxTrend = max(1, collect($report['trend'])->max(fn ($row) => max($row['created'], $row['solved'])) ?? 1);
        $trendCount = count($report['trend']);
        $labelEvery = max(1, (int) ceil($trendCount / 10));
        $statusTotal = max(1, array_sum($report['status_breakdown']));
        $priorityTotal = max(1, array_sum($report['priority_breakdown']));
        $maxCsat = max(1, max($report['csat_distribution'] ?: [0]));
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.insights_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('reports.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('reports.subtitle') }}</p>
        </div>
        <a href="{{ route('app.reports.export', array_filter(['from' => $from, 'to' => $to, 'agent' => $filters['agent'] ?? null, 'priority' => $filters['priority'] ?? null])) }}"
            class="btn-secondary btn-sm shrink-0">
            <x-icon name="download" class="h-4 w-4" /> {{ __('reports.export') }}
        </a>
    </div>

    <form method="GET" action="{{ route('app.reports.index') }}" class="card mt-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[160px_160px_1fr_1fr_auto] lg:items-end">
            <div>
                <label for="report-from" class="label text-xs">{{ __('reports.from') }}</label>
                <input id="report-from" type="date" name="from" value="{{ $from }}" class="input py-2 text-xs">
            </div>
            <div>
                <label for="report-to" class="label text-xs">{{ __('reports.to') }}</label>
                <input id="report-to" type="date" name="to" value="{{ $to }}" class="input py-2 text-xs">
            </div>
            <div>
                <label for="report-agent" class="label text-xs">{{ __('reports.agent') }}</label>
                <select id="report-agent" name="agent" class="select py-2 text-xs">
                    <option value="">{{ __('reports.all_agents') }}</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((string) ($filters['agent'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-priority" class="label text-xs">{{ __('reports.priority') }}</label>
                <select id="report-priority" name="priority" class="select py-2 text-xs">
                    <option value="">{{ __('reports.all_priorities') }}</option>
                    @foreach (\App\Enums\TicketPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary btn-sm flex-1 lg:flex-none">{{ __('reports.apply') }}</button>
                <a href="{{ route('app.reports.index') }}" class="btn-secondary btn-sm">{{ __('common.actions.reset') }}</a>
            </div>
        </div>
        <p class="mt-3 text-[10px] text-slate-400">{{ __('reports.range_note', ['timezone' => __('common.timezone_label')]) }}</p>
    </form>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $kpis = [
                ['label' => __('reports.total'), 'value' => $report['total'], 'icon' => 'ticket', 'tone' => 'brand'],
                ['label' => __('reports.unresolved'), 'value' => $report['unresolved'], 'icon' => 'inbox', 'tone' => 'amber'],
                ['label' => __('reports.solved'), 'value' => $report['solved'], 'icon' => 'check', 'tone' => 'teal'],
                ['label' => __('reports.sla_breached'), 'value' => $report['breached'], 'icon' => 'alert', 'tone' => 'rose'],
            ];
            $tones = [
                'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'teal' => 'bg-teal-50 text-teal-700 ring-teal-100',
                'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="card p-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $tones[$kpi['tone']] }} ring-1">
                    <x-icon :name="$kpi['icon']" class="h-4 w-4" />
                </span>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ $kpi['value'] }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('reports.avg_first') }}</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ hd_duration($report['avg_first_response']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('reports.avg_resolution') }}</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ hd_duration($report['avg_resolution']) }}</p>
        </div>
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('reports.first_compliance') }}</p>
                <span class="text-sm font-bold {{ ($report['first_response_compliance'] ?? 0) >= 90 ? 'text-teal-600' : (($report['first_response_compliance'] ?? 0) >= 70 ? 'text-amber-600' : 'text-rose-600') }}">
                    {{ $report['first_response_compliance'] !== null ? $report['first_response_compliance'].'%' : '—' }}
                </span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-teal-500" style="width: {{ $report['first_response_compliance'] ?? 0 }}%"></div>
            </div>
            <p class="mt-2 text-[10px] text-slate-400">{{ __('reports.first_response_compliance_hint') }}</p>
        </div>
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('reports.resolution_compliance') }}</p>
                <span class="text-sm font-bold {{ ($report['resolution_compliance'] ?? 0) >= 90 ? 'text-teal-600' : (($report['resolution_compliance'] ?? 0) >= 70 ? 'text-amber-600' : 'text-rose-600') }}">
                    {{ $report['resolution_compliance'] !== null ? $report['resolution_compliance'].'%' : '—' }}
                </span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $report['resolution_compliance'] ?? 0 }}%"></div>
            </div>
            <p class="mt-2 text-[10px] text-slate-400">{{ __('reports.resolution_compliance_hint') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr] xl:items-start">
        <section class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">{{ __('reports.volume') }}</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ __('reports.volume_hint') }}</p>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-medium text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-600"></span>{{ __('reports.created_series') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-teal-500"></span>{{ __('reports.solved_series') }}</span>
                </div>
            </div>

            @if ($report['total'] === 0)
                <div class="mt-6">
                    <x-empty-state icon="chart" :title="__('reports.no_data')" :description="__('reports.empty_chart')" />
                </div>
            @else
                <div class="mt-6 flex h-52 items-end gap-0.5 overflow-hidden sm:gap-1">
                    @foreach ($report['trend'] as $point)
                        <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                            title="{{ $point['date'] }} · {{ __('reports.created_series') }}: {{ $point['created'] }} · {{ __('reports.solved_series') }}: {{ $point['solved'] }}">
                            <div class="flex h-44 w-full items-end justify-center gap-px">
                                <span class="w-1/2 rounded-t bg-brand-600/80 transition group-hover:bg-brand-600"
                                    style="height: {{ $point['created'] > 0 ? max(2, round($point['created'] / $maxTrend * 100)) : 0 }}%"></span>
                                <span class="w-1/2 rounded-t bg-teal-500/80 transition group-hover:bg-teal-500"
                                    style="height: {{ $point['solved'] > 0 ? max(2, round($point['solved'] / $maxTrend * 100)) : 0 }}%"></span>
                            </div>
                            <span class="mt-1 h-3 text-[8px] text-slate-400">
                                @if ($loop->index % $labelEvery === 0)
                                    {{ $point['label'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="space-y-4">
            <section class="card p-5">
                <h3 class="text-sm font-bold text-slate-900">{{ __('reports.status_breakdown') }}</h3>
                <ul class="mt-4 space-y-3">
                    @foreach (\App\Enums\TicketStatus::cases() as $status)
                        @php($count = (int) ($report['status_breakdown'][$status->value] ?? 0))
                        <li>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="flex items-center gap-2 text-slate-600">
                                    <span class="h-2 w-2 rounded-full {{ match ($status) {
                                        \App\Enums\TicketStatus::Open => 'bg-sky-500',
                                        \App\Enums\TicketStatus::Pending => 'bg-amber-400',
                                        \App\Enums\TicketStatus::OnHold => 'bg-slate-400',
                                        \App\Enums\TicketStatus::Solved => 'bg-teal-500',
                                        \App\Enums\TicketStatus::Closed => 'bg-slate-600',
                                    } }}"></span>
                                    {{ $status->label() }}
                                </span>
                                <span class="font-semibold text-slate-700">{{ $count }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-slate-400" style="width: {{ round($count / $statusTotal * 100, 1) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-5">
                <h3 class="text-sm font-bold text-slate-900">{{ __('reports.priority_breakdown') }}</h3>
                <ul class="mt-4 space-y-3">
                    @foreach (\App\Enums\TicketPriority::cases() as $priority)
                        @php($count = (int) ($report['priority_breakdown'][$priority->value] ?? 0))
                        <li>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="text-slate-600">{{ $priority->label() }}</span>
                                <span class="font-semibold text-slate-700">{{ $count }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ match ($priority) {
                                    \App\Enums\TicketPriority::Urgent => 'bg-rose-500',
                                    \App\Enums\TicketPriority::High => 'bg-amber-400',
                                    \App\Enums\TicketPriority::Normal => 'bg-sky-500',
                                    \App\Enums\TicketPriority::Low => 'bg-slate-400',
                                } }}" style="width: {{ round($count / $priorityTotal * 100, 1) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.5fr] xl:items-start">
        <section class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">{{ __('reports.customer_satisfaction') }}</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ __('reports.ratings_count', ['count' => $report['csat_count']]) }}</p>
                </div>
                <div class="text-end">
                    <p class="text-2xl font-bold text-slate-900">{{ $report['csat_average'] ?? '—' }}<span class="text-xs font-medium text-slate-400"> / 5</span></p>
                </div>
            </div>

            <div class="mt-5 space-y-2.5">
                @foreach (array_reverse($report['csat_distribution'], true) as $score => $count)
                    <div class="flex items-center gap-2">
                        <span class="flex w-8 items-center gap-1 text-[11px] font-semibold text-slate-500">
                            {{ $score }} <x-icon name="star" class="h-3 w-3 text-amber-400" />
                        </span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-400" style="width: {{ round($count / $maxCsat * 100, 1) }}%"></div>
                        </div>
                        <span class="w-6 text-end text-[11px] text-slate-500">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-[10px] leading-5 text-slate-400">{{ __('reports.sample_note') }}</p>
        </section>

        <section class="table-wrap">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">{{ __('reports.per_agent') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <caption class="sr-only">{{ __('reports.table_caption') }}</caption>
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('reports.agent') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.assigned') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.open') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.solved') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.avg_first_short') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.avg_resolution_short') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('reports.csat') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['per_agent'] as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-avatar :name="$row['name']" size="sm" />
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">{{ $row['name'] }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $row['job_title'] ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end text-xs text-slate-600">{{ $row['assigned'] }}</td>
                                <td class="px-4 py-3 text-end text-xs text-slate-600">{{ $row['open'] }}</td>
                                <td class="px-4 py-3 text-end text-xs font-semibold text-teal-700">{{ $row['solved'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-end text-[11px] text-slate-500">{{ hd_duration($row['avg_first_response']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-end text-[11px] text-slate-500">{{ hd_duration($row['avg_resolution']) }}</td>
                                <td class="px-4 py-3 text-end text-xs font-semibold text-slate-700">{{ $row['csat'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('reports.empty_agents') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <p class="mt-5 text-[10px] leading-5 text-slate-400">
        {{ __('reports.compliance_sample', ['first' => $report['first_response_sample'], 'resolution' => $report['resolution_sample']]) }}
        · {{ __('reports.export_hint') }}
    </p>
@endsection
