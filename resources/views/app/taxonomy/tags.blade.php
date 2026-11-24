@extends('layouts.app')

@section('title', __('app.tags.title'))

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.admin_group') }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.tags.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.tags.subtitle') }}</p>
    </div>

    <section class="card mt-6 p-5">
        <h3 class="text-sm font-bold text-slate-900">{{ __('app.tags.create') }}</h3>
        <form method="POST" action="{{ route('app.tags.store') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_160px_auto] md:items-end">
            @csrf
            <div>
                <label for="tag_name_ar" class="label text-xs">{{ __('app.tags.name_ar') }}</label>
                <input id="tag_name_ar" name="name_ar" value="{{ old('name_ar') }}" required dir="rtl" class="input py-2 text-xs">
                @error('name_ar')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tag_name_en" class="label text-xs">{{ __('app.tags.name_en') }}</label>
                <input id="tag_name_en" name="name_en" value="{{ old('name_en') }}" required dir="ltr" class="input py-2 text-xs text-start">
                @error('name_en')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tag_color" class="label text-xs">{{ __('app.tags.color') }}</label>
                <select id="tag_color" name="color" class="select py-2 text-xs">
                    @foreach ($colors as $color)
                        <option value="{{ $color }}" @selected(old('color') === $color)>{{ __('app.tags.colors.'.$color) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary btn-sm">{{ __('app.tags.create') }}</button>
        </form>
    </section>

    <div class="mt-4 space-y-3">
        @forelse ($tags as $tag)
            <article class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="badge {{ $tag->badgeClasses() }}">{{ $tag->name() }}</span>
                    <span class="text-[11px] text-slate-400">{{ $tag->name_ar }} · {{ $tag->name_en }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">{{ $tag->tickets_count }} {{ __('app.tags.tickets_count') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <details class="group">
                        <summary class="btn btn-secondary btn-sm list-none">{{ __('app.tags.edit') }}</summary>
                        <form method="POST" action="{{ route('app.tags.update', $tag) }}" class="absolute z-20 mt-2 w-72 space-y-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                            @csrf
                            @method('PATCH')
                            <input name="name_ar" value="{{ $tag->name_ar }}" required dir="rtl" class="input py-1.5 text-xs" aria-label="{{ __('app.tags.name_ar') }}">
                            <input name="name_en" value="{{ $tag->name_en }}" required dir="ltr" class="input py-1.5 text-xs text-start" aria-label="{{ __('app.tags.name_en') }}">
                            <select name="color" class="select py-1.5 text-xs" aria-label="{{ __('app.tags.color') }}">
                                @foreach ($colors as $color)
                                    <option value="{{ $color }}" @selected($tag->color === $color)>{{ __('app.tags.colors.'.$color) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-primary btn-sm w-full">{{ __('common.actions.save') }}</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('app.tags.destroy', $tag) }}" x-data="confirmSubmit" data-confirm="{{ __('app.tags.delete_confirm') }}" @submit="submit($event)">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50" aria-label="{{ __('common.actions.delete') }}">
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <x-empty-state icon="tag" :title="__('app.tags.empty')" :description="__('app.tags.subtitle')" />
        @endforelse
    </div>
@endsection
