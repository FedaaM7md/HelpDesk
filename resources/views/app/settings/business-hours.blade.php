@extends('layouts.app')

@section('title', __('app.settings.hours_title'))

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.admin_group') }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.settings.hours_title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.settings.hours_subtitle') }}</p>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
        <span class="badge bg-brand-50 text-brand-700 ring-brand-100">
            <x-icon name="globe" class="h-3 w-3" /> {{ __('app.settings.timezone') }}: {{ __('common.timezone_label') }}
        </span>
        <span class="text-slate-400">{{ __('app.settings.note', ['timezone' => __('common.timezone_label')]) }}</span>
    </div>

    <form method="POST" action="{{ route('app.settings.business-hours.update') }}" class="card mt-5 overflow-hidden">
        @csrf
        @method('PUT')

        <div class="divide-y divide-slate-100">
            @foreach (range(0, 6) as $weekday)
                @php($day = $hours->firstWhere('weekday', $weekday))
                <div class="grid gap-3 px-4 py-4 sm:grid-cols-[180px_1fr_160px_160px] sm:items-center">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="enabled[{{ $weekday }}]" value="1" id="enabled-{{ $weekday }}"
                            @checked($day?->is_enabled)
                            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <label for="enabled-{{ $weekday }}" class="text-sm font-semibold text-slate-800">{{ __('common.weekdays.'.$weekday) }}</label>
                    </div>

                    <p class="hidden text-[11px] text-slate-400 sm:block">{{ $day?->windowLabel() }}</p>

                    <div>
                        <label for="opens-{{ $weekday }}" class="label text-[10px] sm:sr-only">{{ __('app.settings.opens') }}</label>
                        <input id="opens-{{ $weekday }}" type="time" name="days[{{ $weekday }}][opens_at]"
                            value="{{ old('days.'.$weekday.'.opens_at', $day?->opens_at ? substr($day->opens_at, 0, 5) : '09:00') }}"
                            class="input py-1.5 text-xs">
                    </div>
                    <div>
                        <label for="closes-{{ $weekday }}" class="label text-[10px] sm:sr-only">{{ __('app.settings.closes') }}</label>
                        <input id="closes-{{ $weekday }}" type="time" name="days[{{ $weekday }}][closes_at]"
                            value="{{ old('days.'.$weekday.'.closes_at', $day?->closes_at ? substr($day->closes_at, 0, 5) : '17:00') }}"
                            class="input py-1.5 text-xs">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-4">
            <p class="text-[11px] text-slate-400">{{ __('app.settings.note', ['timezone' => __('common.timezone_label')]) }}</p>
            <button type="submit" class="btn-primary btn-sm">
                <x-icon name="check" class="h-4 w-4" /> {{ __('app.settings.save') }}
            </button>
        </div>
    </form>
@endsection
