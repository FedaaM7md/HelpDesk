<x-mail-layout :title="__('mail.created_subject', ['reference' => $ticket->reference])">
    <h1 style="margin:0 0 14px;font-size:22px;line-height:32px;color:#0f172a;">{{ __('mail.created_title') }}</h1>
    <p style="margin:0 0 10px;font-size:14px;line-height:24px;">{{ __('mail.greeting', ['name' => $ticket->requester_name]) }}</p>
    <p style="margin:0 0 20px;font-size:14px;line-height:24px;color:#475569;">
        {{ __('mail.created_intro', ['reference' => $ticket->reference]) }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:0 0 22px;">
        <tr>
            <td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.reference') }}</td>
            <td style="padding:14px 16px;font-size:13px;font-weight:700;color:#1E3A8A;text-align:end;">{{ $ticket->reference }}</td>
        </tr>
        <tr>
            <td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.status') }}</td>
            <td style="padding:14px 16px;font-size:13px;text-align:end;">{{ $ticket->status->label() }}</td>
        </tr>
        <tr>
            <td style="padding:14px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.priority') }}</td>
            <td style="padding:14px 16px;font-size:13px;text-align:end;">{{ $ticket->priority->label() }}</td>
        </tr>
        <tr>
            <td style="padding:14px 16px;font-size:13px;color:#64748b;">{{ __('fields.subject') }}</td>
            <td style="padding:14px 16px;font-size:13px;text-align:end;">{{ $ticket->subject }}</td>
        </tr>
    </table>

    <p style="margin:0 0 22px;text-align:center;">
        <a href="{{ $trackingUrl }}" style="display:inline-block;background:#1E3A8A;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 26px;border-radius:10px;">
            {{ __('mail.cta_track') }}
        </a>
    </p>
    <p style="margin:0;font-size:12px;line-height:20px;color:#94a3b8;">
        {{ __('portal.ticket.signed_note') }}
    </p>
</x-mail-layout>
