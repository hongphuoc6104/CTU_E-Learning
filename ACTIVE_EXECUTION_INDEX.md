# Active Execution Index

This file lists the only remaining execution lanes that should be run now.

Current state:
- Plans 01 to 05 are functionally complete.
- Plan 06 preparation lane is complete.
- Regression batch A already ran once and reported 31/32 pass.
- Seed gap B is closed.
- Payment-proof fix lane C1 is now closed with a green live retest on `http://localhost/ELearning/`.
- Fresh-import seed-sensitive rerun A2 is also complete and confirmed the prior instructor-seed failure was environment drift, not a true seed gap.
- Targeted proof retest lane C2 is NOT confirmed complete on the current runtime/database state.
- Release-gates closure lane D has been attempted, but it must be treated as provisional until C2 is truly green.
- Remaining work is final-lane execution only.

Execution order:

Completed and archived:

- `docs/completed/active-executions/ACTIVE_EXECUTION_01_PAYMENT_PROOF_UPLOAD_FIX.md`
- `docs/completed/active-executions/ACTIVE_EXECUTION_02_FRESH_IMPORT_SEED_RERUN.md`

Remaining execution order:

1. `ACTIVE_EXECUTION_03_PAYMENT_PROOF_RETEST.md`
   - still required now
2. `ACTIVE_EXECUTION_04_RELEASE_GATES_CLOSURE.md`
   - rerun after step 1 is verified green
3. `ACTIVE_EXECUTION_05_FINAL_CLEANUP_IF_APPROVED.md`
   - optional, only after step 2 and owner confirmation

Rule:
- Send exactly one of the numbered files above to another AI.
- That AI should treat the file as the authoritative workstream instructions.
- Do not reopen Plans 01 to 05 as feature lanes.
- Only regression-driven fixes are allowed now.
