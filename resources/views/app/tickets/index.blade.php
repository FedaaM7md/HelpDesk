@extends('layouts.app')

@section('title', __('app.tickets.title'))

@section('content')
    @php($currentStatus = $filters['status'] ?? '')
    <div x-data="filterSheet">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.workspace_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.tickets.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.tickets.subtitle') }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="button" @click="openSheet" class="btn-secondary btn-sm md:hidden" aria-controls="ticket-filters-sheet">
                <x-icon name="filter" class="h-4 w-4" /> {{ __('common.actions.filter') }}
            </button>
            <a href="{{ route('app.tickets.create') }}" class="btn-primary btn-sm">
                <x-icon name="plus" class="h-4 w-4" /> {{ __('app.tickets.new') }}
            </a>
        </div>
    </div>

    <div class="mt-6 flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        <a href="{{ route('app.tickets.index', array_filter(array_merge($filters, ['status' => null]))) }}"
            class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold {{ $currentStatus === '' ? 'bg-brand-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ __('app.tickets.all_tab') }}
            <span class="ms-1 opacity-70">{{ $statusCounts->sum() }}</span>
        </a>
        <a href="{{ route('app.tickets.index', array_filter(array_merge($filters, ['status' => 'unresolved']))) }}"
            class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold {{ $currentStatus === 'unresolved' ? 'bg-brand-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ __('app.tickets.unresolved_tab') }}
            <span class="ms-1 opacity-70">{{ $unresolvedTotal }}</span>
        </a>
        @foreach (\App\Enums\TicketStatus::cases() as $status)
            <a href="{{ route('app.tickets.index', array_filter(array_merge($filters, ['status' => $status->value]))) }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold {{ $currentStatus === $status->value ? 'bg-brand-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $status->label() }}
                <span class="ms-1 opacity-70">{{ (int) ($statusCounts[$status->value] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('app.tickets.index') }}" class="card mt-4 hidden p-4 md:block">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <label for="q" class="sr-only">{{ __('common.actions.search') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </span>
                    <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input ps-9 py-2 text-xs"
                        placeholder="{{ __('app.tickets.search_placeholder') }}">
                </div>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-status" class="sr-only">{{ __('fields.status') }}</label>
                <select id="filter-status" name="status" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_statuses') }}</option>
                    <option value="unresolved" @selected($currentStatus === 'unresolved')>{{ __('app.tickets.unresolved_tab') }}</option>
                    @foreach (\App\Enums\TicketStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-priority" class="sr-only">{{ __('fields.priority') }}</label>
                <select id="filter-priority" name="priority" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_priorities') }}</option>
                    @foreach (\App\Enums\TicketPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-category" class="sr-only">{{ __('fields.category') }}</label>
                <select id="filter-category" name="category" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-assignee" class="sr-only">{{ __('fields.assignee') }}</label>
                <select id="filter-assignee" name="assignee" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_agents') }}</option>
                    <option value="me" @selected(($filters['assignee'] ?? '') === 'me')>{{ __('common.me') }}</option>
                    <option value="unassigned" @selected(($filters['assignee'] ?? '') === 'unassigned')>{{ __('common.unassigned') }}</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((string) ($filters['assignee'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-tag" class="sr-only">{{ __('fields.tags') }}</label>
                <select id="filter-tag" name="tag" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_tags') }}</option>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}" @selected((string) ($filters['tag'] ?? '') === (string) $tag->id)>{{ $tag->name() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-sla" class="sr-only">{{ __('app.tickets.sla') }}</label>
                <select id="filter-sla" name="sla" class="select py-2 text-xs">
                    <option value="">{{ __('app.tickets.all_sla') }}</option>
                    <option value="breached" @selected(($filters['sla'] ?? '') === 'breached')>{{ __('app.tickets.sla_breached') }}</option>
                </select>
            </div>

            <div class="xl:col-span-2">
                <label for="filter-sort" class="sr-only">{{ __('fields.sort') }}</label>
                <select id="filter-sort" name="sort" class="select py-2 text-xs">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('app.tickets.sort_newest') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('app.tickets.sort_oldest') }}</option>
                    <option value="updated" @selected(($filters['sort'] ?? '') === 'updated')>{{ __('app.tickets.sort_updated') }}</option>
                    <option value="priority" @selected(($filters['sort'] ?? '') === 'priority')>{{ __('app.tickets.sort_priority') }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2 xl:col-span-2">
                <button type="submit" class="btn-primary btn-sm flex-1">
                    <x-icon name="filter" class="h-4 w-4" /> {{ __('common.actions.apply') }}
                </button>
                <a href="{{ route('app.tickets.index') }}" class="btn-secondary btn-sm">{{ __('common.actions.reset') }}</a>
            </div>
        </div>
    </form>

    <div class="mt-4">
        @if ($tickets->isEmpty())
            <x-empty-state icon="ticket" :title="__('app.tickets.empty_title')" :description="__('app.tickets.empty_desc')">
                <a href="{{ route('app.tickets.index') }}" class="btn-secondary btn-sm">{{ __('app.tickets.clear_filters') }}</a>
            </x-empty-state>
        @else
            <div class="table-wrap hidden md:block">
                <table class="min-w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('fields.reference') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('fields.subject') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('app.tickets.requester') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('fields.priority') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('fields.status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('app.tickets.assignee') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('app.tickets.sla') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('app.tickets.updated') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tickets as $ticket)
                            <tr class="group hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('app.tickets.show', $ticket) }}" class="font-mono text-[11px] font-bold text-brand-700 hover:text-teal-700">{{ $ticket->reference }}</a>
                                </td>
                                <td class="max-w-sm px-4 py-3">
                                    <a href="{{ route('app.tickets.show', $ticket) }}" class="block truncate font-semibold text-slate-800 group-hover:text-brand-700">{{ $ticket->subject }}</a>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] text-slate-400">{{ $ticket->category?->name() }}</span>
                                        @foreach ($ticket->tags->take(2) as $tag)
                                            <span class="badge {{ $tag->badgeClasses() }} !px-1.5 !py-0 !text-[9px]">{{ $tag->name() }}</span>
                                        @endforeach
                                        @if ($ticket->tags->count() > 2)
                                            <span class="text-[9px] text-slate-400">+{{ $ticket->tags->count() - 2 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-[11rem] truncate text-xs font-medium text-slate-700">{{ $ticket->requester_name }}</p>
                                    <p class="max-w-[11rem] truncate text-[10px] text-slate-400">{{ $ticket->requester_email }}</p>
                                </td>
                                <td class="px-4 py-3"><x-priority-badge :priority="$ticket->priority" /></td>
                                <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($ticket->assignee)
                                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                            <x-avatar :user="$ticket->assignee" size="sm" class="!h-6 !w-6 !text-[9px]" />
                                            {{ $ticket->assignee->name }}
                                        </span>
                                    @else
                                        <span class="text-[11px] font-medium text-amber-600">{{ __('common.unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-sla-badge :state="$ticket->slaState()" /></td>
                                <td class="whitespace-nowrap px-4 py-3 text-end text-[11px] text-slate-400">{{ hd_relative($ticket->updated_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <x-pagination :paginator="$tickets" />
            </div>

            <div class="space-y-3 md:hidden">
                @foreach ($tickets as $ticket)
                    <a href="{{ route('app.tickets.show', $ticket) }}" class="card block p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[10px] font-bold text-brand-700">{{ $ticket->reference }}</span>
                            <x-sla-badge :state="$ticket->slaState()" />
                        </div>
                        <p class="mt-2 text-sm font-bold text-slate-900">{{ $ticket->subject }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ $ticket->requester_name }} · {{ $ticket->category?->name() }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$ticket->status" />
                            <x-priority-badge :priority="$ticket->priority" />
                            <span class="ms-auto text-[10px] text-slate-400">{{ $ticket->assignee?->name ?? __('common.unassigned') }}</span>
                        </div>
                    </a>
                @endforeach
                <x-pagination :paginator="$tickets" />
            </div>
        @endif
    </div>

    {{-- Mobile filters bottom sheet --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true"
        aria-label="{{ __('common.actions.filter') }}" @keydown.escape.window="closeSheet">
        <div class="absolute inset-0 bg-slate-950/40" @click="closeSheet"></div>
        <div id="ticket-filters-sheet" class="absolute inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto rounded-t-2xl bg-white p-4 shadow-xl">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-ink">{{ __('common.actions.filter') }}</h2>
                <button type="button" @click="closeSheet" class="flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="{{ __('common.actions.close') }}">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <form method="GET" action="{{ route('app.tickets.index') }}">
                @include('app.tickets._filters', ['prefix' => 'm'])
            </form>
        </div>
    </div>
</div>
@endsection
