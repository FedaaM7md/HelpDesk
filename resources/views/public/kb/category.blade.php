@extends('layouts.public')

@section('title', $category->name() . ' · ' . __('kb.title'))

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <nav class="text-xs text-slate-400" aria-label="{{ __('common.aria.breadcrumb') }}">
                <a href="{{ route('kb.index') }}" class="hover:text-brand-700">{{ __('kb.title') }}</a>
                <span class="mx-1.5">/</span>
                <span class="text-slate-600">{{ $category->name() }}</span>
            </nav>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">{{ $category->name() }}</h1>
            @if ($category->description())
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500">{{ $category->description() }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_280px] lg:items-start">
            <div>
                @if ($articles->isEmpty())
                    <x-empty-state icon="book" :title="__('kb.empty')" :description="__('kb.empty_search')">
                        <a href="{{ route('kb.index') }}" class="btn-secondary btn-sm">{{ __('kb.back') }}</a>
                    </x-empty-state>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($articles as $article)
                            <x-article-card :article="$article" />
                        @endforeach
                    </div>
                    <div class="mt-6"><x-pagination :paginator="$articles" /></div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('kb.categories') }}</h2>
                    <ul class="mt-4 space-y-2">
                        <li><a href="{{ route('kb.index') }}" class="block rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-50">{{ __('kb.all_categories') }}</a></li>
                        @foreach ($categories as $item)
                            <li>
                                <a href="{{ route('kb.category', $item) }}"
                                    class="block rounded-lg px-2 py-1.5 text-sm {{ $item->id === $category->id ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                    {{ $item->name() }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-2xl bg-brand-800 p-5 text-white">
                    <h2 class="text-sm font-bold">{{ __('kb.need_help') }}</h2>
                    <p class="mt-1 text-xs leading-6 text-brand-100/80">{{ __('kb.need_help_text') }}</p>
                    <a href="{{ route('portal.submit') }}" class="btn mt-4 w-full bg-white text-brand-800 hover:bg-teal-50">
                        <x-icon name="plus" class="h-4 w-4" /> {{ __('kb.submit_ticket') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
