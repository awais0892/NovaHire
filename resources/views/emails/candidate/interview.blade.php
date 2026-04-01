<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Interview Update</title>
</head>
<body style="margin:0;padding:0;background:#f5f7ff;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7ff;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#059669,#1e293b);padding:20px 24px;color:#ffffff;">
                            <div style="font-size:18px;font-weight:700;">NovaHire</div>
                            <div style="margin-top:4px;font-size:12px;opacity:.9;">Interview Stage</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;font-size:14px;">Hi {{ $candidate?->name ?? 'Candidate' }},</p>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
                                Congratulations. Your application for <strong>{{ $job?->title ?? 'this role' }}</strong> has progressed to the interview stage.
                            </p>

                            @if(!empty($note))
                                <div style="margin:16px 0;padding:14px;border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;">
                                    <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#065f46;">AI Recruiter Note</div>
                                    <p style="margin:8px 0 0;font-size:13px;line-height:1.6;color:#064e3b;">{{ $note }}</p>
                                </div>
                            @endif

                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
                                Interview details will appear in your candidate dashboard. Please monitor your notifications and keep your availability updated.
                            </p>

                            <p style="margin:20px 0 0;font-size:13px;color:#475569;">
                                Regards,<br>
                                {{ $company?->name ?? 'NovaHire Recruiting Team' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

