# Plan 06 Prompt A2 - Fresh Import Seed-Sensitive Rerun

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

Luu y:
- blocker seed enrollment-state diversity da dong
- lane nay chi con xac minh instructor seed-state case tren fresh import

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
