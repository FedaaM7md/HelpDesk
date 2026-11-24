@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>@yield('title', __('auth.title')) — {{ __('common.app_name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-brand-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="relative">
                <x-logo on-dark />
            </div>

            <div class="relative max-w-md">
                <div class="mb-8 space-y-3" aria-hidden="true">
                    <div class="ms-0 max-w-[82%] rounded-2xl rounded-ss-sm bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="h-2 w-40 rounded-full bg-white/40"></div>
                        <div class="mt-2 h-2 w-28 rounded-full bg-white/20"></div>
                    </div>
                    <div class="ms-auto max-w-[78%] rounded-2xl rounded-se-sm bg-coral-500 px-4 py-3 shadow-sm">
                        <div class="h-2 w-36 rounded-full bg-white/80"></div>
                        <div class="mt-2 h-2 w-24 rounded-full bg-white/50"></div>
                    </div>
                </div>
                <h1 class="text-3xl font-bold leading-tight">{{ __('auth.panel_title') }}</h1>
                <p class="mt-4 text-sm leading-7 text-brand-100/80">{{ __('auth.panel_text') }}</p>
                <ul class="mt-8 space-y-3 text-sm text-brand-100/90">
                    @foreach ([__('auth.panel_feature_1'), __('auth.panel_feature_2'), __('auth.panel_feature_3')] as $feature)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/15 text-brand-100">
                                <x-icon name="check" class="h-3 w-3" />
                            </span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative flex items-center gap-2 text-xs text-brand-200/70">
                <x-icon name="shield" class="h-4 w-4 text-brand-300" />
                {{ __('auth.secure_note') }}
            </p>
        </section>

        <section class="flex flex-col justify-center px-4 py-12 sm:px-8 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 flex items-center justify-between">
                    <a href="{{ route('home') }}" class="lg:hidden"><x-logo /></a>
                    <a href="{{ route('home') }}" class="hidden items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 lg:inline-flex">
                        <x-icon name="arrow-left" class="h-4 w-4 flip-icon" /> {{ __('auth.back_home') }}
                    </a>
                    <x-language-switch />
                </div>

                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
