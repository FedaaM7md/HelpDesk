@extends('layouts.app')

@section('title', __('app.dashboard.title'))

@section('content')
    @php
        $user = auth()->user();
        $totalStatuses = max(1, $statusCounts->sum());
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">{{ hd_date(now()) }}</p>
            <h2 class="mt-1 text-2xl font-bold text-ink">{{ __('app.dashboard.welcome', ['name' => $user->name]) }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.dashboard.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('app.tickets.create') }}" class="btn-primary btn-sm">
                <x-icon name="plus" class="h-4 w-4" /> {{ __('app.dashboard.new_ticket') }}
            </a>
            @can('view-reports')
                <a href="{{ route('app.reports.index') }}" class="btn-secondary btn-sm">
                    <x-icon name="chart" class="h-4 w-4" /> {{ __('app.dashboard.open_reports') }}
                </a>
            @endcan
        </div>
    </div>

    @php
        $cards = [
            ['key' => 'open_tickets', 'value' => $metrics['open'], 'icon' => 'ticket', 'tone' => 'brand', 'href' => route('app.tickets.index', ['status' => 'unresolved'])],
            ['key' => 'unassigned', 'value' => $metrics['unassigned'], 'icon' => 'inbox', 'tone' => 'amber', 'href' => route('app.tickets.index', ['assignee' => 'unassigned', 'status' => 'unresolved'])],
            ['key' => 'urgent', 'value' => $metrics['urgent'], 'icon' => 'alert', 'tone' => 'rose', 'href' => route('app.tickets.index', ['priority' => 'urgent', 'status' => 'unresolved'])],
            ['key' => 'breached', 'value' => $metrics['breached'], 'icon' => 'clock', 'tone' => 'rose', 'href' => route('app.tickets.index', ['sla' => 'breached'])],
            ['key' => 'my_queue', 'value' => $metrics['mine'], 'icon' => 'user', 'tone' => 'teal', 'href' => route('app.tickets.index', ['assignee' => 'me', 'status' => 'unresolved'])],
            ['key' => 'solved_today', 'value' => $metrics['solved_today'], 'icon' => 'check', 'tone' => 'teal', 'href' => route('app.reports.index')],
        ];
    @endphp

    {{-- Compact stat strip: six small tiles on desktop, two columns on phones. --}}
    <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($cards as $card)
            @php
                $tones = [
                    'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                    'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
                    'teal' => 'bg-teal-50 text-teal-700 ring-teal-100',
                ];
            @endphp
            <a href="{{ $card['href'] }}" class="card group flex items-center gap-2.5 p-3 transition hover:border-brand-300 hover:shadow-md">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$card['tone']] }} ring-1">
                    <x-icon :name="$card['icon']" class="h-4 w-4" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-bold leading-none text-slate-900">{{ $card['value'] }}</span>
                    <span class="mt-1 block text-[10px] font-medium leading-tight text-slate-500">{{ __('app.dashboard.'.$card['key']) }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr] xl:items-start">
        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">{{ __('app.dashboard.needs_attention') }}</h2>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ __('app.dashboard.attention_hint') }}</p>
                    </div>
                    <a href="{{ route('app.tickets.index', ['status' => 'unresolved']) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">{{ __('app.dashboard.view_all') }}</a>
                </div>

                @if ($attention->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-teal-50 text-teal-600">
                            <x-icon name="check" class="h-5 w-5" />
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-700">{{ __('app.dashboard.empty_attention') }}</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($attention as $ticket)
                            <li>
                                <a href="{{ route('app.tickets.show', $ticket) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50">
                                    <span class="hidden w-28 shrink-0 font-mono text-[11px] font-semibold text-slate-400 sm:block">{{ $ticket->reference }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-800">{{ $ticket->subject }}</span>
                                        <span class="mt-0.5 block text-[11px] text-slate-400">{{ $ticket->category?->name() }} · {{ $ticket->assignee?->name ?? __('common.unassigned') }}</span>
                                    </span>
                                    <x-priority-badge :priority="$ticket->priority" class="hidden sm:inline-flex" />
                                    <x-sla-badge :state="$ticket->slaState()" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('app.dashboard.recent_activity') }}</h2>
                    <a href="{{ route('app.tickets.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">{{ __('app.dashboard.view_all') }}</a>
                </div>

                @if ($recent->isEmpty())
                    <div class="px-5 py-12">
                        <x-empty-state icon="ticket" :title="__('app.dashboard.no_tickets')" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-3 text-start">{{ __('app.tickets.requester') }}</th>
                                    <th class="px-5 py-3 text-start">{{ __('fields.subject') }}</th>
                                    <th class="px-5 py-3 text-start">{{ __('fields.status') }}</th>
                                    <th class="px-5 py-3 text-end">{{ __('app.tickets.created') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($recent as $ticket)
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="whitespace-nowrap px-5 py-3">
                                            <div class="flex items-center gap-2">
                                                <x-avatar :name="$ticket->requester_name" size="sm" />
                                                <div class="min-w-0">
                                                    <p class="max-w-[10rem] truncate text-xs font-semibold text-slate-800">{{ $ticket->requester_name }}</p>
                                                    <p class="max-w-[10rem] truncate text-[10px] text-slate-400">{{ $ticket->reference }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <a href="{{ route('app.tickets.show', $ticket) }}" class="block max-w-xs truncate font-medium text-slate-700 hover:text-brand-700">{{ $ticket->subject }}</a>
                                        </td>
                                        <td class="px-5 py-3"><x-status-badge :status="$ticket->status" /></td>
                                        <td class="whitespace-nowrap px-5 py-3 text-end text-[11px] text-slate-400">{{ hd_relative($ticket->created_at) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="min-w-0 space-y-6">
            <section class="card p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('app.dashboard.csat_30d') }}</span>
                    <span class="flex items-center gap-0.5 text-amber-500">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-icon name="star" class="h-3.5 w-3.5 {{ $csatAverage !== null && $i <= round($csatAverage) ? 'fill-amber-400' : 'text-slate-200' }}" />
                        @endfor
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $csatAverage !== null ? $csatAverage : '—' }}<span class="text-sm font-medium text-slate-400"> / 5</span>
                </p>
                @if ($csatAverage === null)
                    <p class="text-[11px] text-slate-400">{{ __('app.dashboard.no_csat_yet') }}</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('app.dashboard.status_overview') }}</h2>
                <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                    @foreach (\App\Enums\TicketStatus::cases() as $status)
                        @php($count = (int) ($statusCounts[$status->value] ?? 0))
                        @if ($count > 0)
                            <span class="h-full {{ match ($status) {
                                \App\Enums\TicketStatus::Open => 'bg-sky-500',
                                \App\Enums\TicketStatus::Pending => 'bg-amber-400',
                                \App\Enums\TicketStatus::OnHold => 'bg-slate-400',
                                \App\Enums\TicketStatus::Solved => 'bg-teal-500',
                                \App\Enums\TicketStatus::Closed => 'bg-slate-600',
                            } }}" style="width: {{ round($count / $totalStatuses * 100, 2) }}%" title="{{ $status->label() }}: {{ $count }}"></span>
                        @endif
                    @endforeach
                </div>
                <ul class="mt-4 space-y-2.5">
                    @foreach (\App\Enums\TicketStatus::cases() as $status)
                        @php($count = (int) ($statusCounts[$status->value] ?? 0))
                        <li class="flex items-center justify-between gap-3 text-xs">
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
                            <span class="font-semibold text-slate-800">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('app.dashboard.your_queue') }}</h2>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ __('app.dashboard.personal_note', ['name' => $user->name]) }}</p>
                <div class="mt-4 flex items-center justify-between rounded-lg bg-brand-50 px-4 py-3">
                    <span class="text-xs font-semibold text-brand-800">{{ __('app.dashboard.my_queue') }}</span>
                    <span class="text-lg font-bold text-brand-800">{{ $metrics['mine'] }}</span>
                </div>
                <p class="mt-3 text-[11px] leading-5 text-slate-500">{{ __('app.dashboard.unassigned_note') }}</p>
                <a href="{{ route('app.tickets.index', ['assignee' => 'me']) }}" class="btn-secondary btn-sm mt-4 w-full">
                    {{ __('common.nav.tickets') }}
                </a>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('app.dashboard.quick_actions') }}</h2>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('app.tickets.create') }}" class="flex min-h-11 items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-700 lg:min-h-0">
                        <x-icon name="plus" class="h-4 w-4" /> {{ __('app.dashboard.new_ticket') }}
                    </a>
                    @can('manage-kb')
                        <a href="{{ route('app.kb.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-700 lg:min-h-0">
                            <x-icon name="book" class="h-4 w-4" /> {{ __('app.dashboard.browse_kb') }}
                        </a>
                    @endcan
                    <a href="{{ route('kb.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-700 lg:min-h-0">
                        <x-icon name="globe" class="h-4 w-4" /> {{ __('common.nav.knowledge_base') }}
                    </a>
                </div>
            </section>
        </aside>
    </div>
@endsection
