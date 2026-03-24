# Active Execution Index

This file lists the only remaining execution lanes that should be run now.

Current state:
- Plans 01 to 05 are functionally complete.
- Plan 06 preparation lane is complete.
- Regression batch A already ran once and reported 31/32 pass.
- Seed gap B is closed.
- Remaining work is final-lane execution only.

Execution order:

1. `ACTIVE_EXECUTION_01_PAYMENT_PROOF_UPLOAD_FIX.md`
   - can run now
2. `ACTIVE_EXECUTION_02_FRESH_IMPORT_SEED_RERUN.md`
   - can run in parallel with step 1 if it uses a fresh import / isolated test state
3. `ACTIVE_EXECUTION_03_PAYMENT_PROOF_RETEST.md`
   - run after step 1
4. `ACTIVE_EXECUTION_04_RELEASE_GATES_CLOSURE.md`
   - run after steps 2 and 3
5. `ACTIVE_EXECUTION_05_FINAL_CLEANUP_IF_APPROVED.md`
   - optional, only after step 4 and owner confirmation

Rule:
- Send exactly one of the numbered files above to another AI.
- That AI should treat the file as the authoritative workstream instructions.
- Do not reopen Plans 01 to 05 as feature lanes.
- Only regression-driven fixes are allowed now.
