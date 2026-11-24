@extends('layouts.app')

@section('title', $user->exists ? __('app.users.edit_title') : __('app.users.create'))

@section('content')
    <div class="max-w-3xl">
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <a href="{{ route('app.users.index') }}" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-brand-700">
                <x-icon name="arrow-left" class="h-4 w-4 flip-icon" /> {{ __('app.users.back') }}
            </a>
        </div>

        <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ $user->exists ? __('app.users.edit_title') : __('app.users.create') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.users.subtitle') }}</p>

        @if ($user->isDemo())
            <div class="mt-4 flex items-start gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-xs leading-5 text-brand-800">
                <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ __('app.users.demo_locked') }}</span>
            </div>
        @endif

        <div class="card mt-6 p-5 sm:p-7">
            <x-flash />

            <form method="POST" action="{{ $user->exists ? route('app.users.update', $user) : route('app.users.store') }}" class="space-y-5">
                @csrf
                @if ($user->exists)
                    @method('PATCH')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">{{ __('fields.name') }} <span class="text-rose-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') border-rose-400 @enderror">
                        @error('name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="label">{{ __('fields.email') }} <span class="text-rose-500">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required dir="ltr"
                            @disabled($user->isDemo())
                            class="input text-start @error('email') border-rose-400 @enderror">
                        @if ($user->isDemo())<input type="hidden" name="email" value="{{ $user->email }}">@endif
                        @error('email')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role" class="label">{{ __('fields.role') }} <span class="text-rose-500">*</span></label>
                        <select id="role" name="role" class="select @error('role') border-rose-400 @enderror" @disabled($user->isDemo())>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $user->role?->value ?? 'agent') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($user->isDemo())<input type="hidden" name="role" value="{{ $user->role?->value }}">@endif
                        @error('role')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="job_title" class="label">{{ __('fields.job_title') }}</label>
                        <input id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="input">
                        @error('job_title')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="locale" class="label">{{ __('fields.locale') }}</label>
                        <select id="locale" name="locale" class="select">
                            <option value="ar" @selected(old('locale', $user->locale ?? 'ar') === 'ar')>{{ __('app.kb.locale_ar') }}</option>
                            <option value="en" @selected(old('locale', $user->locale) === 'en')>{{ __('app.kb.locale_en') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('app.users.status') }}</label>
                        <label class="flex h-[42px] items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm text-slate-600">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))
                                @disabled($user->isDemo())
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('app.users.active') }}
                        </label>
                        @if ($user->isDemo())<input type="hidden" name="is_active" value="1">@endif
                    </div>
                </div>

                <div class="grid gap-5 border-t border-slate-200 pt-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="label">{{ __('app.users.password') }} @unless ($user->exists)<span class="text-rose-500">*</span>@endunless</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" @required(! $user->exists)
                            @disabled($user->isDemo())
                            class="input @error('password') border-rose-400 @enderror">
                        <p class="hint">{{ $user->isDemo() ? __('app.users.demo_locked') : ($user->exists ? __('app.users.password_optional') : __('app.users.password_hint')) }}</p>
                        @error('password')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">{{ __('fields.password_confirmation') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input" @disabled($user->isDemo())>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('app.users.index') }}" class="btn-secondary">{{ __('common.actions.cancel') }}</a>
                    <button type="submit" class="btn-primary">
                        <x-icon name="check" class="h-4 w-4" /> {{ __('common.actions.save_changes') }}
                    </button>
                </div>
            </form>
        </div>

        @if ($user->exists && ! $user->is(auth()->user()) && ! $user->isDemo())
            <section class="mt-5 rounded-2xl border border-rose-200 bg-rose-50/50 p-5">
                <h3 class="text-sm font-bold text-rose-800">{{ __('app.users.danger_zone') }}</h3>
                <p class="mt-1 text-[11px] leading-5 text-rose-700">{{ __('app.users.danger_hint') }}</p>
                <form method="POST" action="{{ route('app.users.destroy', $user) }}" class="mt-3" x-data="confirmSubmit" data-confirm="{{ __('app.users.delete_confirm') }}" @submit="submit($event)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">{{ __('app.users.danger_zone') }}</button>
                </form>
            </section>
        @endif
    </div>
@endsection
