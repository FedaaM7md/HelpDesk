@extends('layouts.app')

@section('title', __('app.kb.title'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.knowledge_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.kb.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.kb.subtitle') }}</p>
        </div>
        <a href="{{ route('app.kb.create') }}" class="btn-primary btn-sm shrink-0">
            <x-icon name="plus" class="h-4 w-4" /> {{ __('app.kb.new') }}
        </a>
    </div>

    <form method="GET" action="{{ route('app.kb.index') }}" class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row">
        <div class="relative flex-1">
            <label for="kb-admin-search" class="sr-only">{{ __('common.actions.search') }}</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input id="kb-admin-search" type="search" name="q" value="{{ $term }}" class="input ps-9 py-2 text-xs" placeholder="{{ __('app.kb.search_placeholder') }}">
        </div>
        <select name="status" class="select w-full py-2 text-xs sm:w-44" aria-label="{{ __('fields.status') }}">
            <option value="">{{ __('app.kb.all_statuses') }}</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="locale" class="select w-full py-2 text-xs sm:w-40" aria-label="{{ __('fields.locale') }}">
            <option value="">{{ __('app.kb.all_locales') }}</option>
            <option value="ar" @selected(request('locale') === 'ar')>{{ __('app.kb.locale_ar') }}</option>
            <option value="en" @selected(request('locale') === 'en')>{{ __('app.kb.locale_en') }}</option>
        </select>
        <button type="submit" class="btn-primary btn-sm">{{ __('common.actions.filter') }}</button>
        <a href="{{ route('app.kb.index') }}" class="btn-secondary btn-sm">{{ __('common.actions.reset') }}</a>
    </form>

    <div class="table-wrap mt-4">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.article') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.category') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.locale') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.views') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.kb.updated') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('app.kb.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($articles as $article)
                    <tr class="hover:bg-slate-50/60">
                        <td class="max-w-xs px-4 py-3">
                            <a href="{{ route('app.kb.edit', $article) }}" class="block truncate font-semibold text-slate-800 hover:text-brand-700">{{ $article->title }}</a>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">{{ $article->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $article->category?->name() }}</td>
                        <td class="px-4 py-3">
                            <span class="badge bg-slate-100 text-slate-600 ring-slate-200">{{ strtoupper($article->locale) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $article->status->badgeClasses() }}">{{ $article->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $article->views }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-400">{{ hd_date($article->updated_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('app.kb.edit', $article) }}" class="btn-secondary btn-sm">{{ __('app.kb.edit') }}</a>
                                <form method="POST" action="{{ route('app.kb.publish', $article) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        {{ $article->isPublished() ? __('app.kb.unpublish') : __('app.kb.publish') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('app.kb.destroy', $article) }}" x-data="confirmSubmit" data-confirm="{{ __('app.kb.delete_confirm') }}" @submit="submit($event)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50" aria-label="{{ __('common.actions.delete') }}">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('app.kb.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-pagination :paginator="$articles" />
    </div>
@endsection
