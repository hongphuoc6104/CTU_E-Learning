# Plan 06 Prompt C2 - Payment Proof Retest

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
