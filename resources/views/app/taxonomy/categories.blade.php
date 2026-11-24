@extends('layouts.app')

@section('title', __('app.categories.title'))

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.admin_group') }}</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.categories.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.categories.subtitle') }}</p>
    </div>

    <section class="card mt-6 p-5">
        <h3 class="text-sm font-bold text-slate-900">{{ __('app.categories.create') }}</h3>
        <form method="POST" action="{{ route('app.categories.store') }}" class="mt-4 space-y-3">
            @csrf
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label for="cat_name_ar" class="label text-xs">{{ __('app.categories.name_ar') }}</label>
                    <input id="cat_name_ar" name="name_ar" value="{{ old('name_ar') }}" required dir="rtl" class="input py-2 text-xs">
                    @error('name_ar')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cat_name_en" class="label text-xs">{{ __('app.categories.name_en') }}</label>
                    <input id="cat_name_en" name="name_en" value="{{ old('name_en') }}" required dir="ltr" class="input py-2 text-xs text-start">
                    @error('name_en')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cat_desc_ar" class="label text-xs">{{ __('app.categories.description_ar') }}</label>
                    <input id="cat_desc_ar" name="description_ar" value="{{ old('description_ar') }}" dir="rtl" class="input py-2 text-xs">
                </div>
                <div>
                    <label for="cat_desc_en" class="label text-xs">{{ __('app.categories.description_en') }}</label>
                    <input id="cat_desc_en" name="description_en" value="{{ old('description_en') }}" dir="ltr" class="input py-2 text-xs text-start">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-5">
                <div class="w-32">
                    <label for="cat_sort" class="label text-xs">{{ __('app.categories.sort_order') }}</label>
                    <input id="cat_sort" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', 0) }}" class="input py-2 text-xs">
                </div>
                <label class="mt-5 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    {{ __('app.categories.active') }}
                </label>
                <button type="submit" class="btn-primary btn-sm mt-5">{{ __('app.categories.create') }}</button>
            </div>
        </form>
    </section>

    <div class="table-wrap mt-4">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('app.categories.name_ar') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.categories.name_en') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.categories.sort_order') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.categories.active') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.categories.tickets_count') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('app.users.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $category->name_ar }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $category->name_en }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $category->is_active ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                                {{ $category->is_active ? __('common.yes') : __('common.no') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $category->tickets_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm list-none">{{ __('app.categories.edit') }}</summary>
                                    <form method="POST" action="{{ route('app.categories.update', $category) }}" class="absolute z-20 mt-2 w-80 space-y-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                                        @csrf
                                        @method('PATCH')
                                        <input name="name_ar" value="{{ $category->name_ar }}" required dir="rtl" class="input py-1.5 text-xs" aria-label="{{ __('app.categories.name_ar') }}">
                                        <input name="name_en" value="{{ $category->name_en }}" required dir="ltr" class="input py-1.5 text-xs text-start" aria-label="{{ __('app.categories.name_en') }}">
                                        <input name="description_ar" value="{{ $category->description_ar }}" dir="rtl" class="input py-1.5 text-xs" aria-label="{{ __('app.categories.description_ar') }}">
                                        <input name="description_en" value="{{ $category->description_en }}" dir="ltr" class="input py-1.5 text-xs text-start" aria-label="{{ __('app.categories.description_en') }}">
                                        <div class="flex items-center gap-3">
                                            <input name="sort_order" type="number" min="0" max="999" value="{{ $category->sort_order }}" class="input w-24 py-1.5 text-xs" aria-label="{{ __('app.categories.sort_order') }}">
                                            <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                                <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                                {{ __('app.categories.active') }}
                                            </label>
                                        </div>
                                        <button type="submit" class="btn-primary btn-sm w-full">{{ __('app.categories.save') }}</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('app.categories.destroy', $category) }}" x-data="confirmSubmit" data-confirm="{{ __('app.categories.delete_confirm') }}" @submit="submit($event)">
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
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('app.categories.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
