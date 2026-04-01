# NovaHire Phase 2 (Score-Based Processing Engine)

Phase 2 automates the flow from analysis score to decision and recruiter note.

## What is Implemented

- Score-to-decision thresholds:
  - `0..50` => `rejected`
  - `51..70` => `shortlisted`
  - `71..100` => `interview`
- Status persistence with timestamp:
  - `applications.status`
  - `applications.status_changed_at`
  - `applications.ai_score`
- OpenAI recruiter note generation with deterministic fallback
- Note storage in `application_notes` (AI source)
- Candidate status notification trigger (`ApplicationStatusChanged`) after automated decision
- Email log write to `email_logs` for phase-2 notification attempts
- Final-status protection:
  - Applications already in `offer` or `hired` are not auto-overridden

## Runtime Wiring

- Main entrypoint: `App\Jobs\ProcessCvAnalysis`
- Engine service: `App\Services\ScoreBasedProcessingEngine`
- Note generator: `App\Services\OpenAiRecruiterNoteService`

When CV analysis finishes, the Phase 2 engine is executed automatically.

## Config

Set in `.env`:

```env
OPENAI_API_KEY=...
AI_NOTE_MODEL=gpt-4o-mini
AI_NOTE_MAX_TOKENS=420
PHASE2_REJECT_MAX_SCORE=50
PHASE2_SHORTLIST_MAX_SCORE=70
```

Email provider remains Resend for later email phases:

```env
MAIL_MAILER=resend
RESEND_KEY=...
```

## Manual Command

Run pipeline manually for an application:

```bash
php artisan recruitment:phase2:process 123
php artisan recruitment:phase2:process 123 --score=71
```

## Tests

Covered in:

- `tests/Feature/Candidate/ScoreBasedProcessingEngineTest.php`

Includes boundary checks for scores `50`, `51`, `70`, `71`, plus final-status lock behavior.
