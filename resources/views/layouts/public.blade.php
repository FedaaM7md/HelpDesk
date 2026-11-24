{{-- HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary; not open source. --}}
@php
    $locale = app()->getLocale();
    $company = config('helpdesk.company.'.($locale === 'ar' ? 'name_ar' : 'name_en'));
    $pageTitle = trim($__env->yieldContent('title')) ?: __('common.app_name');
    $metaDescription = $company.' — '.__('common.tagline');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="color-scheme" content="light">
    <title>{{ $pageTitle }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('common.app_name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta property="og:image" content="{{ asset('og-image.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper" x-data="publicNav">
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-[60] focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        {{ __('common.aria.main_content') }}
    </a>

    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-paper/95 backdrop-blur print:hidden">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ __('common.app_name') }}">
                <span class="hidden sm:block"><x-logo compact /></span>
                <span class="sm:hidden">
                    <svg viewBox="0 0 40 40" class="h-9 w-9" aria-hidden="true">
                        <path d="M5 9h13a8 8 0 0 1 8 8v8a8 8 0 0 1-8 8h-3.5l-3.2 6.2v-6.2H9a8 8 0 0 1-8-8v-8a8 8 0 0 1 8-8Z" fill="#0F5C55" />
                        <path d="M15 10h13a7 7 0 0 1 7 7v7a7 7 0 0 1-7 7h-2.4l-2.6 5.4v-5.4h-2a7 7 0 0 1-7-7v-7a7 7 0 0 1 7-7Z" fill="#F26B5B" />
                        <circle cx="12.2" cy="18" r="1.35" fill="#FFFFFF" />
                        <circle cx="16.6" cy="18" r="1.35" fill="#FFFFFF" />
                        <circle cx="12.2" cy="23" r="1.35" fill="#FFFFFF" />
                    </svg>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('common.aria.primary_navigation') }}">
                <a href="{{ route('kb.index') }}"
                    class="nav-link {{ request()->routeIs('kb.*') ? 'nav-link-active' : '' }}">{{ __('common.nav.knowledge_base') }}</a>
                <a href="{{ route('portal.submit') }}"
                    class="nav-link {{ request()->routeIs('portal.submit') ? 'nav-link-active' : '' }}">{{ __('common.nav.submit_ticket') }}</a>
                <a href="{{ route('portal.track') }}"
                    class="nav-link {{ request()->routeIs('portal.track') ? 'nav-link-active' : '' }}">{{ __('common.nav.track_ticket') }}</a>
            </nav>

            <div class="ms-auto flex items-center gap-2">
                <x-language-switch />
                @auth
                    <a href="{{ route('app.dashboard') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">
                        <x-icon name="headset" class="h-4 w-4" />
                        {{ __('common.nav.workspace') }}
                    </a>
                @else
                    @if (config('helpdesk.demo'))
                        <a href="{{ route('login') }}" class="btn btn-coral btn-sm hidden md:inline-flex">
                            <x-icon name="arrow-right" class="h-4 w-4 flip-icon" />
                            {{ __('common.nav.try_demo') }}
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">
                        <x-icon name="lock" class="h-4 w-4" />
                        <span class="hidden sm:inline">{{ __('common.nav.agent_login') }}</span>
                        <span class="sm:hidden">{{ __('common.actions.login') }}</span>
                    </a>
                @endauth
                <button type="button" @click="toggleMobileNav"
                    class="btn btn-ghost btn-sm lg:hidden" :aria-expanded="mobileNav"
                    aria-controls="mobile-menu" :aria-label="mobileNav ? @js(__('common.actions.close_menu')) : @js(__('common.actions.open_menu'))">
                    <x-icon name="menu" class="h-5 w-5" x-show="!mobileNav" />
                    <x-icon name="x" class="h-5 w-5" x-show="mobileNav" x-cloak />
                </button>
            </div>
        </div>

        <div id="mobile-menu" x-show="mobileNav" x-cloak x-transition.opacity
            class="border-t border-slate-200 bg-paper px-4 py-3 lg:hidden">
            <nav class="flex flex-col gap-1" aria-label="{{ __('common.aria.primary_navigation') }}">
                <a href="{{ route('kb.index') }}" class="nav-link">{{ __('common.nav.knowledge_base') }}</a>
                <a href="{{ route('portal.submit') }}" class="nav-link">{{ __('common.nav.submit_ticket') }}</a>
                <a href="{{ route('portal.track') }}" class="nav-link">{{ __('common.nav.track_ticket') }}</a>
                <a href="{{ route('pages.about') }}" class="nav-link">{{ __('common.nav.about') }}</a>
                <a href="{{ route('pages.privacy') }}" class="nav-link">{{ __('common.nav.privacy') }}</a>
                <a href="{{ route('pages.terms') }}" class="nav-link">{{ __('common.nav.terms') }}</a>
                @auth
                    <a href="{{ route('app.dashboard') }}" class="nav-link">{{ __('common.nav.workspace') }}</a>
                @elseif (config('helpdesk.demo'))
                    <a href="{{ route('login') }}" class="nav-link text-coral-700">{{ __('common.nav.try_demo') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="mt-20 border-t border-brand-900 bg-brand-950 text-brand-100 print:hidden">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <x-logo on-dark />
                <p class="mt-3 text-sm font-semibold text-brand-100">{{ $company }}</p>
                <p class="mt-2 max-w-sm text-sm leading-7 text-brand-100/70">{{ __('common.tagline') }}</p>
                <p class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-[11px] font-medium text-brand-100 ring-1 ring-white/10">
                    <x-icon name="shield" class="h-3.5 w-3.5" />
                    {{ __('common.privacy_note') }}
                </p>
            </div>
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-200/60">{{ __('common.footer_links') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('kb.index') }}" class="hover:text-white">{{ __('common.nav.knowledge_base') }}</a></li>
                    <li><a href="{{ route('portal.submit') }}" class="hover:text-white">{{ __('common.nav.submit_ticket') }}</a></li>
                    <li><a href="{{ route('portal.track') }}" class="hover:text-white">{{ __('common.nav.track_ticket') }}</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">{{ __('common.nav.agent_login') }}</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-200/60">{{ __('common.nav.about') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-white">{{ __('common.nav.about') }}</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white">{{ __('common.nav.privacy') }}</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-white">{{ __('common.nav.terms') }}</a></li>
                    <li class="flex items-center gap-2 text-brand-100/70"><x-icon name="mail" class="h-4 w-4 text-brand-300" /><span dir="ltr">{{ config('helpdesk.company.email') }}</span></li>
                    <li class="flex items-center gap-2 text-brand-100/70"><x-icon name="headset" class="h-4 w-4 text-brand-300" /><span dir="ltr">{{ config('helpdesk.company.phone') }}</span></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-brand-200/70 sm:flex-row sm:px-6 lg:px-8">
                <p>{{ __('common.copyright', ['year' => date('Y')]) }}</p>
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                    @if (config('helpdesk.demo'))
                        <span class="text-brand-200/50">{{ __('common.demo_footer') }}</span>
                    @endif
                    <a href="https://fedaamohamed.com" rel="noopener" class="hover:text-white">{{ __('common.credit') }}</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
