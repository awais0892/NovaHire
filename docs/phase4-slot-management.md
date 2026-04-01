# NovaHire Phase 4 (UK Office Slot Management)

Phase 4 introduces a holiday-aware interview slot engine and recruiter slot management UI.

## What was implemented

- New scheduling schema:
  - `interview_slot_settings`
  - `interview_slot_exceptions`
- New models:
  - `App\Models\InterviewSlotSetting`
  - `App\Models\InterviewSlotException`
- New slot engine service:
  - `App\Services\InterviewSlotEngineService`
  - Generates UK timezone slots from office hours
  - Excludes weekends by default
  - Excludes UK bank holidays by default
  - Supports blackout dates and holiday overrides
  - Handles conflict-safe booking and slot release
- Recruiter slot management area:
  - `GET /recruiter/interview-slots`
  - Configure hours, duration, buffer, weekends, defaults
  - Add/remove blackout dates and holiday overrides
  - Generate slots for date ranges
  - Edit slot availability/mode/link/location
- Recruiter application scheduling flow:
  - Slot picker inside interview modal
  - New endpoint: `GET /recruiter/applications/{application}/interview-slots`
  - Booking by `slot_id` with double-book prevention
  - Reschedule/cancel releases previously booked slots
- AI flow integration:
  - High-score interview decisions now auto-book next available slot when possible.

## Validation run

- `php artisan migrate --force`
- `php artisan test tests/Feature/Interviews/InterviewSlotEngineTest.php tests/Feature/Candidate/ProcessCvAnalysisPhaseTwoFlowTest.php`

