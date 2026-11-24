@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-14 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <h3 class="mt-4 text-sm font-bold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
