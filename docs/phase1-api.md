# NovaHire Phase 1 API (Foundation)

This document defines the Phase 1 backend endpoints needed by frontend and operations tooling.

Base URL (local): `http://127.0.0.1:8000/api/v1`

## 1) Health Check

- Method: `GET`
- Path: `/phase1/health`
- Purpose: Validate Phase 1 infrastructure wiring (DB, holidays, AI keys, email, UK timezone).

Example:

```bash
curl http://127.0.0.1:8000/api/v1/phase1/health
```

Response fields:

- `healthy` boolean
- `checks.database`
- `checks.uk_holidays`
- `checks.openai`
- `checks.email`
- `checks.uk_timezone`

## 2) UK Bank Holidays

- Method: `GET`
- Path: `/uk-bank-holidays`
- Purpose: Return UK bank holiday events used by scheduling/slot logic.
- Query params:
  - `division`: `england-and-wales` | `scotland` | `northern-ireland`
  - `year`: integer (e.g. `2026`)
  - `force`: `1|true` to force refresh cache

Example:

```bash
curl "http://127.0.0.1:8000/api/v1/uk-bank-holidays?division=england-and-wales&year=2026"
```

Response fields:

- `division`
- `year`
- `timezone`
- `source`
- `count`
- `events[]` (`title`, `date`, `year`, `notes`, `bunting`)

## 3) Console Commands (Phase 1 Ops)

- `php artisan recruitment:uk-holidays:sync [--division=england-and-wales] [--year=2026] [--force]`
- `php artisan recruitment:openai:test-note [--name=...] [--role=...] [--score=74] [--decision=shortlist]`
- `php artisan recruitment:mail:test [recipient@example.com]`

## Notes

- Real-time notifications are intentionally excluded from this phase (already implemented separately).
- Socket.IO is not required; Reverb remains the realtime provider where needed by later phases.
