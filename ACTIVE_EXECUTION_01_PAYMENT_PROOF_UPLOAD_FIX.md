# Active Execution 01 - Payment Proof Upload Fix

Standalone AI handoff file.

Current project state:
- Plans 01 to 05 are functionally complete.
- Plan 06 preparation lane is complete.
- A regression run already found one real bug:
  - route: `/ELearning/checkout_action.php`
  - flow entry: `/ELearning/Student/orderDetails.php`
  - symptom: submitting a valid JPG/PDF payment proof shows `Khong the luu minh chung thanh toan.`
  - severity: high

Objective:
- Fix only the payment proof upload bug.

Do not do:
- no feature expansion
- no UI redesign
- no release-gate doc updates
- no unrelated runtime refactors

Expected files to inspect/change if needed:
- `ELearning/checkout_action.php`
- `ELearning/Student/orderDetails.php`
- `ELearning/image/paymentproof/`
- any tiny helper/config change only if directly required by the upload fix

Environment assumptions:
- base URL: `http://localhost/ELearning/`
- DB already imported as `lms_db`
- seed account for quick repro: `cap@example.com` / `123456`

Required work:
1. Reproduce the bug.
2. Identify the real cause.
3. Fix it safely.
4. Retest the same upload flow.

Output format:
- Bug fixed
- Root cause
- Files changed
- Retest run
- Remaining risks
