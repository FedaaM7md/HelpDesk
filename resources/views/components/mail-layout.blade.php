@props(['title' => null])

@php($lang = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('common.app_name') }}</title>
</head>
<body style="margin:0;padding:0;background:#fffdf9;font-family:'Almarai','Segoe UI',Tahoma,Arial,sans-serif;color:#1f2a2a;">
    <div style="display:none;max-height:0;overflow:hidden;">{{ $title }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fffdf9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0f5c55;padding:22px 28px;">
                            <span style="font-size:20px;font-weight:700;color:#ffffff;">{{ __('common.app_name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#fffdf9;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:20px;">
                            {{ __('mail.footer') }}<br>
                            <span style="color:#94a3b8;">{{ __('common.app_name') }} · {{ date('Y') }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
