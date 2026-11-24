@extends('layouts.guest')

@section('title', __('auth.title'))

@section('content')
    <div class="card p-6 sm:p-8">
        <p class="eyebrow">{{ __('common.app_name') }}</p>
        <h2 class="mt-2 text-2xl font-bold text-ink">{{ __('auth.title') }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ __('auth.subtitle') }}</p>

        <div class="mt-6">
            <x-flash />
        </div>

        <div class="mt-6 flex flex-col gap-6">
            @if (config('helpdesk.demo'))
                {{-- Demo entry first on phones; the email form leads on desktop. --}}
                <div class="order-1 rounded-xl border border-brand-200 bg-brand-50 p-4 lg:order-2">
                    <p class="flex items-center gap-2 text-sm font-semibold text-brand-800">
                        <x-icon name="arrow-right" class="h-4 w-4 flip-icon" /> {{ __('auth.demo_title') }}
                    </p>
                    <p class="mt-1 text-xs leading-5 text-brand-700">{{ __('auth.demo_note') }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-2">
                        @foreach ([
                            ['role' => 'admin', 'label' => __('auth.demo_enter_admin'), 'icon' => 'shield'],
                            ['role' => 'supervisor', 'label' => __('auth.demo_enter_supervisor'), 'icon' => 'users'],
                            ['role' => 'agent', 'label' => __('auth.demo_enter_agent'), 'icon' => 'headset'],
                        ] as $demo)
                            <form method="POST" action="{{ route('demo.login') }}">
                                @csrf
                                <input type="hidden" name="role" value="{{ $demo['role'] }}">
                                <button type="submit" class="btn btn-secondary btn-sm w-full">
                                    <x-icon :name="$demo['icon']" class="h-4 w-4" />
                                    {{ $demo['label'] }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <p class="mt-3 flex items-start gap-1.5 text-[11px] leading-4 text-brand-700/80">
                        <x-icon name="refresh" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        {{ __('auth.demo_reset_note') }}
                    </p>
                </div>
            @endif

            <div class="order-2 lg:order-1">
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="label">{{ __('auth.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" inputmode="email" class="input @error('email') border-coral-400 @enderror"
                            placeholder="agent@example.com">
                        @error('email')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="passwordField">
                        <label for="password" class="label">{{ __('auth.password_label') }}</label>
                        <div class="relative">
                            <input id="password" :type="visible ? 'text' : 'password'" type="password" name="password" required
                                autocomplete="current-password" class="input pe-12 @error('password') border-coral-400 @enderror">
                            <button type="button" @click="toggle"
                                class="absolute inset-y-0 end-0 flex min-w-11 items-center justify-center px-3 text-slate-400 hover:text-slate-700"
                                :aria-label="visible ? @js(__('common.actions.close')) : @js(__('common.actions.view'))">
                                <x-icon name="eye" class="h-4 w-4" />
                            </button>
                        </div>
                        @error('password')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        {{ __('auth.remember') }}
                    </label>

                    <button type="submit" class="btn-primary w-full">
                        <x-icon name="lock" class="h-4 w-4" />
                        {{ __('auth.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-slate-500">
        <a href="{{ route('portal.submit') }}" class="link">{{ __('common.nav.submit_ticket') }}</a>
        ·
        <a href="{{ route('portal.track') }}" class="link">{{ __('common.nav.track_ticket') }}</a>
    </p>
    <div class="mt-3 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-[11px] text-slate-400">
        @if (config('helpdesk.demo'))
            <span>{{ __('common.demo_footer') }}</span>
            <span aria-hidden="true">·</span>
        @endif
        <a href="https://fedaamohamed.com" rel="noopener" class="hover:text-slate-600">{{ __('common.credit') }}</a>
    </div>
@endsection
