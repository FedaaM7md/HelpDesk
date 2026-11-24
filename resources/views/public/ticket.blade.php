@extends('layouts.public')

@section('title', $ticket->reference . ' · ' . $ticket->subject)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <x-flash />

        <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold text-brand-700">{{ $ticket->reference }}</span>
                    <x-status-badge :status="$ticket->status" />
                    <x-priority-badge :priority="$ticket->priority" />
                </div>
                <h1 class="mt-2 text-2xl font-bold leading-9 text-slate-900 sm:text-3xl">{{ $ticket->subject }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span>{{ __('portal.ticket.submitted') }} {{ hd_datetime($ticket->created_at) }}</span>
                    <span>{{ $ticket->category?->name() }}</span>
                    <span>{{ __('portal.ticket.assigned_to') }}: {{ $ticket->assignee?->name ?? __('portal.ticket.support_team') }}</span>
                </p>
            </div>
            <a href="{{ route('portal.track') }}" class="btn btn-secondary btn-sm shrink-0">
                <x-icon name="search" class="h-4 w-4" />
                {{ __('portal.track.title') }}
            </a>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.6fr] lg:items-start">
            <div class="space-y-6">
                <section class="card">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('portal.ticket.conversation') }}</h2>
                        <span class="text-[11px] text-slate-400">{{ $ticket->messages->count() }} {{ __('portal.ticket.conversation') }}</span>
                    </div>

                    <ol class="divide-y divide-slate-100">
                        @forelse ($ticket->messages as $message)
                            @php($fromAgent = $message->author_type === \App\Enums\MessageAuthor::Agent)
                            <li class="px-5 py-6">
                                <div class="flex items-start gap-3">
                                    <x-avatar :name="$message->author_name" size="sm"
                                        class="{{ $fromAgent ? '!bg-teal-50 !text-teal-700 !ring-teal-200' : '' }}" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <p class="text-sm font-bold text-slate-900">
                                                {{ $fromAgent ? $message->author_name : $message->author_name }}
                                            </p>
                                            <span class="badge {{ $fromAgent ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                                {{ $fromAgent ? __('portal.ticket.support_team') : __('portal.ticket.you') }}
                                            </span>
                                            <time class="text-[11px] text-slate-400" datetime="{{ $message->created_at->toIso8601String() }}">{{ hd_datetime($message->created_at) }}</time>
                                        </div>
                                        <div class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{{ $message->body }}</div>

                                        @if ($message->attachments->isNotEmpty())
                                            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                                @foreach ($message->attachments as $attachment)
                                                    <li>
                                                        <a href="{{ $attachment->temporaryUrl(30) }}"
                                                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 transition hover:border-teal-300 hover:bg-white">
                                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-slate-200">
                                                                <x-icon name="paperclip" class="h-4 w-4" />
                                                            </span>
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate font-semibold">{{ $attachment->original_name }}</span>
                                                                <span class="block text-[10px] text-slate-400">{{ $attachment->humanSize() }}</span>
                                                            </span>
                                                            <x-icon name="download" class="h-4 w-4 shrink-0 text-slate-400" />
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-12 text-center text-sm text-slate-500">{{ __('portal.ticket.no_messages') }}</li>
                        @endforelse
                    </ol>
                </section>

                @if ($ticket->isResolved() && ! $ticket->csat)
                    <section class="overflow-hidden rounded-2xl bg-brand-800 p-6 text-white">
                        <div class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10 text-teal-200">
                                <x-icon name="star" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-lg font-bold">{{ __('portal.ticket.satisfaction_title') }}</h2>
                                <p class="mt-1 max-w-xl text-sm leading-6 text-brand-100/80">{{ __('portal.ticket.satisfaction_text') }}</p>
                                <a href="{{ $csatUrl }}" class="btn mt-4 bg-white text-brand-800 hover:bg-teal-50">
                                    <x-icon name="star" class="h-4 w-4" />
                                    {{ __('portal.ticket.rate_experience') }}
                                </a>
                            </div>
                        </div>
                    </section>
                @elseif ($ticket->csat)
                    <section class="card p-5">
                        <h2 class="text-sm font-bold text-slate-900">{{ __('portal.ticket.satisfaction_title') }}</h2>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="flex items-center gap-0.5 text-amber-500">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-icon name="star" class="h-4 w-4 {{ $i <= $ticket->csat->score ? 'fill-amber-400' : 'text-slate-300' }}" />
                                @endfor
                            </div>
                            <span class="text-xs font-semibold text-slate-600">{{ __('portal.ticket.csat_score', ['score' => $ticket->csat->score]) }}</span>
                        </div>
                        @if ($ticket->csat->comment)
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $ticket->csat->comment }}</p>
                        @endif
                    </section>
                @endif

                <section class="card p-5 sm:p-6">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('portal.ticket.reply_title') }}</h2>
                    <p class="mt-1 text-xs leading-6 text-slate-500">{{ __('portal.ticket.reply_hint') }}</p>

                    @if ($ticket->status === \App\Enums\TicketStatus::Closed)
                        <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">{{ __('portal.ticket.closed_notice') }}</p>
                    @endif

                    <form method="POST" action="{{ $trackingUrl }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="message" class="sr-only">{{ __('fields.reply') }}</label>
                            <textarea id="message" name="message" rows="6" required maxlength="20000"
                                placeholder="{{ __('portal.ticket.reply_placeholder') }}"
                                class="textarea @error('message') border-rose-400 @enderror">{{ old('message') }}</textarea>
                            @error('message')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div x-data="fileList" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <input id="reply-attachments" name="attachments[]" type="file" multiple
                                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip"
                                    @change="pick($event)"
                                    class="block w-full cursor-pointer text-xs text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                                <p class="hint">{{ __('portal.ticket.attach_hint', ['count' => $maxFiles]) }}</p>
                                <ul x-show="files.length" x-cloak class="mt-1 flex flex-wrap gap-1.5">
                                    <template x-for="file in files" :key="file">
                                        <li class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600" x-text="file"></li>
                                    </template>
                                </ul>
                                @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                                @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="btn-primary shrink-0">
                                <x-icon name="send" class="h-4 w-4 flip-icon" />
                                {{ __('portal.ticket.send_reply') }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('portal.ticket.sla') }}</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.first_response_due') }}</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ hd_datetime($ticket->first_response_due_at) }}</dd>
                            <dd class="mt-1">
                                @if ($ticket->first_responded_at)
                                    <span class="text-[11px] text-teal-700">{{ __('portal.ticket.first_responded') }}: {{ hd_datetime($ticket->first_responded_at) }}</span>
                                @elseif ($ticket->isFirstResponseOverdue())
                                    <x-sla-badge state="breached" />
                                @else
                                    <x-sla-badge state="on_track" />
                                @endif
                            </dd>
                        </div>
                        <div class="border-t border-slate-100 pt-4">
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.resolution_due') }}</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ hd_datetime($ticket->resolution_due_at) }}</dd>
                            <dd class="mt-1">
                                @if ($ticket->resolved_at)
                                    <span class="text-[11px] text-teal-700">{{ __('portal.ticket.resolved_at') }}: {{ hd_datetime($ticket->resolved_at) }}</span>
                                @else
                                    <x-sla-badge :state="$ticket->isResolutionOverdue() ? 'breached' : 'on_track'" />
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="card p-5">
                    <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('portal.ticket.reference') }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.reference') }}</dt>
                            <dd class="font-mono text-xs font-bold text-brand-800">{{ $ticket->reference }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.status') }}</dt>
                            <dd><x-status-badge :status="$ticket->status" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.priority') }}</dt>
                            <dd><x-priority-badge :priority="$ticket->priority" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-slate-500">{{ __('portal.ticket.category') }}</dt>
                            <dd class="text-xs font-semibold text-slate-700">{{ $ticket->category?->name() ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="card-muted p-5">
                    <p class="flex items-start gap-2 text-xs leading-6 text-slate-500">
                        <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                        {{ __('portal.ticket.signed_note') }}
                    </p>
                    <p class="mt-2 text-xs leading-6 text-slate-500">{{ __('portal.ticket.tracking_notice') }}</p>
                </div>

                <div class="card p-5">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('portal.ticket.have_question') }}</h2>
                    <a href="{{ route('kb.index') }}" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-teal-700">
                        <x-icon name="book" class="h-4 w-4" />
                        {{ __('portal.ticket.kb_link') }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
@endsection
