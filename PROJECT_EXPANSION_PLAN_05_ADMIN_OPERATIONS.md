# Project Expansion Plan 05 - Admin Operations, Review, Payment Verification, Reporting

Generated: 2026-03-24

This plan defines the admin-side operational layer for the expanded platform.

## Objective

Allow admins to control quality, payment verification, publishing, users, and reporting for the expanded product.

## Dependencies

This plan depends on:

- Plan 01 domain/schema
- Plan 02 order/payment states
- Plan 04 instructor workflow

## Admin Responsibilities In The Expanded Product

- approve or reject course publication
- verify or reject payment submissions
- manage instructors
- manage students
- manage active/past live sessions if intervention is needed
- review operational reports

## Scope In This Plan

### Existing admin area to extend

- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/courses.php`
- `ELearning/Admin/students.php`
- `ELearning/Admin/feedback.php`
- `ELearning/Admin/contacts.php`
- `ELearning/Admin/trash.php`
- `ELearning/Admin/sellReport.php`

### New admin pages likely required

- `ELearning/Admin/courseReview.php`
- `ELearning/Admin/payments.php`
- `ELearning/Admin/paymentDetails.php`
- `ELearning/Admin/instructors.php`
- `ELearning/Admin/liveSessions.php`

## Admin Workflow A - Course Review And Publishing

1. Instructor submits draft course for review
2. Admin opens pending review queue
3. Admin reviews:
   - basic metadata
   - content structure
   - learning item coverage
   - live-session setup if relevant
4. Admin decision:
   - approve -> set `course_status = published`
   - reject -> return to draft/pending state with notes

### Required validations

- course has instructor
- course has at least one section
- course has at least one valid learning item
- live sessions have valid time ranges and join URLs

## Admin Workflow B - Payment Verification

1. Student submits payment proof
2. Admin opens pending payment queue
3. Admin reviews payment details
4. Decision:
   - verify -> mark payment verified, mark order paid, create enrollment
   - reject -> mark payment rejected and order failed

### Required behavior

- enrollment creation must be transactional with payment/order state update
- if enrollment creation fails, rollback the verification action
- admin should be able to add notes to rejection

## Admin Workflow C - Instructor Management

Admin can:

- create instructor accounts or approve created instructor accounts
- block/unblock instructors
- inspect courses owned by an instructor

## Admin Workflow D - Live Session Oversight

Admin can:

- inspect scheduled/live/ended sessions
- intervene if instructor-configured session data is invalid
- attach replay link if instructor fails to do so

## Admin Workflow E - Reporting

Reports should now include at least:

- paid orders only
- revenue by date range
- number of enrollments
- course popularity by paid enrollments
- optional instructor performance summary

## Required Admin Rules

1. Only admins can publish courses
2. Only admins can verify payments
3. Reporting must use successful paid orders only
4. Admin pages must keep CSRF and session protection
5. Admin destructive actions should prefer soft-delete unless permanent cleanup is explicitly required

## Error Handling Requirements

1. Reject invalid review actions with clear feedback
2. Reject payment verification if order/payment state is stale or already processed
3. Do not allow duplicate enrollments for the same student/course pair
4. Show clear empty states for review queues and payment queues

## Existing Files To Build On

Reuse patterns from:

- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/courses.php`
- `ELearning/Admin/sellReport.php`

Do not break currently working admin auth/session behavior.

## Explicit Out Of Scope For Plan 05

- multi-admin workflow approvals
- finance ledger/accounting module
- revenue sharing/payout engine

## Acceptance Criteria

1. Admin can publish/reject instructor-submitted courses
2. Admin can verify/reject student payment submissions
3. Verified payment creates enrollment correctly
4. Reporting reflects paid/enrolled reality accurately
5. Admin can manage instructor status safely

## Handoff Note For The Next AI

Do not redesign the full admin UI from scratch.
Extend the current admin module with focused operational screens.
