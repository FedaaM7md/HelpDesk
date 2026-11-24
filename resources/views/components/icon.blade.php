@props(['name' => 'dot'])

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home')
            <path d="M3 10.5 12 3l9 7.5" />
            <path d="M5 9.5V21h14V9.5" />
            <path d="M9.5 21v-6h5v6" />
            @break
        @case('ticket')
            <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2.5 2.5 0 0 0 0 5v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2.5 2.5 0 0 0 0-5Z" />
            <path d="M12 5v3M12 16v3" stroke-dasharray="2 2.5" />
            @break
        @case('chat')
            <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H11l-5 4v-4H6.5A2.5 2.5 0 0 1 4 13.5Z" />
            <path d="M8 9h8M8 12h5" />
            @break
        @case('book')
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5Z" />
            <path d="M4 5.5v15A2.5 2.5 0 0 0 6.5 23H20v-5" />
            <path d="M8 7h8M8 11h6" />
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3 20c0-3.1 2.7-5.5 6-5.5s6 2.4 6 5.5" />
            <path d="M16 8.5a3 3 0 1 1 2.5 4.7M17 14.8c2.4.4 4 2.3 4 5.2" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20.5c0-3.4 3.1-6 7-6s7 2.6 7 6" />
            @break
        @case('tag')
            <path d="M3 11.5V4h7.5l9.8 9.8a2 2 0 0 1 0 2.8l-4.7 4.7a2 2 0 0 1-2.8 0Z" />
            <circle cx="7.5" cy="8" r="1.4" />
            @break
        @case('folder')
            <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2.5h7.5A2.5 2.5 0 0 1 21 10v7a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17Z" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7v5.2l3.4 2" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z" />
            @break
        @case('logout')
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3" />
            <path d="M10 8l-4 4 4 4M6 12h9" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4.5 4.5" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('arrow-left')
            <path d="M20 12H4M10 6l-6 6 6 6" />
            @break
        @case('arrow-right')
            <path d="M4 12h16M14 6l6 6-6 6" />
            @break
        @case('paperclip')
            <path d="m9 12.5 5.8-5.8a3.2 3.2 0 0 1 4.5 4.5l-7.5 7.5a5 5 0 0 1-7-7l7.2-7.2" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7" />
            @break
        @case('x')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('alert')
            <path d="M12 4 2.8 20h18.4Z" />
            <path d="M12 9v4.5M12 17h.01" />
            @break
        @case('star')
            <path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2.5" />
            <path d="m4 7 8 6 8-6" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M3.5 12h17M12 3.5c2.5 2.3 3.8 5.2 3.8 8.5S14.5 18.2 12 20.5C9.5 18.2 8.2 15.3 8.2 12S9.5 5.8 12 3.5Z" />
            @break
        @case('headset')
            <path d="M4 13v-1a8 8 0 0 1 16 0v1" />
            <rect x="3" y="13" width="4" height="6" rx="1.5" />
            <rect x="17" y="13" width="4" height="6" rx="1.5" />
            <path d="M20 19a4 4 0 0 1-4 3h-2" />
            @break
        @case('download')
            <path d="M12 4v10M7.5 10.5 12 15l4.5-4.5M4 20h16" />
            @break
        @case('copy')
            <rect x="9" y="9" width="11" height="11" rx="2" />
            <path d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" />
            @break
        @case('shield')
            <path d="M12 3 5 6v5.5c0 4.4 2.8 7.7 7 9.5 4.2-1.8 7-5.1 7-9.5V6Z" />
            <path d="m9 12 2 2 4-4" />
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="16" rx="2" />
            <path d="M3.5 9.5h17M8 3v4M16 3v4" />
            @break
        @case('filter')
            <path d="M3 5h18l-7 8v5l-4 2v-7Z" />
            @break
        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6" />
            @break
        @case('pencil')
            <path d="m4 20 4.5-1 10-10a2.5 2.5 0 0 0-3.5-3.5l-10 10Z" />
            <path d="m13.5 6.5 4 4" />
            @break
        @case('eye')
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('send')
            <path d="m21 3-9.5 18-2.2-7.3L2 11.5Z" />
            <path d="M21 3 9.3 13.7" />
            @break
        @case('inbox')
            <path d="M4 4h16v16H4z" />
            <path d="M4 13h4l2 3h4l2-3h4" />
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="10" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break
        @case('refresh')
            <path d="M20 11a8 8 0 0 0-14.5-4L3 10M3 5v5h5M4 13a8 8 0 0 0 14.5 4L21 14m0 5v-5h-5" />
            @break
        @case('external')
            <path d="M14 4h6v6M20 4l-9 9" />
            <path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5" />
            @break
        @default
            <circle cx="12" cy="12" r="3" />
    @endswitch
</svg>
