@extends('layouts.public')

@section('title', __('portal.submit.title'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="max-w-2xl">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('common.nav.submit_ticket') }}</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ __('portal.submit.title') }}</h1>
            <p class="mt-3 text-sm leading-7 text-slate-500">{{ __('portal.submit.subtitle') }}</p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.35fr_0.65fr] lg:items-start">
            <div class="card p-5 sm:p-8">
                <x-flash />

                <form method="POST" action="{{ route('portal.submit.store') }}" enctype="multipart/form-data" class="space-y-8">
                    @csrf

                    <div class="absolute -start-[9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <fieldset>
                        <legend class="text-sm font-bold text-slate-900">{{ __('portal.submit.your_details') }}</legend>
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="requester_name" class="label">{{ __('fields.requester_name') }} <span class="text-rose-500">*</span></label>
                                <input id="requester_name" name="requester_name" type="text" required autocomplete="name"
                                    value="{{ old('requester_name') }}" placeholder="{{ __('portal.submit.name_placeholder') }}"
                                    class="input @error('requester_name') border-rose-400 @enderror">
                                @error('requester_name')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="requester_email" class="label">{{ __('fields.requester_email') }} <span class="text-rose-500">*</span></label>
                                <input id="requester_email" name="requester_email" type="email" required autocomplete="email"
                                    dir="ltr" value="{{ old('requester_email') }}" placeholder="{{ __('portal.submit.email_placeholder') }}"
                                    class="input text-start @error('requester_email') border-rose-400 @enderror">
                                @error('requester_email')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t border-slate-200 pt-8">
                        <legend class="text-sm font-bold text-slate-900">{{ __('portal.submit.ticket_details') }}</legend>
                        <div class="mt-4 space-y-5">
                            <div>
                                <label for="category_id" class="label">{{ __('fields.category') }} <span class="text-rose-500">*</span></label>
                                <select id="category_id" name="category_id" required class="select @error('category_id') border-rose-400 @enderror">
                                    <option value="">{{ __('portal.submit.category_placeholder') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id', $selectedCategory?->id) === (string) $category->id)>
                                            {{ $category->name() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<p class="error-text">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="subject" class="label">{{ __('fields.subject') }} <span class="text-rose-500">*</span></label>
                                <input id="subject" name="subject" type="text" required maxlength="200" value="{{ old('subject') }}"
                                    placeholder="{{ __('portal.submit.subject_placeholder') }}"
                                    class="input @error('subject') border-rose-400 @enderror">
                                @error('subject')<p class="error-text">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="message" class="label">{{ __('fields.message') }} <span class="text-rose-500">*</span></label>
                                <textarea id="message" name="message" rows="8" required maxlength="20000"
                                    placeholder="{{ __('portal.submit.message_placeholder') }}"
                                    class="textarea @error('message') border-rose-400 @enderror">{{ old('message') }}</textarea>
                                <p class="hint">{{ __('portal.submit.response_note') }}</p>
                                @error('message')<p class="error-text">{{ $message }}</p>@enderror
                            </div>

                            <div x-data="fileList">
                                <label for="attachments" class="label">{{ __('fields.attachments') }} <span class="font-normal text-slate-400">({{ __('common.optional') }})</span></label>
                                <input id="attachments" name="attachments[]" type="file" multiple
                                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip"
                                    @change="pick($event)"
                                    class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:border-teal-400">
                                <p class="hint">{{ __('portal.submit.attachments_hint', ['count' => $maxFiles, 'size' => $maxSize]) }}</p>
                                <ul x-show="files.length" x-cloak class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="file in files" :key="file">
                                        <li class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">
                                            <x-icon name="paperclip" class="h-3 w-3" />
                                            <span x-text="file"></span>
                                        </li>
                                    </template>
                                </ul>
                                @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                                @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-start gap-2 text-xs leading-5 text-slate-500">
                            <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                            {{ __('portal.submit.secure_note') }}
                        </p>
                        <button type="submit" class="btn-primary shrink-0 px-6">
                            <x-icon name="send" class="h-4 w-4 flip-icon" />
                            {{ __('portal.submit.submit') }}
                        </button>
                    </div>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="card p-6">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('portal.submit.side_title') }}</h2>
                    <ol class="mt-5 space-y-4">
                        @foreach ([__('portal.submit.side_item1'), __('portal.submit.side_item2'), __('portal.submit.side_item3')] as $index => $item)
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-bold text-teal-700 ring-1 ring-teal-100">
                                    {{ $index + 1 }}
                                </span>
                                <p class="text-xs leading-6 text-slate-600">{{ $item }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="card-muted p-5">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800">
                        <x-icon name="clock" class="h-4 w-4" />
                        {{ __('portal.home.response_note') }}
                    </p>
                    <p class="mt-2 text-xs leading-6 text-slate-500">{{ __('portal.ticket.signed_note') }}</p>
                </div>

                <a href="{{ route('kb.index') }}" class="card flex items-center gap-3 p-5 transition hover:border-teal-300">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <x-icon name="book" class="h-5 w-5" />
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">{{ __('common.nav.knowledge_base') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('portal.home.kb_subtitle') }}</span>
                    </span>
                </a>
            </aside>
        </div>
    </div>
@endsection
