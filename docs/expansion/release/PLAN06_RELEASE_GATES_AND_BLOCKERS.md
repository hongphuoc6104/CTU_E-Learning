# Plan 06 Release Gates and Remaining Blockers (Preparation Lane)

Generated: 2026-03-24

## Intent

Define rollout gates for final certification after the implementation plans are in place.

This document does not grant final release approval.

## Release Gates

| Gate | Requirement | Current state | Owner lane |
|---|---|---|---|
| G1 Schema import | Fresh import from `SQL/lms_db.sql` succeeds | READY (prep) | Plan 01 baseline |
| G2 Seed dataset readiness | Required curated seed categories present | READY | Plan 06 prep |
| G3 Guest/student baseline regression | Core browse/auth/learning smoke green | PARTIAL - regression batch run, payment-proof bug still open | Plan 06 final lane |
| G4 Instructor operations regression | Draft->content->live->replay->submit review paths green | PARTIAL - seed-sensitive rerun still pending | Plan 06 final lane |
| G5 Admin operations completeness | review + payment + instructor mgmt + live oversight + details pages available | READY TO VERIFY | Plan 05 stabilization |
| G6 Reporting alignment | revenue/transactions aligned with paid-order reality and migration strategy | READY TO VERIFY | Plan 05 stabilization |
| G7 Compatibility safety | Legacy and new paths coexist without regression | READY (no cleanup applied) | Plan 06 prep |
| G8 Full regression matrix | `docs/expansion/release/PLAN06_REGRESSION_MATRIX_EXECUTABLE.md` all critical items pass | PARTIAL - first batch 31/32, reruns still pending | Plan 06 final lane |
| G9 Final cleanup/deprecation | remove/redirect legacy paths safely after verification | NOT ALLOWED YET | Plan 06 final lane |
| G10 Final signoff | full-wave certification and release approval | NOT ALLOWED YET | Final lane |

## Confirmed Blockers

### B1 Final regression evidence not complete yet (hard blocker)

Impact:

- Final cleanup/signoff gates are not allowed until the prepared regression matrix is actually executed and evidenced.
- Blocks full-wave certification regardless of partial prep readiness.

### B2 Final rollout/signoff evidence not complete yet (release blocker)

Impact:

- Gates G5 and G6 are implemented in code, but still require manual verification and regression evidence before certification.

### B3 Fresh-import seed validation evidence still missing (certification blocker)

- Seed blocker for enrollment-state diversity has been closed in SQL.

Impact:

- Final certification still requires fresh-import validation evidence, not just prepared scripts and updated seed rows.

### B4 Payment proof upload bug still open on live runtime (high-severity blocker)

- Route: `/ELearning/checkout_action.php`
- Entry flow: `/ELearning/Student/orderDetails.php`
- Current observed result: valid JPG/PDF upload still returns the save-error state on the live web runtime.

Impact:

- Student commerce regression cannot be marked green.
- Blocks trustworthy closure of G3 and G8 until the live runtime retest passes.

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
2. Admin/reporting verification is regression-tested and evidenced.
3. Payment proof upload bug is fixed and retested green on the live runtime.
4. Full regression matrix passes on fresh import.
5. Rollout/signoff evidence is collected and reviewed.
