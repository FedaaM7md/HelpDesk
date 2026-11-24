@extends('layouts.app')

@section('title', $ticket->reference)

@section('content')
    @php($user = auth()->user())

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
            <a href="{{ route('app.tickets.index') }}" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-brand-700">
                <x-icon name="arrow-left" class="h-4 w-4 flip-icon" /> {{ __('app.tickets.title') }}
            </a>
            <span>/</span>
            <span class="font-mono font-semibold text-slate-600">{{ $ticket->reference }}</span>
        </div>

        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$ticket->status" />
                    <x-priority-badge :priority="$ticket->priority" />
                    <span class="badge bg-slate-100 text-slate-600 ring-slate-200">
                        <x-icon :name="$ticket->channel->icon()" class="h-3 w-3" />
                        {{ $ticket->channel->label() }}
                    </span>
                    <x-sla-badge :state="$ticket->slaState()" />
                </div>
                <h2 class="mt-2 break-words text-2xl font-bold leading-9 text-slate-900">{{ $ticket->subject }}</h2>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span>{{ __('app.tickets.created') }} {{ hd_datetime($ticket->created_at) }}</span>
                    <span>{{ $ticket->category?->name() }}</span>
                    <span>{{ __('app.tickets.assignee') }}: {{ $ticket->assignee?->name ?? __('common.unassigned') }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($ticket->isUnassigned())
                    <form method="POST" action="{{ route('app.tickets.claim', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="user" class="h-4 w-4" /> {{ __('app.tickets.claim') }}
                        </button>
                    </form>
                @endif
                @if (! $ticket->isResolved())
                    <form method="POST" action="{{ route('app.tickets.solve', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-teal btn-sm">
                            <x-icon name="check" class="h-4 w-4" /> {{ __('app.tickets.solve') }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('app.tickets.reopen', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="refresh" class="h-4 w-4" /> {{ __('app.tickets.reopen') }}
                        </button>
                    </form>
                @endif
                @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
                    <form method="POST" action="{{ route('app.tickets.close', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="lock" class="h-4 w-4" /> {{ __('app.tickets.close') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if ($ticket->isSlaBreached())
        <div class="mt-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <p class="leading-6">{{ __('app.tickets.breached_note') }}</p>
        </div>
    @endif

    @if ($ticket->isUnassigned())
        <div class="mt-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <x-icon name="user" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <p class="leading-6">{{ __('app.tickets.unassigned_notice') }} {{ __('app.tickets.claim_hint') }}</p>
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.55fr_1fr] xl:items-start">
        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('app.tickets.conversation') }}</h2>
                    <span class="text-[11px] text-slate-400">{{ $ticket->messages->count() }}</span>
                </div>

                <ol class="divide-y divide-slate-100">
                    @foreach ($ticket->messages as $message)
                        @if ($message->author_type === \App\Enums\MessageAuthor::System)
                            <li class="bg-slate-50/60 px-5 py-3">
                                <p class="flex items-center justify-center gap-2 text-center text-[11px] text-slate-400">
                                    <x-icon name="refresh" class="h-3.5 w-3.5" />
                                    {{ $message->body }}
                                    <span>· {{ hd_relative($message->created_at) }}</span>
                                </p>
                            </li>
                            @continue
                        @endif

                        <li class="px-5 py-6 {{ $message->is_internal ? 'bg-amber-50/40' : '' }}">
                            <div class="flex items-start gap-3">
                                <x-avatar :name="$message->author_name" size="sm"
                                    class="{{ $message->is_internal ? '!bg-amber-100 !text-amber-800 !ring-amber-200' : ($message->author_type === \App\Enums\MessageAuthor::Agent ? '!bg-teal-50 !text-teal-700 !ring-teal-200' : '') }}" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <p class="text-sm font-bold text-slate-900">{{ $message->author_name }}</p>
                                        @if ($message->is_internal)
                                            <span class="badge bg-amber-50 text-amber-800 ring-amber-200">
                                                <x-icon name="lock" class="h-3 w-3" /> {{ __('app.tickets.internal_note') }}
                                            </span>
                                        @elseif ($message->author_type === \App\Enums\MessageAuthor::Agent)
                                            <span class="badge bg-teal-50 text-teal-700 ring-teal-200">
                                                <x-icon name="headset" class="h-3 w-3" /> {{ __('app.tickets.agent_reply') }}
                                            </span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600 ring-slate-200">
                                                <x-icon name="user" class="h-3 w-3" /> {{ __('app.tickets.customer_reply') }}
                                            </span>
                                        @endif
                                        <time class="text-[11px] text-slate-400" datetime="{{ $message->created_at->toIso8601String() }}">{{ hd_datetime($message->created_at) }}</time>
                                        @if ($message->source === 'email')
                                            <span class="text-[10px] text-slate-400">· {{ __('enums.channels.email') }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{{ $message->body }}</div>

                                    @if ($message->attachments->isNotEmpty())
                                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                            @foreach ($message->attachments as $attachment)
                                                <li>
                                                    <a href="{{ $attachment->temporaryUrl(30) }}"
                                                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 transition hover:border-teal-300">
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-brand-700 ring-1 ring-slate-200">
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
                    @endforeach
                </ol>
            </section>

            <section class="card p-5" x-data="replyComposer">
                <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1">
                    <button type="button" @click="setTab('reply')"
                        class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition"
                        :class="tab === 'reply' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <x-icon name="send" class="me-1 inline h-3.5 w-3.5 flip-icon" />
                        {{ __('app.tickets.reply_tab') }}
                    </button>
                    <button type="button" @click="setTab('note')"
                        class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition"
                        :class="tab === 'note' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <x-icon name="lock" class="me-1 inline h-3.5 w-3.5" />
                        {{ __('app.tickets.note_tab') }}
                    </button>
                </div>

                <form x-show="tab === 'reply'" method="POST" action="{{ route('app.tickets.reply', $ticket) }}"
                    enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <label for="reply-body" class="sr-only">{{ __('app.tickets.reply_tab') }}</label>
                        @if ($cannedReplies->isNotEmpty())
                            <select @change="insertCanned($event)" class="select w-full py-1.5 text-xs sm:w-64">
                                <option value="">{{ __('app.tickets.insert_canned') }}</option>
                                @foreach ($cannedReplies as $reply)
                                    <option value="{{ $reply->id }}" data-body="{{ $reply->body() }}">{{ $reply->title() }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <textarea id="reply-body" name="body" rows="6" required maxlength="20000"
                        class="textarea @error('body') border-rose-400 @enderror"
                        placeholder="{{ __('app.tickets.reply_placeholder') }}">{{ old('body') }}</textarea>
                    @error('body')<p class="error-text">{{ $message }}</p>@enderror

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <input type="file" name="attachments[]" multiple
                                accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip"
                                class="block w-full text-xs text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                            <p class="hint">{{ __('app.tickets.attachments_hint', ['count' => config('helpdesk.attachments.max_files'), 'size' => round(config('helpdesk.attachments.max_kilobytes') / 1024, 1).' MB']) }}</p>
                            @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                            @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="btn-primary shrink-0">
                            <x-icon name="send" class="h-4 w-4 flip-icon" />
                            {{ __('app.tickets.send_reply') }}
                        </button>
                    </div>
                </form>

                <form x-show="tab === 'note'" x-cloak method="POST" action="{{ route('app.tickets.note', $ticket) }}" class="mt-4 space-y-4">
                    @csrf
                    <textarea name="body" rows="5" required maxlength="10000" class="textarea"
                        placeholder="{{ __('app.tickets.note_placeholder') }}">{{ old('body') }}</textarea>
                    @error('body')<p class="error-text">{{ $message }}</p>@enderror
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-1.5 text-[11px] text-slate-500">
                            <x-icon name="lock" class="h-3.5 w-3.5" /> {{ __('app.tickets.note_hint') }}
                        </p>
                        <button type="submit" class="btn bg-amber-500 text-white hover:bg-amber-600">
                            <x-icon name="check" class="h-4 w-4" />
                            {{ __('app.tickets.save_note') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="min-w-0 space-y-5">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('app.tickets.details') }}</h2>
                <form method="POST" action="{{ route('app.tickets.update', $ticket) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="ticket-status" class="label text-xs">{{ __('fields.status') }}</label>
                        <select id="ticket-status" name="status" class="select py-2 text-xs">
                            @foreach (\App\Enums\TicketStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <p class="hint">{{ __('app.tickets.status_change_hint') }}</p>
                    </div>

                    <div>
                        <label for="ticket-priority" class="label text-xs">{{ __('fields.priority') }}</label>
                        <select id="ticket-priority" name="priority" class="select py-2 text-xs">
                            @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ticket-assignee" class="label text-xs">{{ __('fields.assignee') }}</label>
                        <select id="ticket-assignee" name="assigned_to" class="select py-2 text-xs" @disabled(! $canAssign && $ticket->assigned_to !== null && ! $ticket->isAssignedTo($user))>
                            <option value="">{{ __('common.unassigned') }}</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        @unless ($canAssign)
                            <p class="hint">{{ __('app.tickets.claim_hint') }}</p>
                        @endunless
                        @error('assigned_to')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <span class="label text-xs">{{ __('fields.tags') }}</span>
                        @if ($tags->isEmpty())
                            <p class="hint">{{ __('app.tickets.no_tags') }}</p>
                        @else
                            <div class="max-h-40 space-y-1.5 overflow-y-auto rounded-xl border border-slate-200 p-2.5">
                                @foreach ($tags as $tag)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-1.5 py-1 text-xs text-slate-600 hover:bg-slate-50">
                                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                            @checked($ticket->tags->contains($tag->id))
                                            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="badge {{ $tag->badgeClasses() }} !py-0">{{ $tag->name() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <p class="hint">{{ __('app.tickets.tag_help') }}</p>
                    </div>

                    <button type="submit" class="btn-primary w-full btn-sm">
                        <x-icon name="check" class="h-4 w-4" /> {{ __('app.tickets.save') }}
                    </button>
                </form>
            </section>

            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('app.tickets.sla_title') }}</h2>
                    <x-sla-badge :state="$ticket->slaState()" />
                </div>
                <dl class="mt-4 space-y-4 text-xs">
                    <div>
                        <dt class="text-slate-500">{{ __('app.tickets.first_response_target') }}</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ hd_datetime($ticket->first_response_due_at) }}</dd>
                        <dd class="mt-1 text-[11px] {{ $ticket->first_responded_at ? 'text-teal-700' : 'text-slate-400' }}">
                            {{ $ticket->first_responded_at ? __('app.tickets.first_response_actual').': '.hd_datetime($ticket->first_responded_at) : '—' }}
                        </dd>
                    </div>
                    <div class="border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">{{ __('app.tickets.resolution_target') }}</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ hd_datetime($ticket->resolution_due_at) }}</dd>
                        <dd class="mt-1 text-[11px] {{ $ticket->resolved_at ? 'text-teal-700' : 'text-slate-400' }}">
                            {{ $ticket->resolved_at ? __('app.tickets.resolved_actual').': '.hd_datetime($ticket->resolved_at) : '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('app.tickets.requester_info') }}</h2>
                <div class="mt-3 flex items-center gap-3">
                    <x-avatar :name="$ticket->requester_name" size="lg" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $ticket->requester_name }}</p>
                        <p class="truncate text-xs text-slate-500" dir="ltr">{{ $ticket->requester_email }}</p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">{{ __('app.tickets.channel') }}</span>
                        <span class="font-semibold text-slate-700">{{ $ticket->channel->label() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">{{ __('app.tickets.created_by') }}</span>
                        <span class="font-semibold text-slate-700">{{ $ticket->creator?->name ?? __('app.tickets.customer_reply') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">{{ __('app.tickets.last_reply') }}</span>
                        <span class="font-semibold text-slate-700">{{ hd_datetime($ticket->last_reply_at) }}</span>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-2" x-data="copyButton" data-copy-text="{{ $ticket->requester_email }}">
                        <x-icon name="mail" class="h-4 w-4 shrink-0 text-slate-400" />
                        <span class="min-w-0 flex-1 truncate text-[11px] text-slate-600" dir="ltr">{{ $ticket->requester_email }}</span>
                        <button type="button" @click="copy()" class="rounded-lg p-1.5 text-slate-500 hover:bg-white hover:text-brand-700"
                            aria-label="{{ __('app.tickets.requester_email_copy') }}">
                            <x-icon name="copy" class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-2" x-data="copyButton" data-copy-text="{{ $trackingUrl }}">
                        <div class="flex items-center gap-2">
                            <x-icon name="lock" class="h-4 w-4 shrink-0 text-teal-600" />
                            <span class="flex-1 text-[11px] font-semibold text-slate-600">{{ __('app.tickets.public_link') }}</span>
                            <button type="button" @click="copy()" class="btn btn-secondary btn-sm !px-2 !py-1">
                                <x-icon name="copy" class="h-3.5 w-3.5" />
                                <span x-show="!copied">{{ __('app.tickets.copy_link') }}</span><span x-show="copied" x-cloak>{{ __('common.actions.copied') }}</span>
                            </button>
                        </div>
                        <p class="mt-1.5 text-[10px] leading-4 text-slate-400">{{ __('app.tickets.public_link_hint') }}</p>
                    </div>
                </div>
                <p class="mt-3 flex items-start gap-1.5 text-[10px] leading-4 text-slate-400">
                    <x-icon name="shield" class="mt-0.5 h-3 w-3 shrink-0" /> {{ __('app.tickets.signed_link_hint') }}
                </p>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-slate-900">{{ __('portal.ticket.satisfaction_title') }}</h2>
                @if ($ticket->csat)
                    <div class="mt-3 flex items-center gap-1 text-amber-500">
                        @for ($i = 1; $i <= 5; $i++)
                            <x-icon name="star" class="h-4 w-4 {{ $i <= $ticket->csat->score ? 'fill-amber-400' : 'text-slate-200' }}" />
                        @endfor
                        <span class="ms-2 text-xs font-semibold text-slate-600">{{ __('app.tickets.csat_score', ['score' => $ticket->csat->score]) }}</span>
                    </div>
                    @if ($ticket->csat->comment)
                        <p class="mt-2 rounded-lg bg-slate-50 p-2.5 text-xs leading-5 text-slate-600">{{ $ticket->csat->comment }}</p>
                    @endif
                @elseif ($ticket->isResolved() && $csatUrl)
                    <p class="mt-2 text-xs text-slate-500">{{ __('app.tickets.no_csat') }}</p>
                    <div class="mt-3 rounded-xl bg-slate-50 p-2" x-data="copyButton" data-copy-text="{{ $csatUrl }}">
                        <div class="flex items-center gap-2">
                            <x-icon name="star" class="h-4 w-4 shrink-0 text-amber-500" />
                            <span class="flex-1 text-[11px] font-semibold text-slate-600">{{ __('app.tickets.csat_link') }}</span>
                            <button type="button" @click="copy()" class="btn btn-secondary btn-sm !px-2 !py-1">
                                <x-icon name="copy" class="h-3.5 w-3.5" />
                                <span x-show="!copied">{{ __('common.actions.copy') }}</span><span x-show="copied" x-cloak>{{ __('common.actions.copied') }}</span>
                            </button>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-xs text-slate-500">{{ __('app.tickets.csat_pending') }}</p>
                @endif
            </section>

            @can('delete', $ticket)
                <section class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5">
                    <h2 class="text-sm font-bold text-rose-800">{{ __('app.tickets.danger_zone') }}</h2>
                    <p class="mt-1 text-[11px] leading-5 text-rose-700">{{ __('app.tickets.delete_warning') }}</p>
                    <form method="POST" action="{{ route('app.tickets.destroy', $ticket) }}" class="mt-3"
                        x-data="confirmSubmit" data-confirm="{{ __('app.tickets.delete_confirm') }}" @submit="submit($event)">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm w-full">
                            <x-icon name="trash" class="h-4 w-4" /> {{ __('app.tickets.delete') }}
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
@endsection
