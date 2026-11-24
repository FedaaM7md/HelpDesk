@extends('layouts.app')

@section('title', __('app.sla.title'))

@section('content')
    @php
        $fallbackText = collect(\App\Services\SlaService::FALLBACK)
            ->map(fn ($minutes, $priority) => \App\Enums\TicketPriority::from($priority)->label().': '.\App\Models\SlaPolicy::minutesLabel($minutes[0]).' / '.\App\Models\SlaPolicy::minutesLabel($minutes[1]))
            ->implode(' · ');
    @endphp

    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.admin_group') }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.sla.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.sla.subtitle') }}</p>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <div class="card-muted p-4 text-xs leading-6 text-slate-600 lg:col-span-2">
            <p class="flex items-start gap-2">
                <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                {{ __('app.sla.intro') }}
            </p>
            <p class="mt-2">{{ __('app.sla.timezone_note', ['timezone' => __('common.timezone_label')]) }}</p>
            <p class="mt-2 text-slate-500">{{ __('app.sla.fallback_note', ['fallbacks' => $fallbackText]) }}</p>
        </div>
        <div class="rounded-2xl border border-brand-100 bg-brand-50 p-4 text-xs leading-6 text-brand-800">
            {!! __('app.sla.command_note') !!}
        </div>
    </div>

    <form method="POST" action="{{ route('app.sla.update') }}" class="card mt-5 overflow-hidden">
        @csrf
        @method('PUT')
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('app.sla.priority') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('app.sla.first_response') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('app.sla.resolution') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('app.sla.use_business_hours') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('app.sla.active') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($priorities as $priority)
                        @php($policy = $policies->get($priority->value))
                        <tr>
                            <td class="whitespace-nowrap px-4 py-4">
                                <x-priority-badge :priority="$priority" />
                            </td>
                            <td class="px-4 py-4">
                                <input type="number" name="policies[{{ $priority->value }}][first_response_minutes]"
                                    value="{{ old('policies.'.$priority->value.'.first_response_minutes', $policy?->first_response_minutes ?? $fallback[$priority->value][0]) }}"
                                    min="5" max="525600" required class="input w-32 py-1.5 text-xs"
                                    aria-label="{{ $priority->label() }} — {{ __('app.sla.first_response') }}">
                                <span class="ms-1 text-[10px] text-slate-400">{{ __('app.sla.minutes') }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <input type="number" name="policies[{{ $priority->value }}][resolution_minutes]"
                                    value="{{ old('policies.'.$priority->value.'.resolution_minutes', $policy?->resolution_minutes ?? $fallback[$priority->value][1]) }}"
                                    min="5" max="525600" required class="input w-32 py-1.5 text-xs"
                                    aria-label="{{ $priority->label() }} — {{ __('app.sla.resolution') }}">
                                <span class="ms-1 text-[10px] text-slate-400">{{ __('app.sla.minutes') }}</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" name="policies[{{ $priority->value }}][use_business_hours]" value="1"
                                    @checked(old('policies.'.$priority->value.'.use_business_hours', $policy?->use_business_hours ?? true))
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    aria-label="{{ $priority->label() }} — {{ __('app.sla.use_business_hours') }}">
                            </td>
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" name="policies[{{ $priority->value }}][is_active]" value="1"
                                    @checked(old('policies.'.$priority->value.'.is_active', $policy?->is_active ?? true))
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    aria-label="{{ $priority->label() }} — {{ __('app.sla.active') }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-4">
            <p class="text-[11px] text-slate-400">{{ __('app.sla.minutes_hint') }}</p>
            <button type="submit" class="btn-primary btn-sm">
                <x-icon name="check" class="h-4 w-4" /> {{ __('app.sla.save') }}
            </button>
        </div>
    </form>
@endsection
