@extends('layouts.public')

@section('title', __('portal.csat.title'))

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:py-20">
        <div class="text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 ring-1 ring-amber-100">
                <x-icon name="star" class="h-7 w-7" />
            </span>
            <h1 class="mt-5 text-3xl font-bold text-slate-900">{{ __('portal.csat.title') }}</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-7 text-slate-500">
                {{ __('portal.csat.subtitle', ['reference' => $ticket->reference]) }}
            </p>
        </div>

        <div class="card mt-8 p-6 sm:p-8">
            <x-flash />

            @if ($ticket->csat)
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-900">{{ __('portal.csat.rated_title') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('portal.csat.rated_text', ['reference' => $ticket->reference]) }}</p>
                    <div class="mt-5 flex items-center justify-center gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-icon name="star" class="h-8 w-8 {{ $i <= $ticket->csat->score ? 'fill-amber-400 text-amber-400' : 'text-slate-300' }}" />
                        @endfor
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-600">{{ __('portal.csat.your_rating') }}: {{ $ticket->csat->score }} / 5 — {{ __('portal.csat.labels.'.$ticket->csat->score) }}</p>
                    @if ($ticket->csat->comment)
                        <p class="mx-auto mt-4 max-w-md rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">{{ $ticket->csat->comment }}</p>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ URL::temporarySignedRoute('portal.csat.store', now()->addDays((int) config('helpdesk.csat_link_days', 30)), ['ticket' => $ticket->reference]) }}"
                    class="space-y-7">
                    @csrf
                    <fieldset>
                        <legend class="text-sm font-bold text-slate-900">{{ __('portal.csat.question') }}</legend>
                        <div class="mt-4 grid grid-cols-5 gap-2 sm:gap-3">
                            @for ($score = 1; $score <= 5; $score++)
                                <label class="group cursor-pointer">
                                    <input type="radio" name="score" value="{{ $score }}" class="peer sr-only"
                                        @checked((int) old('score') === $score) required>
                                    <span class="flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-1 py-4 text-center transition hover:border-amber-300 hover:bg-amber-50/50 peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:ring-2 peer-checked:ring-amber-300/40 peer-focus-visible:ring-2 peer-focus-visible:ring-teal-500">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600 group-hover:bg-amber-100 group-hover:text-amber-700">
                                            {{ $score }}
                                        </span>
                                        <span class="text-[10px] font-semibold leading-4 text-slate-500">{{ __('portal.csat.labels.'.$score) }}</span>
                                    </span>
                                </label>
                            @endfor
                        </div>
                        @error('score')<p class="error-text">{{ $message }}</p>@enderror
                    </fieldset>

                    <div>
                        <label for="comment" class="label">{{ __('portal.csat.comment_label') }}</label>
                        <textarea id="comment" name="comment" rows="4" maxlength="2000" class="textarea"
                            placeholder="{{ __('portal.csat.comment_placeholder') }}">{{ old('comment') }}</textarea>
                        @error('comment')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        <x-icon name="send" class="h-4 w-4 flip-icon" />
                        {{ __('portal.csat.submit') }}
                    </button>
                </form>
            @endif

            <div class="mt-6 border-t border-slate-200 pt-5 text-center">
                <a href="{{ URL::temporarySignedRoute('portal.ticket.show', now()->addDays((int) config('helpdesk.tracking_link_days', 90)), ['ticket' => $ticket->reference]) }}"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-teal-700">
                    <x-icon name="arrow-left" class="h-4 w-4 flip-icon" />
                    {{ __('portal.csat.back') }}
                </a>
            </div>
        </div>

        <p class="mt-6 flex items-center justify-center gap-2 text-[11px] text-slate-400">
            <x-icon name="lock" class="h-3.5 w-3.5" />
            {{ __('portal.ticket.signed_note') }}
        </p>
    </div>
@endsection
