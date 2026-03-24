# Plan 06 Prompt C1 - Bug Fix Lane

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
