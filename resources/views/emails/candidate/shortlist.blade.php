<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shortlist Update</title>
</head>
<body style="margin:0;padding:0;background:#f5f7ff;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7ff;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1d4ed8,#1e293b);padding:20px 24px;color:#ffffff;">
                            <div style="font-size:18px;font-weight:700;">NovaHire</div>
                            <div style="margin-top:4px;font-size:12px;opacity:.9;">Shortlist Progress</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px;font-size:14px;">Hi {{ $candidate?->name ?? 'Candidate' }},</p>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
                                Great news. Your application for <strong>{{ $job?->title ?? 'this role' }}</strong> has been shortlisted.
                            </p>

                            @if(!empty($note))
                                <div style="margin:16px 0;padding:14px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;">
                                    <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#1e40af;">AI Recruiter Note</div>
                                    <p style="margin:8px 0 0;font-size:13px;line-height:1.6;color:#1e3a8a;">{{ $note }}</p>
                                </div>
                            @endif

                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
                                Our team will review the shortlist and contact you with next steps. Keep your profile and documents up to date in your candidate dashboard.
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

