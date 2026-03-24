# Plan 06 Release Gates and Remaining Blockers (Preparation Lane)

Generated: 2026-03-24

## Intent

Define rollout gates for final certification while Plan 05 is still in progress.

This document does not grant final release approval.

## Release Gates

| Gate | Requirement | Current state | Owner lane |
|---|---|---|---|
| G1 Schema import | Fresh import from `SQL/lms_db.sql` succeeds | READY (prep) | Plan 01 baseline |
| G2 Seed dataset readiness | Required curated seed categories present | READY with one gap (enrollment state diversity) | Plan 06 prep |
| G3 Guest/student baseline regression | Core browse/auth/learning smoke green | READY TO EXECUTE | Plan 06 prep |
| G4 Instructor operations regression | Draft->content->live->replay->submit review paths green | READY TO EXECUTE | Plan 04/06 |
| G5 Admin operations completeness | review + payment + instructor mgmt + live oversight + details pages available | BLOCKED | Plan 05 |
| G6 Reporting alignment | revenue/transactions aligned with paid-order reality and migration strategy | PARTIAL/BLOCKED | Plan 05 |
| G7 Compatibility safety | Legacy and new paths coexist without regression | READY (no cleanup applied) | Plan 06 prep |
| G8 Full regression matrix | `PLAN06_REGRESSION_MATRIX_EXECUTABLE.md` all critical items pass | NOT RUN (prep phase) | Plan 06 final lane |
| G9 Final cleanup/deprecation | remove/redirect legacy paths safely after verification | NOT ALLOWED YET | Plan 06 final lane |
| G10 Final signoff | full-wave certification and release approval | NOT ALLOWED YET | Final lane |

## Confirmed Blockers

### B1 Plan 05 not stabilized yet (hard blocker)

Impact:

- Final cleanup/signoff gates are not allowed while Plan 05 is still active.
- Blocks full-wave certification regardless of partial prep readiness.

### B2 Reporting still legacy-first (functional blocker for final alignment)

Files using legacy `courseorder` for reporting dashboards:

- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/sellReport.php`
- `ELearning/Admin/students.php`
- `ELearning/Admin/courses.php`

Impact:

- Reporting gate G6 cannot be certified as fully aligned to new paid-order model until Plan 05 stabilization decision is finalized.

### B3 Seed enrollment-state diversity gap (certification blocker)

- Current seed includes enrollment rows but only `enrollment_status = active`.

Impact:

- Plan 06 mandatory seed-state diversity for final certification is partial.

## Allowed Actions While Blocked

- Continue preparing migration notes/checklists and validation scripts.
- Run staged regression and log failures with blocker references.
- Keep compatibility code in place.

## Not Allowed Yet

- Legacy route deprecation.
- Compatibility code deletion.
- Final full-wave signoff.

## Exit Criteria To Remove Blocked State

1. Plan 05 delivers stabilized admin operations coverage and signoff.
2. Reporting alignment decision is implemented and regression-tested.
3. Seed data gap for enrollment-state diversity is resolved.
4. Full regression matrix passes on fresh import.
