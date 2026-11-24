@extends('layouts.public')

@section('title', __('common.app_name') . ' — ' . __('common.app_descriptor'))

@section('content')
    {{-- Hero: the real search + track tools, not a marketing mock. --}}
    <section class="border-b border-slate-200 bg-mint/50">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-start lg:gap-14 lg:px-8 lg:py-16">
            <div>
                <p class="eyebrow">{{ __('portal.home.eyebrow') }}</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight text-ink sm:text-4xl lg:text-[2.75rem]">
                    {{ __('portal.home.hero_question') }}
                </h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600 sm:text-base">
                    {{ __('portal.home.hero_support_line') }}
                </p>

                <form method="GET" action="{{ route('kb.index') }}" class="mt-7" role="search">
                    <label for="hero-search" class="sr-only">{{ __('portal.home.search_label') }}</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-slate-400">
                                <x-icon name="search" class="h-5 w-5" />
                            </span>
                            <input id="hero-search" type="search" name="q"
                                class="input ps-12 py-3.5 text-base shadow-sm"
                                data-search-input
                                placeholder="{{ __('portal.home.search_placeholder') }}">
                        </div>
                        <button type="submit" class="btn-primary px-6 py-3.5 text-base">
                            {{ __('portal.home.search_button') }}
                        </button>
                    </div>
                </form>

                <p class="mt-4 text-sm text-slate-500">{{ __('portal.home.contact_line') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a href="{{ route('portal.submit') }}" class="btn btn-coral btn-sm">
                        <x-icon name="send" class="h-4 w-4 flip-icon" />
                        {{ __('portal.home.cta_submit') }}
                    </a>
                    <a href="{{ route('portal.track') }}" class="btn btn-secondary btn-sm">
                        <x-icon name="search" class="h-4 w-4" />
                        {{ __('portal.home.cta_track') }}
                    </a>
                </div>

                <ul class="mt-7 flex flex-wrap gap-x-5 gap-y-2 text-xs font-medium text-slate-500">
                    @foreach (['shield' => __('portal.home.trust_sla'), 'globe' => __('portal.home.trust_bilingual'), 'lock' => __('portal.home.trust_secure')] as $icon => $label)
                        <li class="inline-flex items-center gap-1.5">
                            <x-icon :name="$icon" class="h-4 w-4 text-brand-500" />
                            {{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Real "track a ticket" tool, embedded so it is genuinely usable. --}}
            <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-ink">{{ __('portal.home.track_title') }}</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('portal.home.track_subtitle') }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon name="ticket" class="h-4 w-4" />
                    </span>
                </div>

                <form method="POST" action="{{ route('portal.track.lookup') }}" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label for="hero-reference" class="label text-xs">{{ __('fields.reference') }}</label>
                        <input id="hero-reference" type="text" name="reference" value="{{ old('reference') }}" required
                            placeholder="{{ __('portal.track.reference_placeholder') }}" autocomplete="off"
                            dir="ltr" class="input py-2 text-sm @error('reference') border-coral-400 @enderror">
                        @error('reference')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="hero-email" class="label text-xs">{{ __('fields.email') }}</label>
                        <input id="hero-email" type="email" name="email" value="{{ old('email') }}" required
                            placeholder="{{ __('portal.track.email_placeholder') }}" autocomplete="email" inputmode="email"
                            class="input py-2 text-sm @error('email') border-coral-400 @enderror">
                        @error('email')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary w-full">
                        {{ __('portal.home.track_cta') }}
                    </button>
                </form>
                <p class="mt-3 text-[11px] leading-4 text-slate-400">{{ __('portal.track.private_note') }}</p>
            </aside>
        </div>
    </section>

    {{-- Categories as a compact list, not identical icon cards. --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">{{ __('portal.home.categories_eyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-bold text-ink">{{ __('portal.home.categories_title') }}</h2>
                <p class="mt-1.5 max-w-xl text-sm leading-6 text-slate-500">{{ __('portal.home.categories_subtitle') }}</p>
            </div>
            <a href="{{ route('portal.submit') }}" class="link shrink-0 text-sm">{{ __('portal.home.cta_submit') }}</a>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($categories as $category)
                <a href="{{ route('portal.submit', ['category' => $category->slug]) }}"
                    class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-mint text-brand-700 ring-1 ring-brand-100">
                        <x-icon name="chat" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-ink">{{ $category->name() }}</span>
                        <span class="mt-0.5 line-clamp-1 block text-xs text-slate-500">
                            {{ $category->description() ?: __('portal.home.categories_subtitle') }}
                        </span>
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-brand-600 flip-icon" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- Knowledge base --}}
    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">{{ __('portal.home.kb_eyebrow') }}</p>
                    <h2 class="mt-2 text-2xl font-bold text-ink">{{ __('portal.home.kb_title') }}</h2>
                    <p class="mt-1.5 max-w-xl text-sm leading-6 text-slate-500">{{ __('portal.home.kb_subtitle') }}</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-xs font-semibold text-slate-500">{{ trans_choice('kb.article_count', $articleCount, ['count' => $articleCount]) }}</span>
                    <a href="{{ route('kb.index') }}" class="link">{{ __('portal.home.kb_browse') }}</a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($articles as $article)
                    <x-article-card :article="$article" />
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-empty-state icon="book" :title="__('kb.empty')" :description="__('portal.home.kb_subtitle')" />
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- The flow, shown as a real conversation rather than identical feature cards. --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
            <div>
                <p class="eyebrow">{{ __('portal.home.steps_subtitle') }}</p>
                <h2 class="mt-2 text-2xl font-bold text-ink">{{ __('portal.home.steps_title') }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">{{ __('portal.home.response_note') }}</p>
            </div>

            <ol class="space-y-6">
                @php
                    $steps = [
                        ['side' => 'customer', 'n' => 1, 'title' => __('portal.home.step1_title'), 'desc' => __('portal.home.step1_desc'), 'icon' => 'pencil'],
                        ['side' => 'agent', 'n' => 2, 'title' => __('portal.home.step2_title'), 'desc' => __('portal.home.step2_desc'), 'icon' => 'chat'],
                        ['side' => 'customer', 'n' => 3, 'title' => __('portal.home.step3_title'), 'desc' => __('portal.home.step3_desc'), 'icon' => 'star'],
                    ];
                @endphp
                @foreach ($steps as $step)
                    <li class="flex gap-3 {{ $step['side'] === 'agent' ? 'flex-row-reverse' : '' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $step['side'] === 'agent' ? 'bg-brand-700 text-white' : 'bg-mint text-brand-700 ring-1 ring-brand-100' }} text-xs font-bold">
                            {{ $step['n'] }}
                        </span>
                        <div class="chat-bubble {{ $step['side'] === 'agent' ? 'bg-brand-700 text-white' : 'bg-mint text-ink ring-1 ring-brand-100' }}">
                            <p class="flex items-center gap-1.5 text-xs font-semibold {{ $step['side'] === 'agent' ? 'text-brand-100' : 'text-brand-700' }}">
                                <x-icon :name="$step['icon']" class="h-3.5 w-3.5" />
                                {{ $step['title'] }}
                            </p>
                            <p class="mt-1 text-sm leading-6 {{ $step['side'] === 'agent' ? 'text-white/90' : 'text-slate-600' }}">{{ $step['desc'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
