# Active Execution 02 - Fresh Import Seed Rerun

Standalone AI handoff file.

Current project state:
- Plans 01 to 05 are functionally complete.
- Plan 06 preparation lane is complete.
- One regression case previously failed around instructor seed visibility:
  - expected seeded instructor-owned `draft` and `pending_review` courses
  - result looked inconsistent during the earlier run
- There is strong suspicion this was environment state drift, not a true seed gap.
- Seed-gap lane B already closed the enrollment-state diversity blocker, so this rerun is now focused only on verifying the instructor-state case under a clean import.

Latest verification status:
- a fresh import of `SQL/lms_db.sql` was executed
- seeded course states were confirmed directly in the database:
  - `Excel văn phòng cho người đi làm` -> `draft`
  - `Laravel Team Project Workflow` -> `pending_review`
- instructor login + course-list checks confirmed the seeded states are visible in the expected instructor/admin operational views
- conclusion: the earlier regression failure was environment drift, not a true seed gap

Current status: COMPLETE for step 02.

Objective:
- Run a fresh-import, seed-sensitive rerun to decide whether the prior failure was a real seed problem or only test-environment mutation.

Do not do:
- no code changes
- no feature changes
- no doc changes unless explicitly asked after the rerun
- do not create extra test courses before checking the seed cases

Environment assumptions:
- base URL: `http://localhost/ELearning/`
- import source: `SQL/lms_db.sql`
- seed instructor accounts from `README.md`

Required work:
1. Re-import `SQL/lms_db.sql` on a fresh database state.
2. Before creating any new runtime data, verify seed-only instructor states.
3. Test these accounts:
   - `ngoc.creator@example.com` / `instructor123`
   - `chau.instructor@example.com` / `instructor123`
4. Check `/ELearning/Instructor/courses.php` for seeded `draft` and `pending_review` visibility.
5. Check admin operational views for the seeded pending-review course.
6. Conclude whether the prior fail was a true seed gap or environment drift.

Output format:
- Regression scope run
- Passed cases
- Failed cases
- Evidence collected
- Seed/data blockers still open
- Conclusion: true seed gap or environment drift
