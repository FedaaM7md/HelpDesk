<x-mail-layout :title="__('mail.reply_subject', ['reference' => $ticket->reference])">
    <h1 style="margin:0 0 14px;font-size:22px;line-height:32px;color:#0f172a;">{{ __('mail.reply_title') }}</h1>
    <p style="margin:0 0 10px;font-size:14px;line-height:24px;">{{ __('mail.greeting', ['name' => $ticket->requester_name]) }}</p>
    <p style="margin:0 0 20px;font-size:14px;line-height:24px;color:#475569;">
        {{ __('mail.reply_intro', ['reference' => $ticket->reference]) }}
    </p>

    <div style="border-inline-start:4px solid #0D9488;background:#f0fdfa;border-radius:10px;padding:16px 18px;margin:0 0 22px;">
        <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#0f766e;">{{ __('mail.latest_reply') }} · {{ $ticketMessage->author_name }}</p>
        <div style="font-size:14px;line-height:24px;color:#334155;">{!! nl2br(e($ticketMessage->body)) !!}</div>
    </div>

    <p style="margin:0 0 22px;text-align:center;">
        <a href="{{ $trackingUrl }}" style="display:inline-block;background:#1E3A8A;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 26px;border-radius:10px;">
            {{ __('mail.cta_track') }}
        </a>
    </p>
    <p style="margin:0;font-size:12px;line-height:20px;color:#94a3b8;">
        {{ __('portal.ticket.reply_hint') }}
    </p>
</x-mail-layout>
