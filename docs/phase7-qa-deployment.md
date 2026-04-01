# NovaHire Phase 7 (Integration Testing, QA & Deployment)

Phase 7 closes the delivery lifecycle with production readiness checks, rollout steps, and handover.

## Automated QA Coverage Implemented

- End-to-end score chain validation for exact Phase 7 checkpoints:
  - score `30` => rejected
  - score `60` => shortlisted
  - score `85` => interview + auto slot booking
  - includes verification for decision, recruiter note, email log, candidate notification, and candidate dashboard visibility
  - file: `tests/Feature/Candidate/PhaseSevenIntegrationChainTest.php`

- Security-focused regression tests:
  - cross-company application detail access blocked
  - SQL-like input in recruiter search does not break query execution
  - candidate portal note rendering escapes script tags
  - file: `tests/Feature/Security/SecurityReviewPhaseSevenTest.php`

- Performance simulation harness:
  - command: `php artisan recruitment:phase7:simulate-load --candidates=500 --chunk=50`
  - creates candidate/application volume, queued email logs, and in-app notifications
  - optional analysis dispatch: `--dispatch-analysis`
  - command test: `tests/Feature/Performance/PhaseSevenLoadSimulationCommandTest.php`

## QA Execution Checklist

- Run full automated suite:
  - `php artisan test`

- Run focused phase checks:
  - `php artisan test tests/Feature/Candidate/PhaseSevenIntegrationChainTest.php`
  - `php artisan test tests/Feature/Security/SecurityReviewPhaseSevenTest.php`
  - `php artisan test tests/Feature/Interviews/InterviewSlotEngineTest.php`

- Run load simulation:
  - baseline: `php artisan recruitment:phase7:simulate-load --candidates=500 --chunk=50`
  - with analysis jobs queued: `php artisan recruitment:phase7:simulate-load --candidates=500 --chunk=50 --dispatch-analysis`

## HR UAT Checklist

- Recruiter dashboard:
  - pipeline list filters, sorting, and exports work
  - manual note override persists and optional resend queues email
  - status updates trigger candidate-side tracking changes

- Slot management:
  - slot generation works across weekdays
  - UK holidays are blocked by default
  - slot booking and cancellation update availability correctly

- Bulk actions:
  - bulk status update changes target records only within current company
  - cross-company records remain inaccessible

## Candidate Portal UAT Checklist

- My Applications page:
  - status badges and timeline match backend status
  - recruiter notes timeline is visible and escaped safely
  - email history section shows outbound decision messages

- Interview experience:
  - scheduled interview card shows date, timezone, mode, interviewer, and join link when available
  - invitation response updates are persisted and visible

- Notification UX:
  - bell unread count updates
  - mark single / mark all as read endpoints work

## Security Review Checklist

- SQL injection:
  - test search/filter parameters with SQL-like payloads and verify normal responses

- XSS:
  - verify recruiter notes and dynamic fields are escaped in Blade output

- Authorization:
  - verify role middleware and company ownership checks on recruiter endpoints

- Data exposure:
  - ensure no recruiter endpoint returns another company’s application payload

## Staging Deployment Checklist (Week 13)

- Environment:
  - set `APP_ENV=staging`, `APP_DEBUG=false`
  - configure `QUEUE_CONNECTION`, `BROADCAST_CONNECTION=reverb`, mail provider keys, and OpenAI key

- Release steps:
  - `php artisan migrate --force`
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - run smoke tests on auth, candidate apply flow, recruiter applications, and interview scheduling

- Sign-off:
  - record HR UAT completion
  - record candidate portal completion
  - obtain client sign-off before production promotion

## Production Deployment & Monitoring (Week 14)

- Pre-flight:
  - backup database
  - validate queue workers and Reverb process readiness
  - verify mail sender domain and API credentials

- Go-live:
  - deploy release
  - run migrations
  - restart queue workers
  - confirm critical user journeys

- Monitoring:
  - observe `storage/logs/laravel.log` for 5xx, mail transport, notification, and broadcast errors
  - monitor queue backlog and failed jobs
  - monitor interview booking and status update latency

## Post-launch HR Training & Handover

- Training pack:
  - recruiter workflow (review, override, schedule, bulk update)
  - candidate communication flow (notes, decision emails, interview updates)
  - slot and holiday management

- Handover assets:
  - Phase 1-7 docs
  - UAT sign-off sheet
  - production support contact and incident escalation path

