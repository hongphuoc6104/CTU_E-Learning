# Plan 06 Seed-Data Validation Summary (Preparation Lane)

Generated: 2026-03-24

Validated source: `SQL/lms_db.sql`

## Validation Method

- Read schema + seed inserts from `SQL/lms_db.sql`.
- Cross-check required dataset categories from Plan Index and Plan 06.
- Confirm runtime references for seeded entities in main guest/student/instructor/admin flows.

## Coverage Snapshot

### Actor coverage

- Admins: 2 seeded (`admin`, `operations.admin`).
- Instructors: 3 seeded and active.
- Students: 10 seeded with mixed commerce/learning states.

### Course and content coverage

- Courses: published, draft, pending_review, archived present.
- Course types: self-paced and blended present.
- Learning item types: video, article, document, quiz, live_session, replay present.
- Legacy compatibility content: `lesson` table seeded in parallel with `learning_item`.

### Commerce and enrollment coverage

- `order_master` states present: pending, awaiting_verification, paid, failed, cancelled.
- `payment` states present: pending, submitted, verified, rejected.
- `courseorder` legacy success history present.
- `enrollment` present with active records and mixed progress percentages.

### Live and replay coverage

- `live_session` states present: scheduled, ended, replay_available.
- Replay assets present and linked to live sessions.
- Session attendance records present.

### Quiz/progress coverage

- Multiple quizzes, questions, and answers seeded.
- Quiz attempts include pass and fail records.
- Learning progress includes not_started, in_progress, completed, passed.

## Required 10 Dataset Categories (Plan Policy)

| Required dataset | Status | Evidence |
|---|---|---|
| 1) Published self-paced beginner course with video/article/PDF/quiz | PASS | Course 1 + items 1-6 |
| 2) Published blended course with live sessions and replay links | PASS | Course 2 + live/replay items |
| 3) Draft instructor course | PASS | Course 3 |
| 4) Pending-review instructor course | PASS | Course 4 |
| 5) Student with no purchases | PASS | Seed students without enrollment/order success present |
| 6) Student with cart items but no paid orders | PASS | `lan.cart@example.com` with cart + non-paid flow |
| 7) Student with paid enrollment and partial progress | PASS | `cap@example.com` enrollment + mixed progress |
| 8) Student with paid enrollment and completed progress + quiz pass | PASS | `hoan.thanh@example.com` + passed attempt |
| 9) Order awaiting verification with proof metadata | PASS | order 3 + payment submitted |
| 10) Rejected/failed order and cancelled example | PASS | orders 4/5 + payment rejected/pending |

## Mandatory Category Check (Plan 06 section)

| Category | Status | Notes |
|---|---|---|
| admin | PASS | present |
| instructors | PASS | present |
| students in multiple states | PASS | multiple profiles/scenarios |
| courses in multiple states | PASS | draft/pending/published/archived |
| learning items in multiple types | PASS | all required types present |
| payments in multiple states | PASS | pending/submitted/verified/rejected |
| enrollments in multiple states | PASS | `active` + `expired` seeded |
| live sessions in multiple states | PASS | scheduled/ended/replay_available |
| replay links | PASS | replay_asset seeded |
| quiz attempts | PASS | pass and fail attempts present |

## Identified Gap For Final Certification

- Enrollment-state gap is closed in seed: non-active case now present via `enrollment_status = expired`.
- Active primary demo learning flows remain intact because partial-progress and main completed learning scenarios stay on `active` records.

## Runtime Compatibility Notes From Seed

- Hybrid model intentionally present (`lesson` + `learning_item`, `courseorder` + new order model).
- This supports transition testing and should not be cleaned up until Plan 05 stabilization + final lane.

## Prepared Validation Script

- Query pack added: `SQL/plan06_seed_validation_checks.sql`.
- Use it after fresh import to produce PASS/FAIL evidence quickly.
