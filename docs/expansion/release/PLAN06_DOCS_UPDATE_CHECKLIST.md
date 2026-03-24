# Plan 06 Documentation Update Checklist (Preparation Lane)

Generated: 2026-03-24

## Objective

Prepare documentation updates needed for migration/testing/rollout without claiming final release completion.

## Current Document Baseline

- `README.md` already includes:
  - setup/import flow
  - seeded account list
  - expanded commerce/learning context
- Plan files include execution status updates for Plan 01-06.

## Documentation Checklist

### A) Setup and bootstrap

- [x] Confirm `README.md` points to `SQL/lms_db.sql` as primary bootstrap.
- [x] Confirm no second SQL import is required for baseline demo.
- [ ] Add explicit note for optional Plan 06 validation queries (`SQL/plan06_seed_validation_checks.sql`) in setup verification section.

### B) Role and flow docs

- [x] Guest/student/admin roles described in README.
- [x] Instructor seed accounts documented.
- [ ] Add explicit compatibility note: public detail currently reads legacy lesson list while student player uses learning items.
- [ ] Add explicit note that admin operations pages must be complete before final rollout gate is green.

### C) Commerce and payment docs

- [x] Mock payment model documented.
- [x] Payment verification concept documented.
- [ ] Add troubleshooting note for order/payment mismatch between legacy (`courseorder`) and new (`order_master`) data during migration period.

### D) Seed demo scenarios

- [x] Seed accounts and representative scenarios are listed.
- [ ] Add direct mapping table from seed account -> intended regression scenario bucket (guest/student/instructor/admin).
- [ ] Add note for known temporary gap: enrollment status diversity for final certification.

### E) Release and rollout docs

- [x] Plan 06 now references preparation artifacts.
- [ ] Link all Plan 06 checklist files from README (or a central release notes section) before final lane.
- [ ] Add explicit release gate table and blocker list in docs (see `docs/expansion/release/PLAN06_RELEASE_GATES_AND_BLOCKERS.md`).

## Files To Update In Final Documentation Pass

- `README.md`
- `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
- (optional) a dedicated release notes file if project owner wants one

## Notes

- This checklist is intentionally preparation-only.
- Do not mark final docs complete until Plan 05 blockers are resolved and full regression matrix passes.
