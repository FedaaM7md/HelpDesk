{{-- HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary; not open source. --}}
@php($user = auth()->user())
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>@yield('title', __('common.nav.workspace')) — {{ __('common.app_name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-50/40" x-data="workspaceShell">
    <a href="#workspace-content"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-[60] focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        {{ __('common.aria.main_content') }}
    </a>

    <div x-show="sidebarOpen" x-cloak @click="closeSidebar"
        class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" x-transition.opacity></div>

    <aside class="app-sidebar fixed inset-y-0 start-0 z-50 flex w-72 flex-col bg-brand-950 text-brand-100 shadow-xl transition duration-200 print:hidden"
        :class="{ 'is-open': sidebarOpen }" aria-label="{{ __('common.aria.sidebar') }}">
        <div class="flex h-16 shrink-0 items-center justify-between px-5">
            <a href="{{ route('app.dashboard') }}" class="inline-flex items-center gap-2.5">
                <x-logo compact on-dark />
            </a>
            <button type="button" @click="closeSidebar" class="flex min-h-11 min-w-11 items-center justify-center rounded-lg text-brand-200 hover:bg-white/10 lg:hidden"
                aria-label="{{ __('common.actions.close_menu') }}">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="mx-5 mb-2 rounded-lg bg-white/5 px-3 py-2 ring-1 ring-white/5">
            <p class="text-[11px] font-semibold text-brand-100">{{ config('helpdesk.company.'.(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')) }}</p>
            <p class="mt-0.5 text-[10px] leading-4 text-brand-100/60">{{ __('common.tagline') }}</p>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            <div>
                <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200/50">{{ __('common.nav.workspace_group') }}</p>
                <div class="space-y-1">
                    <a href="{{ route('app.dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('app.dashboard') ? 'sidebar-link-active' : '' }}">
                        <x-icon name="home" class="h-5 w-5" />
                        {{ __('common.nav.dashboard') }}
                    </a>
                    <a href="{{ route('app.tickets.index') }}"
                        class="sidebar-link {{ request()->routeIs('app.tickets.*') ? 'sidebar-link-active' : '' }}">
                        <x-icon name="ticket" class="h-5 w-5" />
                        <span class="flex-1">{{ __('common.nav.tickets') }}</span>
                        @if (($navCounts['unresolved'] ?? 0) > 0)
                            <span class="rounded-full bg-brand-400/25 px-2 py-0.5 text-[10px] font-bold text-brand-100">{{ $navCounts['unresolved'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('app.canned.index') }}"
                        class="sidebar-link {{ request()->routeIs('app.canned.*') ? 'sidebar-link-active' : '' }}">
                        <x-icon name="chat" class="h-5 w-5" />
                        {{ __('common.nav.canned_replies') }}
                    </a>
                </div>
            </div>

            @can('manage-kb')
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200/50">{{ __('common.nav.knowledge_group') }}</p>
                    <div class="space-y-1">
                        <a href="{{ route('app.kb.index') }}"
                            class="sidebar-link {{ request()->routeIs('app.kb.*') ? 'sidebar-link-active' : '' }}">
                            <x-icon name="book" class="h-5 w-5" />
                            {{ __('common.nav.kb_management') }}
                        </a>
                    </div>
                </div>
            @endcan

            @can('view-reports')
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200/50">{{ __('common.nav.insights_group') }}</p>
                    <div class="space-y-1">
                        <a href="{{ route('app.reports.index') }}"
                            class="sidebar-link {{ request()->routeIs('app.reports.*') ? 'sidebar-link-active' : '' }}">
                            <x-icon name="chart" class="h-5 w-5" />
                            {{ __('common.nav.reports') }}
                        </a>
                    </div>
                </div>
            @endcan

            @if (auth()->user()->can('manage-taxonomy') || auth()->user()->can('manage-settings') || auth()->user()->can('manage-users'))
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200/50">{{ __('common.nav.admin_group') }}</p>
                    <div class="space-y-1">
                        @can('manage-taxonomy')
                            <a href="{{ route('app.categories.index') }}"
                                class="sidebar-link {{ request()->routeIs('app.categories.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="folder" class="h-5 w-5" />
                                {{ __('common.nav.categories') }}
                            </a>
                            <a href="{{ route('app.tags.index') }}"
                                class="sidebar-link {{ request()->routeIs('app.tags.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="tag" class="h-5 w-5" />
                                {{ __('common.nav.tags') }}
                            </a>
                        @endcan
                        @can('manage-settings')
                            <a href="{{ route('app.sla.index') }}"
                                class="sidebar-link {{ request()->routeIs('app.sla.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="clock" class="h-5 w-5" />
                                {{ __('common.nav.sla') }}
                            </a>
                            <a href="{{ route('app.settings.business-hours') }}"
                                class="sidebar-link {{ request()->routeIs('app.settings.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="settings" class="h-5 w-5" />
                                {{ __('common.nav.business_hours') }}
                            </a>
                        @endcan
                        @can('manage-users')
                            <a href="{{ route('app.users.index') }}"
                                class="sidebar-link {{ request()->routeIs('app.users.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="users" class="h-5 w-5" />
                                {{ __('common.nav.users') }}
                            </a>
                        @endcan
                        @can('view-audit')
                            <a href="{{ route('app.audit.index') }}"
                                class="sidebar-link {{ request()->routeIs('app.audit.*') ? 'sidebar-link-active' : '' }}">
                                <x-icon name="shield" class="h-5 w-5" />
                                {{ __('common.nav.audit') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endif
        </nav>

        <div class="shrink-0 border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <x-avatar :user="$user" size="sm" class="!bg-white/10 !text-white !ring-white/10" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                    <p class="truncate text-[11px] text-brand-200/60">{{ $user->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-brand-200/70 hover:bg-white/10 hover:text-white"
                        aria-label="{{ __('common.actions.logout') }}" title="{{ __('common.actions.logout') }}">
                        <x-icon name="logout" class="h-5 w-5 flip-icon" />
                    </button>
                </form>
            </div>
            <a href="https://fedaamohamed.com" rel="noopener" class="mt-3 block text-center text-[10px] text-brand-200/50 hover:text-brand-100">{{ __('common.credit') }}</a>
        </div>
    </aside>

    <div class="min-h-screen lg:ps-72 print:ps-0">
        <x-demo-banner />
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur print:hidden">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" @click="openSidebar" class="btn btn-ghost btn-sm lg:hidden"
                    aria-label="{{ __('common.actions.open_menu') }}">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-base font-semibold text-ink sm:text-lg">@yield('title', __('common.nav.dashboard'))</h1>
                </div>

                <form method="GET" action="{{ route('app.tickets.index') }}" class="hidden md:block" role="search">
                    <label for="global-search" class="sr-only">{{ __('app.search_global') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400">
                            <x-icon name="search" class="h-4 w-4" />
                        </span>
                        <input id="global-search" type="search" name="q" value="{{ request()->routeIs('app.tickets.index') ? request('q') : '' }}"
                            placeholder="{{ __('app.search_global_placeholder') }}"
                            data-search-input
                            class="input w-64 ps-9 py-2 text-xs lg:w-72">
                    </div>
                </form>

                <x-language-switch />

                <div class="relative" x-data="userMenu" @keydown.escape.window="close">
                    <button type="button" @click="toggle" :aria-expanded="open" :aria-label="'{{ __('common.aria.user_menu') }}'"
                        class="flex min-h-11 items-center gap-2 rounded-lg p-1.5 text-start hover:bg-slate-100">
                        <x-avatar :user="$user" size="sm" />
                        <span class="hidden min-w-0 sm:block">
                            <span class="block max-w-[10rem] truncate text-xs font-semibold text-slate-800">{{ $user->name }}</span>
                            <span class="block text-[10px] text-slate-500">{{ $user->role->label() }}</span>
                        </span>
                        <x-icon name="arrow-right" class="hidden h-3.5 w-3.5 rotate-90 text-slate-400 sm:block" />
                    </button>

                    <div x-show="open" x-cloak x-transition.opacity @click.outside="close"
                        class="absolute end-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('app.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                            <x-icon name="home" class="h-4 w-4 text-slate-400" /> {{ __('common.nav.dashboard') }}
                        </a>
                        <a href="{{ route('app.tickets.index', ['assignee' => 'me']) }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                            <x-icon name="ticket" class="h-4 w-4 text-slate-400" /> {{ __('common.nav.tickets') }}
                        </a>
                        <a href="{{ route('home') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                            <x-icon name="globe" class="h-4 w-4 text-slate-400" /> {{ __('common.nav.home') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm font-semibold text-coral-700 hover:bg-coral-50">
                                <x-icon name="logout" class="h-4 w-4 flip-icon" /> {{ __('common.actions.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="workspace-content" class="p-4 sm:p-6 lg:p-8">
            <x-flash />
            @yield('content')
        </main>
    </div>
</body>
</html>
