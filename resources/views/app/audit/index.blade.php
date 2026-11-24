@extends('layouts.app')

@section('title', __('app.audit.title'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">{{ __('common.nav.admin_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-ink">{{ __('app.audit.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.audit.subtitle') }}</p>
        </div>
    </div>

    <div class="table-wrap mt-6 hidden md:block">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('app.audit.when') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.audit.actor') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.audit.action') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.audit.subject') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.audit.ip') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50/60">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ hd_datetime($log->created_at) }}</td>
                        <td class="px-4 py-3 text-xs font-semibold text-slate-700">{{ $log->user?->name ?? __('common.system') }}</td>
                        <td class="px-4 py-3">
                            <span class="badge bg-slate-100 text-slate-700 ring-slate-200">{{ __('app.audit.actions.'.$log->action) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            @php($subjectKey = $log->subject_type ? \Illuminate\Support\Str::snake(class_basename($log->subject_type)) : null)
                            @if ($subjectKey)
                                {{ __('app.audit.subjects.'.$subjectKey) }}@if ($log->subject_id) <span class="text-slate-400" dir="ltr">#{{ $log->subject_id }}</span>@endif
                            @else
                                —
                            @endif
                            @if (! empty($log->metadata['title'] ?? $log->metadata['email'] ?? null))
                                <span class="ms-1 text-slate-400">· {{ $log->metadata['title'] ?? $log->metadata['email'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-400" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('app.audit.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-pagination :paginator="$logs" />
    </div>

    {{-- Stacked cards on phones --}}
    <div class="mt-6 space-y-3 md:hidden">
        @forelse ($logs as $log)
            <div class="card p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="badge bg-slate-100 text-slate-700 ring-slate-200">{{ __('app.audit.actions.'.$log->action) }}</span>
                    <span class="text-[10px] text-slate-400">{{ hd_datetime($log->created_at) }}</span>
                </div>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $log->user?->name ?? __('common.system') }}</p>
                @php($subjectKey = $log->subject_type ? \Illuminate\Support\Str::snake(class_basename($log->subject_type)) : null)
                <p class="mt-1 text-[11px] text-slate-500">
                    @if ($subjectKey)
                        {{ __('app.audit.subjects.'.$subjectKey) }}@if ($log->subject_id) <span dir="ltr">#{{ $log->subject_id }}</span>@endif
                    @endif
                    @if (! empty($log->metadata['title'] ?? $log->metadata['email'] ?? null))
                        · {{ $log->metadata['title'] ?? $log->metadata['email'] }}
                    @endif
                </p>
                <p class="mt-1 text-[10px] text-slate-400" dir="ltr">{{ $log->ip_address ?? '—' }}</p>
            </div>
        @empty
            <x-empty-state icon="shield" :title="__('app.audit.empty')" />
        @endforelse
        <x-pagination :paginator="$logs" />
    </div>
@endsection
