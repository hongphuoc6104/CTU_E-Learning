# Active Execution 03 - Payment Proof Retest

Standalone AI handoff file.

Precondition:
- Run this only after `ACTIVE_EXECUTION_01_PAYMENT_PROOF_UPLOAD_FIX.md` is completed and verified green on `http://localhost/ELearning/`.

Objective:
- Rerun the proof-related regression subset affected by the payment proof upload bug.

Do not do:
- no code changes
- no feature expansion
- no release-gate doc updates in this lane

Environment assumptions:
- base URL: `http://localhost/ELearning/`
- DB already imported as `lms_db`

Required tests:
1. Create a pending order.
2. Submit payment using reference-only path.
3. Submit payment using a valid proof file (PDF/JPG).
4. Confirm `order_status` and `payment_status` transition correctly.
5. Open admin payment screens:
   - `payments.php`
   - `paymentDetails.php`
6. Verify payment and confirm enrollment is granted.

Output format:
- Regression scope run
- Passed cases
- Failed cases
- Evidence collected
- Bugs for fix lane
- Release blockers still open
