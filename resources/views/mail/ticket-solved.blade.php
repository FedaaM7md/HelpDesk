<x-mail-layout :title="__('mail.solved_subject', ['reference' => $ticket->reference])">
    <h1 style="margin:0 0 14px;font-size:22px;line-height:32px;color:#0f172a;">{{ __('mail.solved_title') }}</h1>
    <p style="margin:0 0 10px;font-size:14px;line-height:24px;">{{ __('mail.greeting', ['name' => $ticket->requester_name]) }}</p>
    <p style="margin:0 0 20px;font-size:14px;line-height:24px;color:#475569;">
        {{ __('mail.solved_intro', ['reference' => $ticket->reference]) }}
    </p>

    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:18px;margin:0 0 22px;text-align:center;">
        <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#92400e;">{{ __('mail.cta_rate') }}</p>
        <p style="margin:0 0 14px;font-size:12px;line-height:20px;color:#a16207;">{{ __('mail.rate_hint') }}</p>
        <a href="{{ $csatUrl }}" style="display:inline-block;background:#0D9488;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:11px 24px;border-radius:10px;">
            {{ __('portal.csat.title') }}
        </a>
    </div>

    <p style="margin:0 0 8px;font-size:12px;line-height:20px;color:#64748b;">{{ __('mail.need_help') }}</p>
    <a href="{{ URL::temporarySignedRoute('portal.ticket.show', now()->addDays((int) config('helpdesk.tracking_link_days', 90)), ['ticket' => $ticket->reference]) }}"
        style="font-size:12px;color:#1E3A8A;font-weight:700;text-decoration:underline;">
        {{ __('mail.cta_track') }}
    </a>
</x-mail-layout>
