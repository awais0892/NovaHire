# NovaHire Phase 3 (Automated Email Dispatch)

Phase 3 now sends branded candidate decision emails automatically after score-based decisions.

## Implemented

- Three branded HTML candidate templates:
  - `resources/views/emails/candidate/rejection.blade.php`
  - `resources/views/emails/candidate/shortlist.blade.php`
  - `resources/views/emails/candidate/interview.blade.php`
- AI note embedded dynamically in each template
- Decision email queue service:
  - `App\Services\CandidateDecisionEmailService`
- Retryable sender job:
  - `App\Jobs\SendDecisionEmailJob`
  - `tries=3`, backoff from config (`60, 180, 540` seconds)
- Full tracking in `email_logs` (`queued`, `sent`, `failed`) with metadata
- HR daily digest command:
  - `php artisan recruitment:emails:digest`
  - scheduled daily via `PHASE3_DIGEST_SEND_TIME`

## Trigger Path

`ProcessCvAnalysis` now:
1. Runs score engine (Phase 2)
2. Sends in-app status notification
3. Queues Phase 3 candidate decision email job

## Commands

```bash
php artisan recruitment:emails:digest
php artisan recruitment:emails:digest --date=2026-03-30
```

## Env

```env
MAIL_MAILER=resend
RESEND_KEY=...
PHASE3_DIGEST_SEND_TIME=07:30
PHASE3_EMAIL_DEDUPE_MINUTES=30
```
