# Plan 06 Migration and Backfill Checklist (Preparation Lane)

Generated: 2026-03-24

## Objective

Prepare migration and compatibility notes for rollout safety while Plan 05 is still the main implementation lane.

## Scope Guardrails (Must Keep)

- Preparation lane only: no new feature expansion.
- No final cleanup/deprecation while Plan 05 is still stabilizing.
- Keep legacy and new flows running in parallel where required.
- Prefer checklists, notes, and validation scripts over runtime rewrites.

## Dependencies Checked

- `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
- `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
- seed baseline file: `SQL/lms_db.sql`

## Current Compatibility Map

| Domain | Legacy path | New path | Current runtime behavior | Prep status |
|---|---|---|---|---|
| Course content display | `lesson` | `course_section` + `learning_item` | Public detail still reads `lesson`; student player reads `learning_item` | Intentional compatibility |
| Commerce order lifecycle | `courseorder` | `order_master` + `order_item` + `payment` | Checkout creates new order model | New model active |
| Enrollment access | legacy successful purchase rows | `enrollment` | Student learning access checks `enrollment` | New model active |
| Payment verification | legacy-only status assumptions | admin payment workflow in `order_master`/`payment` | Admin verify/reject updates new model, then writes compatibility `courseorder` record | Hybrid compatibility |
| Revenue/reporting | `courseorder` | (target) paid orders from new model | Several admin pages still rely on legacy table | Pending migration |

## Migration/Backfill Checklist

### A) Legacy `lesson` -> `learning_item` video mapping

- [x] Confirm both tables coexist in schema (`SQL/lms_db.sql`).
- [x] Confirm seed already includes both legacy `lesson` and new `learning_item` records.
- [x] Confirm student runtime learning uses `learning_item` (`ELearning/Student/watchcourse.php`).
- [x] Confirm public course detail still has legacy read path (`ELearning/coursedetails.php`).
- [ ] Prepare idempotent production backfill SQL for environments that still have legacy-only lessons.
- [ ] Add dry-run count check: lessons per course vs generated video items per section.

### B) Legacy purchases -> enrollment/new order reality

- [x] Confirm checkout writes `order_master`, `order_item`, `payment` (`ELearning/checkout.php`).
- [x] Confirm payment verification updates new order/payment states and creates `enrollment` (`ELearning/Admin/payments.php`, `ELearning/Admin/admin_helpers.php`).
- [x] Confirm compatibility write to `courseorder` is still preserved after verify (`ELearning/Admin/admin_helpers.php`).
- [ ] Prepare reconciliation query pack: paid `order_master` vs `courseorder` `TXN_SUCCESS` count by day and by course.
- [ ] Prepare rollback note if reconciliation mismatch appears in staging.

### C) Runtime compatibility safety rules

- [x] Do not delete compatibility writes (`courseorder`) yet.
- [x] Do not remove legacy read paths (`lesson`) yet.
- [x] Keep dual-read/dual-write behavior documented before final cutover.
- [ ] Define final cutover checkpoint (only after Plan 05 stable and regression matrix green).

### D) Data quality and integrity checks to run before final lane

- [x] Seed validation query script prepared: `SQL/plan06_seed_validation_checks.sql`.
- [ ] Run script on fresh import and store evidence snapshot.
- [ ] Resolve all remaining FAIL checks on fresh import before final certification.

## Known Blockers Found During Preparation

1. Final regression matrix and rollout evidence have not been fully executed yet.
2. Final migration cleanup/signoff is intentionally deferred until release gates are green.

## Deferred To Final Cleanup Lane (After Plan 05 Stable)

- Legacy route deprecation/removal.
- Compatibility write removal for `courseorder`.
- Legacy `lesson` cleanup.
- Final release signoff and full-wave certification.
