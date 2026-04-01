<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 26px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            background: #1d4ed8;
            border-radius: 10px;
            color: #ffffff;
            padding: 18px 20px;
        }

        .title {
            font-size: 19px;
            font-weight: 700;
        }

        .subtitle {
            margin-top: 4px;
            font-size: 12px;
            opacity: 0.92;
        }

        .meta {
            margin-top: 6px;
            font-size: 10px;
            opacity: 0.82;
        }

        .score-box {
            text-align: right;
            white-space: nowrap;
        }

        .score-value {
            font-size: 44px;
            font-weight: 800;
            line-height: 1;
        }

        .score-label {
            font-size: 11px;
            opacity: 0.9;
        }

        .section {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .muted {
            color: #6b7280;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .skill {
            display: inline-block;
            margin: 3px 5px 0 0;
        }

        .cols {
            width: 100%;
            border-collapse: collapse;
        }

        .cols td {
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .panel {
            border-radius: 8px;
            padding: 10px;
        }

        .panel-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }

        .panel-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .list {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .list li {
            margin: 0 0 6px 0;
        }

        .spacer {
            height: 6px;
        }
    </style>
</head>

<body>
    @php
        $analysis = $application->aiAnalysis;
        $score = (int) ($analysis?->match_score ?? 0);
        $recommendation = (string) ($analysis?->recommendation ?? 'maybe');
        $recommendationLabel = strtoupper(str_replace('_', ' ', $recommendation));

        $recommendationClass = match ($recommendation) {
            'strong_yes' => 'badge-success',
            'yes' => 'badge-info',
            'maybe' => 'badge-warning',
            default => 'badge-danger',
        };

        $matched = is_array($analysis?->matched_skills) ? $analysis->matched_skills : [];
        $missing = is_array($analysis?->missing_skills) ? $analysis->missing_skills : [];

        $iqRaw = $analysis?->interview_questions ?? [];
        $iqArray = is_array($iqRaw) ? $iqRaw : [];
        $groupedQuestions = collect($iqArray)
            ->filter(fn($q) => is_array($q) && !empty($q['type']) && !empty($q['question']))
            ->groupBy('type');
    @endphp

    <table class="header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="title">AI Candidate Evaluation Report</div>
                <div class="subtitle">{{ $application->candidate->name }} | {{ $application->jobListing->title }}</div>
                <div class="meta">Application #{{ $application->id }} | Generated {{ now()->format('d M Y, H:i') }}</div>
            </td>
            <td class="score-box">
                <div class="score-value">{{ $score }}</div>
                <div class="score-label">Match Score / 100</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Final Recommendation</div>
        <span class="badge {{ $recommendationClass }}">{{ $recommendationLabel }}</span>
        <span class="muted" style="margin-left:8px;">Tokens used: {{ (int) ($analysis?->tokens_used ?? 0) }}</span>
        @if(!empty($application->recruiter_notes))
            <div class="spacer"></div>
            <div><strong>Recruiter Notes:</strong> {{ $application->recruiter_notes }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">AI Summary</div>
        <div>{{ $analysis?->reasoning ?: 'No AI reasoning available for this application yet.' }}</div>
    </div>

    <div class="section">
        <table class="cols">
            <tr>
                <td>
                    <div class="section-title">Strengths</div>
                    <div class="panel panel-success">{{ $analysis?->strengths ?: 'No strengths listed.' }}</div>
                </td>
                <td>
                    <div class="section-title">Gaps</div>
                    <div class="panel panel-danger">{{ $analysis?->weaknesses ?: 'No gaps listed.' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Skill Coverage</div>

        <div><strong>Matched Skills</strong></div>
        @forelse($matched as $skill)
            <span class="badge badge-success skill">{{ $skill }}</span>
        @empty
            <span class="muted">No matched skills listed.</span>
        @endforelse

        <div class="spacer"></div>
        <div><strong>Missing Skills</strong></div>
        @forelse($missing as $skill)
            <span class="badge badge-danger skill">{{ $skill }}</span>
        @empty
            <span class="muted">No missing skills listed.</span>
        @endforelse
    </div>

    <div class="section">
        <div class="section-title">Suggested Interview Questions</div>

        @foreach([
            'technical' => 'Technical',
            'behavioural' => 'Behavioural',
            'gap_probing' => 'Gap Probing',
        ] as $type => $label)
            @php $items = $groupedQuestions->get($type, collect()); @endphp
            @if($items->isNotEmpty())
                <div style="margin-top:6px;"><strong>{{ $label }}</strong></div>
                <ol class="list">
                    @foreach($items as $q)
                        <li>{{ data_get($q, 'question') }}</li>
                    @endforeach
                </ol>
            @endif
        @endforeach

        @if($groupedQuestions->isEmpty())
            <span class="muted">No interview questions generated.</span>
        @endif
    </div>
</body>

</html>
