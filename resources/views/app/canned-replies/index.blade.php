@extends('layouts.app')

@section('title', __('app.canned.title'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.knowledge_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.canned.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.canned.subtitle') }}</p>
        </div>
        <p class="max-w-sm text-[11px] leading-5 text-slate-400">{{ $canManage ? __('app.canned.create_hint') : __('app.canned.read_only') }}</p>
    </div>

    @if ($canManage)
        <section class="card mt-6 p-5">
            <h3 class="text-sm font-bold text-slate-900">{{ __('app.canned.new') }}</h3>
            <form method="POST" action="{{ route('app.canned.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="title_ar" class="label text-xs">{{ __('app.canned.title_ar') }} <span class="text-rose-500">*</span></label>
                        <input id="title_ar" name="title_ar" value="{{ old('title_ar') }}" required dir="rtl" class="input" placeholder="مثال: تأكيد استلام الطلب">
                        @error('title_ar')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="title_en" class="label text-xs">{{ __('app.canned.title_en') }} <span class="text-rose-500">*</span></label>
                        <input id="title_en" name="title_en" value="{{ old('title_en') }}" required dir="ltr" class="input text-start" placeholder="e.g. Request received">
                        @error('title_en')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="body_ar" class="label text-xs">{{ __('app.canned.body_ar') }} <span class="text-rose-500">*</span></label>
                        <textarea id="body_ar" name="body_ar" rows="5" required dir="rtl" class="textarea">{{ old('body_ar') }}</textarea>
                        @error('body_ar')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="body_en" class="label text-xs">{{ __('app.canned.body_en') }} <span class="text-rose-500">*</span></label>
                        <textarea id="body_en" name="body_en" rows="5" required dir="ltr" class="textarea text-start">{{ old('body_en') }}</textarea>
                        @error('body_en')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        {{ __('app.canned.active') }}
                    </label>
                    <button type="submit" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" /> {{ __('app.canned.new') }}
                    </button>
                </div>
            </form>
        </section>
    @endif

    <div class="mt-6 space-y-3">
        @forelse ($replies as $reply)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">{{ $reply->title() }}</h3>
                            <span class="badge {{ $reply->is_active ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                                {{ $reply->is_active ? __('app.canned.active') : __('app.canned.inactive') }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">{{ $reply->title_ar }} · {{ $reply->title_en }}</p>
                    </div>
                    @if ($canManage)
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('app.canned.destroy', $reply) }}"
                                x-data="confirmSubmit" data-confirm="{{ __('app.canned.delete_confirm') }}" @submit="submit($event)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50">
                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-xs leading-6 text-slate-600">{{ $reply->body() }}</p>

                @if ($canManage)
                    <details class="group mt-3">
                        <summary class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-teal-700">
                            <x-icon name="pencil" class="h-3.5 w-3.5" /> {{ __('app.tags.edit') }}
                        </summary>
                        <form method="POST" action="{{ route('app.canned.update', $reply) }}" class="mt-3 space-y-3 rounded-xl border border-slate-200 p-3">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 md:grid-cols-2">
                                <input name="title_ar" value="{{ old('title_ar', $reply->title_ar) }}" required dir="rtl" class="input py-2 text-xs" aria-label="{{ __('app.canned.title_ar') }}">
                                <input name="title_en" value="{{ old('title_en', $reply->title_en) }}" required dir="ltr" class="input py-2 text-xs text-start" aria-label="{{ __('app.canned.title_en') }}">
                                <textarea name="body_ar" rows="4" required dir="rtl" class="textarea text-xs">{{ old('body_ar', $reply->body_ar) }}</textarea>
                                <textarea name="body_en" rows="4" required dir="ltr" class="textarea text-xs text-start">{{ old('body_en', $reply->body_en) }}</textarea>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="is_active" value="1" @checked($reply->is_active) class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    {{ __('app.canned.active') }}
                                </label>
                                <button type="submit" class="btn-primary btn-sm">{{ __('app.canned.save') }}</button>
                            </div>
                        </form>
                    </details>
                @endif
            </article>
        @empty
            <x-empty-state icon="chat" :title="__('app.canned.empty')" :description="__('app.canned.create_hint')" />
        @endforelse
    </div>
@endsection
