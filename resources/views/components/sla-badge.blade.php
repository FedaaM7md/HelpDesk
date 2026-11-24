@props(['state' => 'none', 'label' => null])

@php
    $map = [
        'breached' => ['class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'icon' => 'alert', 'text' => __('portal.ticket.breached')],
        'at_risk' => ['class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'icon' => 'clock', 'text' => __('portal.ticket.at_risk')],
        'on_track' => ['class' => 'bg-teal-50 text-teal-700 ring-teal-200', 'icon' => 'check', 'text' => __('portal.ticket.on_track')],
        'met' => ['class' => 'bg-teal-50 text-teal-700 ring-teal-200', 'icon' => 'check', 'text' => __('portal.ticket.met')],
        'none' => ['class' => 'bg-slate-100 text-slate-600 ring-slate-200', 'icon' => 'clock', 'text' => __('portal.ticket.none')],
    ];
    $style = $map[$state] ?? $map['none'];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$style['class']]) }}>
    <x-icon :name="$style['icon']" class="h-3.5 w-3.5" />
    {{ $label ?? $style['text'] }}
</span>
