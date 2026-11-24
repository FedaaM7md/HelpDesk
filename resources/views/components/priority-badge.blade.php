@props(['priority'])

<span {{ $attributes->merge(['class' => 'badge '.$priority->badgeClasses()]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
    {{ $priority->label() }}
</span>
