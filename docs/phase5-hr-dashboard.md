# NovaHire Phase 5 (HR Dashboard & DataTable Interface)

Phase 5 extends recruiter applications into a full HR control panel with manual override and export workflows.

## What was implemented

- Recruiter applications backend upgrades (`RecruiterApplicationController`):
  - Shared filtered query builder for consistent listing + exports
  - `GET /recruiter/applications/{application}/details` JSON endpoint for detail drawer
  - `POST /recruiter/applications/{application}/notes/override` for HR manual note override
  - CSV export endpoint: `GET /recruiter/applications/export/csv`
  - PDF export endpoint: `GET /recruiter/applications/export/pdf`
  - richer audit metadata with old/new status and note snapshots

- Recruiter applications UI (`resources/views/pages/recruiter/applications/index.blade.php`):
  - DataTable-style client interactions on current page:
    - quick search
    - column sorting (candidate, role, score, status, applied date)
  - Export controls (CSV/PDF)
  - Candidate detail drawer:
    - application summary
    - AI note summary
    - interview snapshot
    - email history
  - HR note override form inside drawer with optional immediate resend
  - Recent manual changes panel (audit feed)
  - write controls now respect manage permission flags

- Email service enhancement (`CandidateDecisionEmailService`):
  - forced resend support for HR override flows
  - custom note content override support for outbound decision emails

- New PDF view:
  - `resources/views/pdf/applications-export.blade.php`

- RBAC extension:
  - Added role `hr_standard` in `RolesAndPermissionsSeeder`
  - Added test account seed `hr.standard@test.com`
  - Login + root redirect support for `hr_standard`
  - Menu support for `hr_standard`
  - Applications routes moved to shared HR role access:
    - `role:hr_admin|hr_standard`
  - Mutation routes remain protected by `permission:applications.manage`

## Notes

- Existing HR admin (`hr_admin`) flows remain intact.
- `hr_standard` is read-focused for applications unless granted manage permissions.
