@php
    /*
     * THE PASSWORD-RESET EMAIL, in the farm's colours.
     *
     * Email clients strip <style> blocks, custom properties, flex and grid,
     * so this is tables and inline styles in plain hex from
     * config/gfms-brand.php - the same source the PDF reports use. It mirrors
     * the sign-in screens: the farm's mark and name, then a flat card with a
     * hairline border, one primary button, and a celery well for the note.
     *
     * $message is reserved here (Laravel puts its mail message in every mail
     * view), which is why nothing below is called that. $url and $logoUrl are
     * built from APP_URL, never the request - see
     * FortifyServiceProvider::emailRootUrl().
     *
     * @var array{name: string, address: ?string, phone: ?string} $farm
     */
    $ink = $brand['foreground'];
    $secondary = $brand['muted_foreground'];
    $font = "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>Reset your password · {{ $farm['name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: {{ $brand['background'] }}; -webkit-text-size-adjust: 100%;">
    {{-- The inbox preview line, hidden in the email itself. --}}
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Your link to choose a new password. It works for {{ $minutes }} minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: {{ $brand['background'] }};">
        <tr>
            <td align="center" style="padding: 32px 16px 40px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px;">

                    {{-- The farm's mark and name, as on the sign-in screens. --}}
                    <tr>
                        <td style="padding: 0 4px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-right: 12px; vertical-align: middle;">
                                        <img src="{{ $logoUrl }}" width="40" height="40" alt=""
                                             style="display: block; width: 40px; height: 40px; border-radius: 999px; border: 0;">
                                    </td>
                                    <td style="vertical-align: middle; font-family: {{ $font }}; font-size: 16px; line-height: 20px; font-weight: 700; color: {{ $ink }};">
                                        {{ $farm['name'] }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- The card. Flat, a hairline border, no shadow - as in the system. --}}
                    <tr>
                        <td style="background-color: {{ $brand['card'] }}; border: 1px solid {{ $brand['border'] }}; border-radius: 12px; padding: 32px 32px 28px;">
                            <p style="margin: 0 0 6px; font-family: {{ $font }}; font-size: 13px; line-height: 18px; font-weight: 600; color: {{ $secondary }};">
                                Password reset
                            </p>
                            <h1 style="margin: 0 0 14px; font-family: {{ $font }}; font-size: 24px; line-height: 30px; font-weight: 700; letter-spacing: -0.01em; color: {{ $ink }};">
                                Reset your password
                            </h1>
                            <p style="margin: 0 0 24px; font-family: {{ $font }}; font-size: 15px; line-height: 24px; color: {{ $ink }};">
                                We received a request to reset the password for
                                <strong style="font-weight: 600;">{{ $email }}</strong>.
                                If that was you, choose a new one with the button below.
                            </p>

                            {{-- A table-cell button, so it keeps its shape in Outlook too. --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
                                <tr>
                                    <td align="center" bgcolor="{{ $brand['primary'] }}" style="border-radius: 8px; background-color: {{ $brand['primary'] }};">
                                        <a href="{{ $url }}" target="_blank"
                                           style="display: inline-block; padding: 13px 24px; font-family: {{ $font }}; font-size: 15px; line-height: 18px; font-weight: 600; color: {{ $brand['primary_foreground'] }}; text-decoration: none; border-radius: 8px;">
                                            Choose a new password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- The one thing to know about the link, in a celery well. --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 20px;">
                                <tr>
                                    <td style="background-color: {{ $brand['muted'] }}; border-radius: 8px; padding: 12px 16px; font-family: {{ $font }}; font-size: 14px; line-height: 21px; color: {{ $ink }};">
                                        This link works for <strong style="font-weight: 600;">{{ $minutes }} minutes</strong>, and only once.
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 24px; font-family: {{ $font }}; font-size: 14px; line-height: 22px; color: {{ $secondary }};">
                                <strong style="font-weight: 600; color: {{ $ink }};">Didn’t ask for this?</strong>
                                You can ignore this email. Your password stays the same unless you open the link and save a new one.
                            </p>

                            <p style="margin: 0; padding-top: 20px; border-top: 1px solid {{ $brand['border'] }}; font-family: {{ $font }}; font-size: 13px; line-height: 20px; color: {{ $secondary }};">
                                Button not working? Copy this link into your browser:<br>
                                <a href="{{ $url }}" target="_blank" style="color: {{ $brand['primary'] }}; text-decoration: underline; word-break: break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Who sent it, and why. --}}
                    <tr>
                        <td style="padding: 20px 4px 0; font-family: {{ $font }}; font-size: 12px; line-height: 18px; color: {{ $secondary }};">
                            <strong style="font-weight: 600;">{{ $farm['name'] }}</strong>@if (filled($farm['address'])) · {{ $farm['address'] }}@endif
                            <br>
                            You’re getting this because someone asked to reset the password for your account on the farm’s record system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
