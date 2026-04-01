<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Email Digest</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:18px 22px;background:#0f172a;color:#fff;">
                            <div style="font-size:18px;font-weight:700;">NovaHire</div>
                            <div style="margin-top:4px;font-size:12px;opacity:.9;">Daily Email Dispatch Digest</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px;">
                            <p style="margin:0 0 10px;font-size:14px;">
                                Company: <strong>{{ $company->name }}</strong>
                            </p>
                            <p style="margin:0 0 18px;font-size:13px;color:#475569;">
                                Window: {{ $from->timezone(config('recruitment.uk_timezone', 'Europe/London'))->format('d M Y H:i') }}
                                to
                                {{ $to->timezone(config('recruitment.uk_timezone', 'Europe/London'))->format('d M Y H:i') }}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;font-weight:600;">Total Sent</td>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;">{{ $summary['sent'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;font-weight:600;">Total Failed</td>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;">{{ $summary['failed'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;font-weight:600;">Queued</td>
                                    <td style="padding:10px;border:1px solid #e2e8f0;font-size:13px;">{{ $summary['queued'] ?? 0 }}</td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0;font-size:12px;color:#64748b;">
                                Breakdown by template:
                                @foreach(($summary['templates'] ?? []) as $template => $count)
                                    <span style="display:inline-block;margin-right:8px;">{{ $template }}: {{ $count }}</span>
                                @endforeach
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

