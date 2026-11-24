@extends('layouts.public')

@section('title', __('kb.title'))

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('common.nav.knowledge_base') }}</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('kb.title') }}</h1>
                <p class="mt-3 text-sm leading-7 text-slate-500">{{ __('kb.subtitle') }}</p>
            </div>

            <form method="GET" action="{{ route('kb.index') }}" class="mt-7 flex flex-col gap-3 sm:flex-row" role="search">
                <div class="relative flex-1">
                    <label for="kb-search" class="sr-only">{{ __('kb.search_placeholder') }}</label>
                    <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-slate-400">
                        <x-icon name="search" class="h-5 w-5" />
                    </span>
                    <input id="kb-search" type="search" name="q" value="{{ $term }}" class="input ps-12 py-3"
                        placeholder="{{ __('kb.search_placeholder') }}">
                </div>
                @if ($categorySlug !== '')
                    <input type="hidden" name="category" value="{{ $categorySlug }}">
                @endif
                <button type="submit" class="btn-primary px-6">{{ __('common.actions.search') }}</button>
            </form>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('kb.index', array_filter(['q' => $term ?: null])) }}"
                    class="badge {{ $categorySlug === '' ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                    {{ __('kb.all_categories') }}
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('kb.index', array_filter(['category' => $category->slug, 'q' => $term ?: null])) }}"
                        class="badge {{ $categorySlug === $category->slug ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                        {{ $category->name() }}
                        <span class="opacity-60">({{ $category->articles_count }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($term !== '')
            <p class="mb-5 text-sm text-slate-500">{{ __('kb.results_for', ['term' => $term]) }}</p>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_280px] lg:items-start">
            <div>
                @if ($articles->isEmpty())
                    <x-empty-state icon="book" :title="__('common.no_results')"
                        :description="$term !== '' ? __('kb.empty_search') : __('kb.empty')">
                        <a href="{{ route('portal.submit') }}" class="btn-primary btn-sm">{{ __('kb.submit_ticket') }}</a>
                    </x-empty-state>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($articles as $article)
                            <x-article-card :article="$article" />
                        @endforeach
                    </div>
                    <div class="mt-6">
                        <x-pagination :paginator="$articles" />
                    </div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('kb.categories') }}</h2>
                    <ul class="mt-4 space-y-2">
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('kb.category', $category) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-brand-700">
                                    <span>{{ $category->name() }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $category->articles_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-2xl bg-brand-800 p-5 text-white">
                    <h2 class="text-sm font-bold">{{ __('kb.need_help') }}</h2>
                    <p class="mt-1 text-xs leading-6 text-brand-100/80">{{ __('kb.need_help_text') }}</p>
                    <a href="{{ route('portal.submit') }}" class="btn mt-4 w-full bg-white text-brand-800 hover:bg-teal-50">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('kb.submit_ticket') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
