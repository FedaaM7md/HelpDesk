@extends('layouts.app')

@section('title', __('app.tickets.new'))

@section('content')
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('app.tickets.index') }}" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-brand-700">
            <x-icon name="arrow-left" class="h-4 w-4 flip-icon" /> {{ __('app.tickets.title') }}
        </a>
        <span>/</span>
        <span class="font-semibold text-slate-600">{{ __('app.tickets.new') }}</span>
    </div>

    <div class="mt-4 max-w-3xl">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('app.tickets.new') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('portal.submit.subtitle') }}</p>

        <div class="card mt-6 p-5 sm:p-7">
            <x-flash />

            <form method="POST" action="{{ route('app.tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="requester_name" class="label">{{ __('fields.requester_name') }} <span class="text-rose-500">*</span></label>
                        <input id="requester_name" name="requester_name" type="text" required value="{{ old('requester_name') }}"
                            class="input @error('requester_name') border-rose-400 @enderror">
                        @error('requester_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="requester_email" class="label">{{ __('fields.requester_email') }} <span class="text-rose-500">*</span></label>
                        <input id="requester_email" name="requester_email" type="email" required dir="ltr"
                            value="{{ old('requester_email') }}" class="input text-start @error('requester_email') border-rose-400 @enderror">
                        @error('requester_email')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="subject" class="label">{{ __('fields.subject') }} <span class="text-rose-500">*</span></label>
                    <input id="subject" name="subject" type="text" required maxlength="200" value="{{ old('subject') }}"
                        class="input @error('subject') border-rose-400 @enderror">
                    @error('subject')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="category_id" class="label">{{ __('fields.category') }} <span class="text-rose-500">*</span></label>
                        <select id="category_id" name="category_id" required class="select @error('category_id') border-rose-400 @enderror">
                            <option value="">{{ __('portal.submit.category_placeholder') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name() }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="priority" class="label">{{ __('fields.priority') }}</label>
                        <select id="priority" name="priority" class="select">
                            @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        @error('priority')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="assigned_to" class="label">{{ __('fields.assignee') }}</label>
                        <select id="assigned_to" name="assigned_to" class="select">
                            <option value="">{{ __('common.unassigned') }}</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((string) old('assigned_to') === (string) $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="message" class="label">{{ __('fields.message') }} <span class="text-rose-500">*</span></label>
                    <textarea id="message" name="message" rows="8" required maxlength="20000"
                        class="textarea @error('message') border-rose-400 @enderror">{{ old('message') }}</textarea>
                    @error('message')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="attachments" class="label">{{ __('fields.attachments') }} <span class="font-normal text-slate-400">({{ __('common.optional') }})</span></label>
                    <input id="attachments" name="attachments[]" type="file" multiple
                        accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip"
                        class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                    <p class="hint">{{ __('app.tickets.attachments_hint', ['count' => config('helpdesk.attachments.max_files'), 'size' => round(config('helpdesk.attachments.max_kilobytes') / 1024, 1).' MB']) }}</p>
                    @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('app.tickets.index') }}" class="btn-secondary">{{ __('common.actions.cancel') }}</a>
                    <button type="submit" class="btn-primary">
                        <x-icon name="plus" class="h-4 w-4" /> {{ __('app.tickets.new') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
