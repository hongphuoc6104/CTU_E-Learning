# Plan 06 Regression Matrix (Executable Preparation)

Generated: 2026-03-24

Purpose: provide an executable matrix for staged and final certification runs without turning Plan 06 into a feature plan.

## Execution Rules

- Execute this matrix on a fresh SQL import (`SQL/lms_db.sql`) first.
- Record pass/fail with screenshot or query evidence for every failed case.
- If a case fails because Plan 05 work is incomplete, mark as `Blocked by Plan 05` (not ignored).

## Environment Preconditions

- PHP app boots without fatal errors.
- MySQL schema import succeeds from `SQL/lms_db.sql`.
- Seed login accounts are available.
- Tailwind build already generated (`ELearning/css/tailwind.css`).

## Stage Smoke (Run After Any Integration Merge)

- [ ] Public browse still works (`ELearning/index.php`, `ELearning/courses.php`, `ELearning/coursedetails.php`).
- [ ] Student auth still works (`ELearning/login.php`, `ELearning/signup.php`).
- [ ] Admin auth still works (`ELearning/Admin/admin.php`).
- [ ] Existing paid-course access still works (`ELearning/Student/myCourse.php`, `ELearning/Student/watchcourse.php`).

## Full Regression Matrix

### Guest

- [ ] Open home page.
- [ ] Open course catalog and published course cards.
- [ ] Open course detail page.
- [ ] Open signup page.
- [ ] Open login page.

### Student Commerce

- [ ] Add course to cart (`ELearning/cart_api.php`).
- [ ] Remove course from cart (`ELearning/Student/myCart.php`).
- [ ] Create order from single course and from cart (`ELearning/checkout.php`).
- [ ] Submit payment reference/proof (`ELearning/checkout_action.php`, `ELearning/Student/orderDetails.php`).
- [ ] See order status transitions (`ELearning/Student/myOrders.php`).
- [ ] Verify seeded pending/submitted payment records are visible and testable.

### Student Learning

- [ ] Enrolled courses visible (`ELearning/Student/myCourse.php`).
- [ ] Open course player (`ELearning/Student/watchcourse.php`).
- [ ] Open video/article/document items.
- [ ] Submit quiz attempt and observe pass/fail flow (`ELearning/Student/quizAttempt.php`).
- [ ] Progress updates persist (`ELearning/Student/markProgress.php`).
- [ ] Live session status and join behavior visible.
- [ ] Replay opens after availability.
- [ ] Confirm one partial-progress and one completed-progress student from seed.

### Instructor

- [ ] Instructor login works (`ELearning/Instructor/instructorLogin.php`).
- [ ] Create draft course (`ELearning/Instructor/addCourse.php`).
- [ ] Add section (`ELearning/Instructor/sections.php`).
- [ ] Add learning item types (`ELearning/Instructor/learningItems.php`).
- [ ] Add live session and replay (`ELearning/Instructor/liveSessions.php`).
- [ ] Submit draft course for review (`ELearning/Instructor/courses.php`).
- [ ] Verify seeded instructor-owned draft and pending-review courses exist.

### Admin

- [ ] Admin login works (`ELearning/Admin/admin.php`).
- [ ] Review pending course queue (`ELearning/Admin/courseReview.php`).
- [ ] Verify payment and see enrollment creation result (`ELearning/Admin/payments.php`).
- [ ] Reporting pages render (`ELearning/Admin/adminDashboard.php`, `ELearning/Admin/sellReport.php`).
- [ ] Manage instructors page accessible (`ELearning/Admin/instructors.php`).
- [ ] Verify seeded order/payment state diversity is visible in admin screens.

### Seed Integrity

- [ ] All 10 required curated dataset categories exist after fresh import.
- [ ] Seeded actors can login with documented credentials.
- [ ] Seeded published courses visible publicly.
- [ ] Seeded pending-review/draft courses appear only in operational views.

## Technical Checks

- [ ] No PHP syntax errors in touched files (`php -l` on changed docs-related helper scripts if any).
- [ ] No broken schema import from `SQL/lms_db.sql`.
- [ ] No path/case mismatch in key routes.
- [ ] No reintroduced jQuery usage.
- [ ] No reintroduced Bootstrap dependency.

## Current Preparation Blockers (Track During Execution)

- [ ] Plan 05 stabilization signoff not completed yet.
- [ ] Reporting alignment to final paid-order source-of-truth not finalized.
- [ ] Enrollment seed states currently lack non-`active` rows for final-state diversity.

## Certification Rule

Do not mark full-wave certification complete until:

1. Plan 05 stabilization is confirmed.
2. All blockers above are resolved or explicitly waived by project owner.
3. All checklist groups are green on a fresh import run.
