@props(['compact' => false, 'onDark' => false])

<span class="inline-flex items-center gap-3">
    <svg viewBox="0 0 40 40" aria-hidden="true" class="shrink-0 {{ $compact ? 'h-8 w-8' : 'h-10 w-10' }}">
        {{-- Two overlapping speech bubbles leaning into a subtle heart. --}}
        <path d="M5 9h13a8 8 0 0 1 8 8v8a8 8 0 0 1-8 8h-3.5l-3.2 6.2v-6.2H9a8 8 0 0 1-8-8v-8a8 8 0 0 1 8-8Z" fill="#0F5C55" />
        <path d="M15 10h13a7 7 0 0 1 7 7v7a7 7 0 0 1-7 7h-2.4l-2.6 5.4v-5.4h-2a7 7 0 0 1-7-7v-7a7 7 0 0 1 7-7Z" fill="#F26B5B" />
        <circle cx="12.2" cy="18" r="1.35" fill="#FFFFFF" />
        <circle cx="16.6" cy="18" r="1.35" fill="#FFFFFF" />
        <circle cx="12.2" cy="23" r="1.35" fill="#FFFFFF" />
    </svg>
    <span class="text-base font-bold tracking-tight {{ $onDark ? 'text-white' : 'text-ink' }}">
        {{ __('common.app_name') }}
    </span>
</span>
