@extends('layouts.app')

@section('title', __('app.users.title'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-600">{{ __('common.nav.admin_group') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ __('app.users.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.users.subtitle') }}</p>
        </div>
        <a href="{{ route('app.users.create') }}" class="btn-primary btn-sm shrink-0">
            <x-icon name="plus" class="h-4 w-4" /> {{ __('app.users.new') }}
        </a>
    </div>

    <form method="GET" action="{{ route('app.users.index') }}" class="card mt-6 flex flex-col gap-3 p-4 sm:flex-row">
        <div class="relative flex-1">
            <label for="user-search" class="sr-only">{{ __('common.actions.search') }}</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input id="user-search" type="search" name="q" value="{{ $term }}" class="input ps-9 py-2 text-xs"
                placeholder="{{ __('app.users.search_placeholder') }}">
        </div>
        <select name="role" class="select w-full py-2 text-xs sm:w-48" aria-label="{{ __('fields.role') }}">
            <option value="">{{ __('common.all') }}</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary btn-sm">{{ __('common.actions.filter') }}</button>
        <a href="{{ route('app.users.index') }}" class="btn-secondary btn-sm">{{ __('common.actions.reset') }}</a>
    </form>

    <div class="table-wrap mt-4 hidden md:block">
        <table class="min-w-full text-sm">
            <thead class="table-head">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('app.users.name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.users.email') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.users.role') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.users.status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('app.users.last_login') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('app.users.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $member)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :user="$member" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-slate-800">
                                        {{ $member->name }}
                                        @if ($member->id === auth()->id())
                                            <span class="ms-1 rounded bg-slate-100 px-1 py-0.5 text-[9px] font-semibold text-slate-500">{{ __('app.users.you') }}</span>
                                        @endif
                                        @if ($member->isDemo())
                                            <span class="ms-1 rounded bg-brand-100 px-1 py-0.5 text-[9px] font-semibold text-brand-700">{{ __('common.demo') }}</span>
                                        @endif
                                    </p>
                                    <p class="truncate text-[10px] text-slate-400">{{ $member->job_title ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600" dir="ltr">{{ $member->email }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $member->role->badgeClasses() }}">{{ $member->role->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $member->is_active ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                                {{ $member->is_active ? __('app.users.active') : __('app.users.inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-[11px] text-slate-500">{{ $member->last_login_at ? hd_datetime($member->last_login_at) : __('app.users.never_signed_in') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('app.users.edit', $member) }}" class="btn-secondary btn-sm">{{ __('app.users.edit') }}</a>
                                @if ($member->id !== auth()->id() && ! $member->isDemo())
                                    <form method="POST" action="{{ route('app.users.destroy', $member) }}" x-data="confirmSubmit" data-confirm="{{ __('app.users.delete_confirm') }}" @submit="submit($event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50" aria-label="{{ __('common.actions.delete') }}">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('app.users.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-pagination :paginator="$users" />
    </div>

    {{-- Stacked cards on phones --}}
    <div class="mt-4 space-y-3 md:hidden">
        @forelse ($users as $member)
            <div class="card p-4">
                <div class="flex items-start gap-3">
                    <x-avatar :user="$member" size="md" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="ms-1 rounded bg-slate-100 px-1 py-0.5 text-[9px] font-semibold text-slate-500">{{ __('app.users.you') }}</span>
                            @endif
                            @if ($member->isDemo())
                                <span class="ms-1 rounded bg-brand-100 px-1 py-0.5 text-[9px] font-semibold text-brand-700">{{ __('common.demo') }}</span>
                            @endif
                        </p>
                        <p class="truncate text-[11px] text-slate-400">{{ $member->job_title ?? '—' }}</p>
                        <p class="mt-1 truncate text-[11px] text-slate-500" dir="ltr">{{ $member->email }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="badge {{ $member->role->badgeClasses() }}">{{ $member->role->label() }}</span>
                    <span class="badge {{ $member->is_active ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                        {{ $member->is_active ? __('app.users.active') : __('app.users.inactive') }}
                    </span>
                    <span class="ms-auto text-[10px] text-slate-400">{{ $member->last_login_at ? hd_datetime($member->last_login_at) : __('app.users.never_signed_in') }}</span>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <a href="{{ route('app.users.edit', $member) }}" class="btn-secondary btn-sm flex-1">{{ __('app.users.edit') }}</a>
                    @if ($member->id !== auth()->id() && ! $member->isDemo())
                        <form method="POST" action="{{ route('app.users.destroy', $member) }}" x-data="confirmSubmit" data-confirm="{{ __('app.users.delete_confirm') }}" @submit="submit($event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm !text-rose-600 hover:!bg-rose-50" aria-label="{{ __('common.actions.delete') }}">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <x-empty-state icon="users" :title="__('app.users.empty')" />
        @endforelse
        <x-pagination :paginator="$users" />
    </div>
@endsection
