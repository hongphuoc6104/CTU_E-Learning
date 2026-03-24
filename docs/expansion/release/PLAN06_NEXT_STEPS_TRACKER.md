# Plan 06 Next Steps Tracker

Generated: 2026-03-24

## Current State Snapshot

- Plans 01 to 05 are functionally complete.
- Plan 06 preparation lane is complete.
- Regression lane A has already run one full batch and reported `31/32` pass.
- Seed-gap lane B has already closed the enrollment-state diversity blocker.
- C1 has partial diagnosis only; the live runtime proof-upload retest is still failing, so the bug-fix lane remains open.

## Confirmed Open Items

1. Real bug to fix:
    - payment proof file upload fails in `ELearning/checkout_action.php`
2. Seed-sensitive rerun still needed on fresh import:
   - verify instructor-owned `draft` and `pending_review` seed courses exist before any state-mutating tests
3. Regression evidence and release-gate docs still need to be updated with final verified results
4. Final cleanup/signoff is still deferred until all regression evidence is green

## Recommended Execution Order

### Step C1 - Fix the known real bug

Scope:

- fix payment proof upload path/runtime issue only
- do not add new features

Target output:

- code fix
- local retest notes for proof upload flow

### Step C2 - Rerun proof-related regression subset

Scope:

- rerun only the impacted payment-proof and verification flow after C1
- confirm the previously failing real bug is gone

Target output:

- pass/fail retest evidence

### Step A2 - Fresh-import seed-sensitive rerun

Scope:

- fresh import `SQL/lms_db.sql`
- rerun the seed-sensitive instructor case before creating any new test courses
- confirm both `draft` and `pending_review` seed states are visible in the correct operational views

Target output:

- pass/fail evidence proving whether the previous failure was seed/data drift or a true seed gap

### Step D - Release gates and docs closure

Scope:

- update Plan 06 artifacts using the final verified regression outcomes
- close or downgrade blockers with evidence only

Target output:

- updated release gates
- updated blocker list
- clear final remaining blockers

### Step E - Optional final cleanup lane

Only start if:

- proof-upload bug is fixed
- reruns are green
- fresh-import evidence is clean
- project owner wants legacy cleanup/signoff work

## Safe Parallelism

- C1 can run now.
- A2 can also run now on a fresh import if it does not mutate the same test environment used by C1.
- C2 must wait for C1 to be verified green on the live runtime.
- D must wait for C2 and A2.
- E must wait for D and owner confirmation.

## Tracking Board

- [ ] C1 Fix payment proof upload bug and verify it on live runtime
- [ ] C2 Rerun payment-proof regression subset
- [ ] A2 Rerun seed-sensitive instructor case on fresh import
- [ ] D Update release gates and docs from verified evidence
- [ ] E Optional final cleanup/signoff lane

## Standalone Prompt Files

- `docs/expansion/release/prompts/PLAN06_PROMPT_C1_BUG_FIX.md`
- `docs/expansion/release/prompts/PLAN06_PROMPT_C2_PAYMENT_PROOF_RETEST.md`
- `docs/expansion/release/prompts/PLAN06_PROMPT_A2_FRESH_IMPORT_RERUN.md`
- `docs/expansion/release/prompts/PLAN06_PROMPT_D_RELEASE_GATES.md`
- `docs/expansion/release/prompts/PLAN06_PROMPT_E_FINAL_CLEANUP.md`

## Prompt C1 - Bug Fix Lane

```text
Hãy thực thi workstream Plan 06 Stabilization Fix - C1 payment proof upload bug.

Bat buoc doc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
3. `docs/expansion/release/PLAN06_NEXT_STEPS_TRACKER.md`
4. regression result summary da co

Bug can sua:
- Route: `/ELearning/checkout_action.php`
- Entry flow: `/ELearning/Student/orderDetails.php`
- Account test: `cap@example.com` / `123456` (co the lap lai voi student seed khac)
- Trieu chung: submit payment form voi file proof hop le (PDF/JPG) thi bao `Khong the luu minh chung thanh toan.`
- Muc do: high

Muc tieu:
- chi sua bug upload payment proof file
- khong mo rong feature
- khong redesign UI
- khong dong vao plan khac neu khong can thiet

Yeu cau:
- tim nguyen nhan that su (path, permission, upload handling, runtime save path, validation, Apache/XAMPP behavior, etc.)
- sua gon, an toan
- neu can them runtime-safe guardrail thi duoc, nhung khong doi business flow
- sau khi sua, rerun ngay case upload proof lien quan neu moi truong cho phep

Tra ket qua theo format:
- Bug fixed
- Root cause
- Files changed
- Retest run
- Remaining risks
```

## Prompt C2 - Payment Proof Retest

```text
Hãy thực thi workstream Plan 06 targeted regression rerun - C2 payment proof retest.

Bat buoc doc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
3. `docs/expansion/release/PLAN06_REGRESSION_MATRIX_EXECUTABLE.md`
4. `docs/expansion/release/PLAN06_NEXT_STEPS_TRACKER.md`

Muc tieu:
- rerun subset regression bi anh huong boi bug payment proof upload sau khi C1 xong

Bat buoc test lai:
- tao order pending
- submit payment voi payment_reference only
- submit payment voi payment_proof file (PDF/JPG)
- xac nhan order_status va payment_status doi dung
- admin mo `payments.php` / `paymentDetails.php`
- admin verify payment va kiem tra enrollment duoc cap

Khong sua code.
Chi thu thap evidence pass/fail.

Tra ket qua theo format:
- Regression scope run
- Passed cases
- Failed cases
- Evidence collected
- Bugs for fix lane
- Release blockers still open
```

## Prompt A2 - Fresh Import Seed-Sensitive Rerun

```text
Hãy thực thi workstream Plan 06 fresh-import seed-sensitive rerun - A2.

Bat buoc doc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
3. `docs/expansion/release/PLAN06_REGRESSION_MATRIX_EXECUTABLE.md`
4. `docs/expansion/release/PLAN06_NEXT_STEPS_TRACKER.md`
5. `README.md`

Muc tieu:
- fresh import lai `SQL/lms_db.sql`
- rerun rieng nhom case seed-sensitive truoc khi tao them du lieu test moi
- xac minh fail truoc do ve instructor-owned `draft` / `pending_review` la do seed gap hay do environment state drift

Bat buoc test lai:
- login `ngoc.creator@example.com` / `instructor123`
- login `chau.instructor@example.com` / `instructor123`
- vao `/ELearning/Instructor/courses.php`
- kiem tra dung seed goc:
  - co `draft`
  - co `pending_review`
- kiem tra admin operational views co thay pending-review course seed hay khong

Khong sua code.
Khong tao them khoa hoc test truoc khi xac minh xong case nay.

Tra ket qua theo format:
- Regression scope run
- Passed cases
- Failed cases
- Evidence collected
- Seed/data blockers still open
- Conclusion: true seed gap or environment drift
```

## Prompt D - Release Gates Closure

```text
Hãy thực thi workstream Plan 06 release gates closure - D.

Bat buoc doc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
3. `docs/expansion/release/PLAN06_RELEASE_GATES_AND_BLOCKERS.md`
4. `docs/expansion/release/PLAN06_REGRESSION_MATRIX_EXECUTABLE.md`
5. `docs/expansion/release/PLAN06_NEXT_STEPS_TRACKER.md`
6. ket qua moi nhat tu C2 va A2

Muc tieu:
- cap nhat release gates va blocker docs theo evidence that
- dong/mo blocker dung muc do
- khong them feature moi

Can cap nhat neu co evidence:
- `docs/expansion/release/PLAN06_RELEASE_GATES_AND_BLOCKERS.md`
- `docs/expansion/release/PLAN06_REGRESSION_MATRIX_EXECUTABLE.md`
- `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
- neu can: `README.md`

Tra ket qua theo format:
- Gates updated
- Blockers closed
- Blockers still open
- Files changed
- Ready for final cleanup/signoff: yes/no
```

## Prompt E - Optional Final Cleanup

```text
Hãy thực thi workstream Plan 06 optional final cleanup/signoff - E.

Chi bat dau neu:
- C1/C2 da xanh
- A2 xac nhan seed-sensitive case sach
- D da cap nhat gate/blocker va project owner dong y cleanup

Bat buoc doc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
3. `docs/expansion/release/PLAN06_RELEASE_GATES_AND_BLOCKERS.md`
4. `docs/expansion/release/PLAN06_NEXT_STEPS_TRACKER.md`

Muc tieu:
- chi thuc hien cleanup nao that su an toan
- khong xoa compatibility code neu chua co evidence regression va owner signoff

Tra ket qua theo format:
- Cleanup applied
- Legacy paths kept
- Risks
- Final signoff readiness
```
