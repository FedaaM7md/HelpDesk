@php($p = $prefix ?? 'd')

<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
    <div class="xl:col-span-4">
        <label for="{{ $p }}-q" class="sr-only">{{ __('common.actions.search') }}</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input id="{{ $p }}-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input ps-9 py-2 text-xs"
                placeholder="{{ __('app.tickets.search_placeholder') }}">
        </div>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-status" class="sr-only">{{ __('fields.status') }}</label>
        <select id="{{ $p }}-status" name="status" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_statuses') }}</option>
            <option value="unresolved" @selected($currentStatus === 'unresolved')>{{ __('app.tickets.unresolved_tab') }}</option>
            @foreach (\App\Enums\TicketStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-priority" class="sr-only">{{ __('fields.priority') }}</label>
        <select id="{{ $p }}-priority" name="priority" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_priorities') }}</option>
            @foreach (\App\Enums\TicketPriority::cases() as $priority)
                <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-category" class="sr-only">{{ __('fields.category') }}</label>
        <select id="{{ $p }}-category" name="category" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name() }}</option>
            @endforeach
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-assignee" class="sr-only">{{ __('fields.assignee') }}</label>
        <select id="{{ $p }}-assignee" name="assignee" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_agents') }}</option>
            <option value="me" @selected(($filters['assignee'] ?? '') === 'me')>{{ __('common.me') }}</option>
            <option value="unassigned" @selected(($filters['assignee'] ?? '') === 'unassigned')>{{ __('common.unassigned') }}</option>
            @foreach ($agents as $agent)
                <option value="{{ $agent->id }}" @selected((string) ($filters['assignee'] ?? '') === (string) $agent->id)>{{ $agent->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-tag" class="sr-only">{{ __('fields.tags') }}</label>
        <select id="{{ $p }}-tag" name="tag" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_tags') }}</option>
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}" @selected((string) ($filters['tag'] ?? '') === (string) $tag->id)>{{ $tag->name() }}</option>
            @endforeach
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-sla" class="sr-only">{{ __('app.tickets.sla') }}</label>
        <select id="{{ $p }}-sla" name="sla" class="select py-2 text-xs">
            <option value="">{{ __('app.tickets.all_sla') }}</option>
            <option value="breached" @selected(($filters['sla'] ?? '') === 'breached')>{{ __('app.tickets.sla_breached') }}</option>
        </select>
    </div>

    <div class="xl:col-span-2">
        <label for="{{ $p }}-sort" class="sr-only">{{ __('fields.sort') }}</label>
        <select id="{{ $p }}-sort" name="sort" class="select py-2 text-xs">
            <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('app.tickets.sort_newest') }}</option>
            <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('app.tickets.sort_oldest') }}</option>
            <option value="updated" @selected(($filters['sort'] ?? '') === 'updated')>{{ __('app.tickets.sort_updated') }}</option>
            <option value="priority" @selected(($filters['sort'] ?? '') === 'priority')>{{ __('app.tickets.sort_priority') }}</option>
        </select>
    </div>

    <div class="flex items-center gap-2 xl:col-span-2">
        <button type="submit" class="btn-primary btn-sm flex-1">
            <x-icon name="filter" class="h-4 w-4" /> {{ __('common.actions.apply') }}
        </button>
        <a href="{{ route('app.tickets.index') }}" class="btn-secondary btn-sm">{{ __('common.actions.reset') }}</a>
    </div>
</div>
