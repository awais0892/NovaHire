# Google Calendar Integration (Interview Scheduling)

NovaHire can sync recruiter interview schedules to Google Calendar when enabled.

## What this integration does

- On interview schedule:
  - creates or updates a Google Calendar event
  - stores `google_calendar_event_id` and sync timestamp on the interview
  - for video mode without a custom link, requests a Google Meet link

- On interview cancel:
  - marks the Google Calendar event as cancelled

- If Google API fails:
  - interview scheduling/cancellation still succeeds in NovaHire
  - failure is logged as a warning

## Required environment variables

Set these in `.env`:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_CALENDAR_ENABLED=true
GOOGLE_CALENDAR_ID=primary
GOOGLE_CALENDAR_REFRESH_TOKEN=...
GOOGLE_CALENDAR_TIMEOUT=10
```

## Database migration

Run:

```bash
php artisan migrate
```

This adds Google event tracking fields to `interviews`.

## Notes about OAuth

- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` are app credentials.
- `GOOGLE_CALENDAR_REFRESH_TOKEN` must be generated with Calendar scope consent.
- The refresh token should belong to the recruiter/organization Google account that owns the target calendar.

