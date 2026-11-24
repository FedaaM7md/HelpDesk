@extends('layouts.public')

@section('title', $article->title . ' · ' . __('kb.title'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <nav class="text-xs text-slate-400" aria-label="{{ __('common.aria.breadcrumb') }}">
            <a href="{{ route('kb.index') }}" class="hover:text-brand-700">{{ __('kb.title') }}</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('kb.category', $article->category) }}" class="hover:text-brand-700">{{ $article->category->name() }}</a>
            <span class="mx-1.5">/</span>
            <span class="line-clamp-1 text-slate-600">{{ $article->title }}</span>
        </nav>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px] lg:items-start">
            <article class="card overflow-hidden">
                <header class="border-b border-slate-200 bg-slate-50/60 px-6 py-7 sm:px-8">
                    <a href="{{ route('kb.category', $article->category) }}" class="badge bg-brand-50 text-brand-700 ring-brand-100">{{ $article->category->name() }}</a>
                    <h1 class="mt-4 text-2xl font-bold leading-9 text-slate-900 sm:text-3xl sm:leading-[1.35]">{{ $article->title }}</h1>
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px] text-slate-400">
                        <span>{{ __('kb.updated', ['date' => hd_date($article->published_at ?? $article->updated_at)]) }}</span>
                        <span class="inline-flex items-center gap-1"><x-icon name="eye" class="h-3.5 w-3.5" /> {{ __('kb.views', ['count' => $article->views]) }}</span>
                        <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ __('kb.reading_time', ['count' => $article->readingMinutes()]) }}</span>
                    </div>
                </header>

                <div class="px-6 py-7 sm:px-8">
                    @if ($article->excerpt)
                        <p class="mb-6 rounded-xl border-s-4 border-teal-400 bg-teal-50/60 px-4 py-3 text-sm font-medium leading-7 text-teal-900">
                            {{ $article->excerpt }}
                        </p>
                    @endif
                    <div class="prose-article">{!! nl2br(e($article->body)) !!}</div>
                </div>

                <footer class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/60 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <a href="{{ route('kb.category', $article->category) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-teal-700">
                        <x-icon name="arrow-left" class="h-4 w-4 flip-icon" />
                        {{ $article->category->name() }}
                    </a>
                    <a href="{{ route('portal.submit') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('kb.submit_ticket') }}
                    </a>
                </footer>
            </article>

            <aside class="space-y-4">
                @if ($related->isNotEmpty())
                    <div class="card p-5">
                        <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('kb.related') }}</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ route('kb.show', $item) }}" class="block text-sm font-semibold leading-6 text-slate-700 hover:text-brand-700">{{ $item->title }}</a>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ __('kb.reading_time', ['count' => $item->readingMinutes()]) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl bg-brand-800 p-5 text-white">
                    <h2 class="text-sm font-bold">{{ __('kb.need_help') }}</h2>
                    <p class="mt-1 text-xs leading-6 text-brand-100/80">{{ __('kb.need_help_text') }}</p>
                    <a href="{{ route('portal.submit') }}" class="btn mt-4 w-full bg-white text-brand-800 hover:bg-teal-50">
                        {{ __('kb.submit_ticket') }}
                    </a>
                </div>

                <div class="card-muted p-5">
                    <a href="{{ route('kb.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-700">
                        <x-icon name="book" class="h-4 w-4" /> {{ __('kb.back') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
