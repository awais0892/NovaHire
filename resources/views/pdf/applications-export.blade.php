<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NovaHire Applications Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .meta {
            margin-top: 6px;
            color: #4b5563;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">NovaHire Applications Export</h1>
        <p class="meta">
            Generated: {{ $generatedAt->format('d M Y H:i') }}
            | Total: {{ $applications->count() }}
            | Search: {{ $filters['q'] !== '' ? $filters['q'] : 'All' }}
            | Status: {{ $filters['status'] !== '' ? ucfirst($filters['status']) : 'All' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Candidate</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>AI Score</th>
                <th>Applied</th>
                <th>Interview</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                @php $interview = $application->upcomingInterview; @endphp
                <tr>
                    <td>{{ $application->id }}</td>
                    <td>{{ $application->candidate->name ?? '-' }}</td>
                    <td>{{ $application->candidate->email ?? '-' }}</td>
                    <td>{{ $application->jobListing->title ?? '-' }}</td>
                    <td>{{ ucfirst((string) $application->status) }}</td>
                    <td>{{ is_null($application->ai_score) ? '-' : $application->ai_score . '%' }}</td>
                    <td>{{ $application->created_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>
                        @if($interview)
                            {{ $interview->starts_at?->timezone($interview->timezone)->format('d M Y H:i') ?? '-' }}
                            ({{ strtoupper((string) $interview->mode) }})
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No applications matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
