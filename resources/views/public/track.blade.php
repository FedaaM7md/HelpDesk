@extends('layouts.public')

@section('title', __('portal.track.title'))

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-20">
        <div class="text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                <x-icon name="search" class="h-6 w-6" />
            </span>
            <h1 class="mt-5 text-3xl font-bold text-slate-900">{{ __('portal.track.title') }}</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-500">{{ __('portal.track.subtitle') }}</p>
        </div>

        <div class="card mx-auto mt-8 max-w-xl p-6 sm:p-8">
            <x-flash />

            <form method="POST" action="{{ route('portal.track.lookup') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="reference" class="label">{{ __('fields.reference') }}</label>
                    <input id="reference" name="reference" type="text" required value="{{ old('reference') }}"
                        placeholder="{{ __('portal.track.reference_placeholder') }}" dir="ltr"
                        class="input text-start font-mono uppercase @error('reference') border-rose-400 @enderror">
                    <p class="hint">{{ __('portal.track.reference_hint') }}</p>
                    @error('reference')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="label">{{ __('fields.requester_email') }}</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email"
                        placeholder="{{ __('portal.track.email_placeholder') }}" dir="ltr"
                        class="input text-start @error('email') border-rose-400 @enderror">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full">
                    <x-icon name="search" class="h-4 w-4" />
                    {{ __('portal.track.lookup') }}
                </button>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-5">
                <p class="text-xs leading-6 text-slate-500">{{ __('portal.track.help') }}</p>
                <div class="mt-3 flex flex-wrap gap-3 text-xs">
                    <a href="{{ route('portal.submit') }}" class="link">{{ __('common.nav.submit_ticket') }}</a>
                    <a href="{{ route('kb.index') }}" class="link">{{ __('common.nav.knowledge_base') }}</a>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-6 flex max-w-xl items-start gap-3 rounded-2xl border border-slate-200 bg-white/70 p-4 text-xs leading-6 text-slate-500">
            <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
            <p>{{ __('portal.track.private_note') }}</p>
        </div>
    </div>
@endsection
