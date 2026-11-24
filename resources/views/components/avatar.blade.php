@props(['user' => null, 'name' => null, 'size' => 'md'])

@php
    $displayName = $name ?? $user?->name ?? '?';
    $initials = $user?->initials() ?? mb_strtoupper(mb_substr($displayName, 0, 2));
    $sizes = [
        'sm' => 'h-8 w-8 text-[11px]',
        'md' => 'h-10 w-10 text-xs',
        'lg' => 'h-12 w-12 text-sm',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-800 ring-1 ring-brand-200 '.($sizes[$size] ?? $sizes['md'])]) }}
    title="{{ $displayName }}">
    {{ $initials }}
</span>
