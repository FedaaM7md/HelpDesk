@extends('layouts.app')

@section('title', $article->exists ? __('app.kb.edit_title') : __('app.kb.create_title'))

@section('content')
    <div class="max-w-4xl">
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <a href="{{ route('app.kb.index') }}" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-brand-700">
                <x-icon name="arrow-left" class="h-4 w-4 flip-icon" /> {{ __('app.kb.back') }}
            </a>
        </div>

        <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ $article->exists ? __('app.kb.edit_title') : __('app.kb.create_title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.kb.subtitle') }}</p>

        <div class="card mt-6 p-5 sm:p-7">
            <x-flash />

            <form method="POST" action="{{ $article->exists ? route('app.kb.update', $article) : route('app.kb.store') }}" class="space-y-5">
                @csrf
                @if ($article->exists)
                    @method('PATCH')
                @endif

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="kb_category_id" class="label">{{ __('app.kb.category') }} <span class="text-rose-500">*</span></label>
                        <select id="kb_category_id" name="kb_category_id" required class="select @error('kb_category_id') border-rose-400 @enderror">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('kb_category_id', $article->kb_category_id) === (string) $category->id)>{{ $category->name() }}</option>
                            @endforeach
                        </select>
                        @error('kb_category_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="locale" class="label">{{ __('app.kb.locale') }} <span class="text-rose-500">*</span></label>
                        <select id="locale" name="locale" class="select">
                            <option value="ar" @selected(old('locale', $article->locale) === 'ar')>{{ __('app.kb.locale_ar') }}</option>
                            <option value="en" @selected(old('locale', $article->locale) === 'en')>{{ __('app.kb.locale_en') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="label">{{ __('app.kb.status') }} <span class="text-rose-500">*</span></label>
                        <select id="status" name="status" class="select">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $article->status?->value ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="title" class="label">{{ __('fields.title') }} <span class="text-rose-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="200"
                        class="input @error('title') border-rose-400 @enderror">
                    @error('title')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="slug" class="label">{{ __('app.kb.slug') }}</label>
                        <input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" dir="ltr"
                            class="input text-start font-mono text-xs @error('slug') border-rose-400 @enderror"
                            placeholder="how-to-reset-password">
                        <p class="hint">{{ __('app.kb.slug_hint') }}</p>
                        @error('slug')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="translation_key" class="label">{{ __('app.kb.translation_key') }}</label>
                        <input id="translation_key" name="translation_key" value="{{ old('translation_key', $article->translation_key) }}"
                            list="translation-keys" dir="ltr" class="input text-start font-mono text-xs @error('translation_key') border-rose-400 @enderror"
                            placeholder="{{ __('app.kb.pick_translation') }}">
                        <datalist id="translation-keys">
                            @foreach ($translationKeys as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                        <p class="hint">{{ __('app.kb.translation_key_hint') }}</p>
                        @error('translation_key')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="excerpt" class="label">{{ __('app.kb.excerpt') }}</label>
                    <textarea id="excerpt" name="excerpt" rows="2" maxlength="300" class="textarea @error('excerpt') border-rose-400 @enderror">{{ old('excerpt', $article->excerpt) }}</textarea>
                    @error('excerpt')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="body" class="label">{{ __('app.kb.body') }} <span class="text-rose-500">*</span></label>
                    <textarea id="body" name="body" rows="16" required maxlength="60000"
                        class="textarea text-[13px] leading-7 @error('body') border-rose-400 @enderror">{{ old('body', $article->body) }}</textarea>
                    <p class="hint">{{ __('app.kb.body_hint') }}</p>
                    @error('body')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-center gap-1.5 text-[11px] text-slate-400">
                        <x-icon name="shield" class="h-3.5 w-3.5" /> {{ __('app.kb.body_plain_note') }}
                    </p>
                    <div class="flex gap-2">
                        <a href="{{ route('app.kb.index') }}" class="btn-secondary">{{ __('common.actions.cancel') }}</a>
                        <button type="submit" class="btn-primary">
                            <x-icon name="check" class="h-4 w-4" /> {{ __('app.kb.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
