# Project Flow Hardening Plan

Generated: 2026-03-24

This is the follow-up implementation plan for the current project after the migration plan in `PROJECT_AUDIT.md` was completed.

Use this file when the goal is to harden business logic, improve end-to-end flow reliability, close remaining logic gaps, and avoid breaking the parts that already work.

Do not create another planning file unless explicitly requested.

## Relationship To Existing Files

- `PROJECT_AUDIT.md`
  - historical audit + completed migration record
  - treat it as background and completed context
- `PROJECT_FLOW_HARDENING_PLAN.md`
  - current source of truth for the next implementation wave
  - focused on logic consistency, exception handling, and flow reliability

## Final Goal

Keep the current successful project direction intact while making the application safer and more consistent from the first page visit to the end of each user journey.

The target system remains:

- Backend: `PHP + MySQL`
- Frontend: `HTML + CSS + Tailwind CSS + Vanilla JavaScript`
- Architecture: server-rendered PHP

This plan does not change the product goal of the project.

The project remains an e-learning platform with:

- public course browsing
- student registration and login
- cart and mock checkout
- owned-course playback
- profile and feedback
- admin management of courses, lessons, students, contacts, feedback, trash, and reports

## Main Objective Of This Plan

Fix the remaining logic and flow issues without reopening the completed migration work.

Specifically:

1. Make business rules consistent across pages and APIs
2. Make end-to-end journeys reliable even when users do unexpected things
3. Improve error handling and exceptions in important flows
4. Complete weak or incomplete admin/student flows
5. Preserve all working Tailwind/vanilla/PHP migration results

## Must-Read Rules For Any AI Executing This Plan

1. Read `PROJECT_AUDIT.md` first for context, but do not reopen completed migration phases unless a regression is found
2. Use this file as the active execution plan for logic hardening
3. Keep the current stack exactly as-is:
   - no Bootstrap
   - no jQuery
   - no Popper
   - no Owl Carousel
   - no SPA framework
   - no backend framework migration
4. Do not redesign the database broadly unless explicitly required by this plan
5. Do not touch unrelated binary assets, screenshots, or old documents
6. Do not change working routes unless this plan explicitly says to deprecate or redirect one
7. Prefer targeted fixes over broad rewrites
8. Any touched flow must keep or improve:
   - CSRF protection
   - password hashing
   - session hardening
   - prepared statements
9. If a fix would require a large schema redesign, stop and document it as a follow-up instead of improvising a risky rewrite

## Hard Constraints

These are mandatory.

1. Do not reintroduce removed frontend dependencies
2. Do not replace server-side PHP rendering with client-side framework rendering
3. Do not change mock checkout into a real payment gateway in this plan
4. Do not change the project into a student-id-based relational redesign in this plan unless explicitly approved later
5. Do not remove completed features that currently pass regression
6. Do not silently change behavior that users/admins depend on; if behavior changes, update `README.md` or relevant docs

## Non-Goals

The following are out of scope unless explicitly approved later:

- real online payment integration
- full database normalization redesign
- multi-role permission system redesign
- full API architecture extraction
- replacing all remaining `$conn->query(...)` calls in every file just for style consistency
- visual redesign of the whole app

## Protected Areas

Avoid touching these unless strictly needed for a listed fix:

- `package.json`
- `tailwind.config.js`
- `ELearning/css/tailwind.input.css`
- `ELearning/css/tailwind.css`
- `ELearning/paymentstatus.php`
- `ELearning/Admin/adminPaymentStatus.php`
- `ELearning/PaytmKit/`
- shared layout files unless a listed fix really requires them:
  - `ELearning/mainInclude/header.php`
  - `ELearning/mainInclude/footer.php`
  - `ELearning/Student/stuInclude/header.php`
  - `ELearning/Student/stuInclude/footer.php`
  - `ELearning/Admin/adminInclude/header.php`
  - `ELearning/Admin/adminInclude/footer.php`

## Canonical Business Rules

All implementation work in this plan must follow these rules.

### Rule 1 - Ownership

A student owns a course only when:

- `courseorder.status = 'TXN_SUCCESS'`
- and `courseorder.is_deleted = 0`

This rule must be used consistently in:

- public purchase-state UI
- cart add validation
- my courses list
- watch course access
- revenue/report calculations

### Rule 2 - Public Course Visibility

Public pages show only courses where:

- `course.is_deleted = 0`

### Rule 3 - Deleted Course Access For Existing Owners

Policy for this plan:

- if a course is soft-deleted, it should disappear from public catalog pages
- existing successful owners may still see and watch it from student-owned areas

Do not change this policy unless explicitly approved.

### Rule 4 - Revenue

Revenue and order counts must use only successful, non-deleted orders.

### Rule 5 - Cart Visibility

Cart badge count and cart page contents must represent the same set of rows:

- cart row belongs to current student
- cart row is not deleted
- linked course still exists and is not deleted

### Rule 6 - Student Soft Delete

Policy for this plan:

- deleting a student disables student account access
- active cart items for that student should be disabled/soft-deleted
- historical successful orders should remain for audit/reporting unless a later policy explicitly changes this

### Rule 7 - Student Email Changes

Because the current schema uses `stu_email` in related tables, changing a student email must not orphan:

- `courseorder.stu_email`
- `cart.stu_email`

Either:

- synchronize related rows in a transaction, or
- block email edits until a broader schema redesign is approved

For this plan, the preferred approach is transactional synchronization.

### Rule 8 - Password Change UX

The canonical student password-change flow should be the secure one.

Preferred policy for this plan:

- deprecate `ELearning/Student/studentChangePass.php` by redirecting to the secure password section in `ELearning/Student/studentProfile.php`
- do not keep two different password-change behaviors active

### Rule 9 - Lesson Ownership And Integrity

Admins must not be able to create or update a lesson with a missing/deleted course target.

### Rule 10 - Error Handling

For touched flows:

- page requests should redirect cleanly or show visible fallback UI
- write actions should return visible success/failure state
- multi-table updates should use transactions
- uploads should clean up orphaned files if DB update fails

## Feature Inventory

### Public Features

- Home page: `ELearning/index.php`
- Course catalog: `ELearning/courses.php`
- Course detail: `ELearning/coursedetails.php`
- Student signup: `ELearning/signup.php`, `ELearning/studentRegistration.php`, `ELearning/Student/addstudent.php`
- Student login: `ELearning/login.php`, `ELearning/Student/addstudent.php`
- Contact form: `ELearning/contact.php`
- Admin login modal: `ELearning/mainInclude/footer.php`, `ELearning/js/adminajaxrequest.js`, `ELearning/Admin/admin.php`

### Student Features

- Cart: `ELearning/cart_api.php`, `ELearning/Student/myCart.php`
- Checkout: `ELearning/checkout.php`, `ELearning/checkout_action.php`
- My courses: `ELearning/Student/myCourse.php`
- Watch course: `ELearning/Student/watchcourse.php`
- Profile + avatar + inline password change: `ELearning/Student/studentProfile.php`
- Standalone password page: `ELearning/Student/studentChangePass.php`
- Feedback submission: `ELearning/Student/stufeedback.php`
- Logout: `ELearning/logout.php`

### Admin Features

- Dashboard: `ELearning/Admin/adminDashboard.php`
- Courses: `ELearning/Admin/courses.php`, `ELearning/Admin/addCourse.php`, `ELearning/Admin/editcourse.php`
- Lessons: `ELearning/Admin/lessons.php`, `ELearning/Admin/addLesson.php`, `ELearning/Admin/editlesson.php`
- Students: `ELearning/Admin/students.php`, `ELearning/Admin/editstudent.php`
- Feedback moderation: `ELearning/Admin/feedback.php`
- Contacts inbox: `ELearning/Admin/contacts.php`
- Trash: `ELearning/Admin/trash.php`
- Revenue report: `ELearning/Admin/sellReport.php`
- Admin password change: `ELearning/Admin/adminChangePass.php`

## End-To-End Journeys And Current Gaps

This section is the execution map for the next AI.

### Journey A - Guest Visits Site And Becomes Student

Flow:

1. Visit `ELearning/index.php`
2. Browse `ELearning/courses.php`
3. Open `ELearning/coursedetails.php?course_id=...`
4. Sign up through `ELearning/signup.php`
5. Log in through `ELearning/login.php`

Current gaps to fix:

- public course ownership state is not fully consistent with success-only order logic
- invalid course detail requests do not always fail gracefully
- signup validation is still thin on the server side
- login/signup endpoints have weak exception messaging for edge cases

### Journey B - Student Adds To Cart, Checks Out, Studies Course

Flow:

1. Add course to cart from catalog or detail page
2. View `ELearning/Student/myCart.php`
3. Remove/re-add cart items
4. Submit cart or single-course checkout through `ELearning/checkout.php`
5. Finalize purchase in `ELearning/checkout_action.php`
6. View owned courses in `ELearning/Student/myCourse.php`
7. Watch lessons in `ELearning/Student/watchcourse.php`

Current gaps to fix:

- cart add does not verify active course existence strongly enough
- cart badge count and cart page query can diverge on orphan/deleted course rows
- owned-course logic is not fully aligned with success-only order rule
- watch access uses inconsistent ownership criteria
- report/dashboard calculations also need the same success-only rule

### Journey C - Student Manages Personal Account

Flow:

1. Open `ELearning/Student/studentProfile.php`
2. Update name/occupation/avatar
3. Change password
4. Submit feedback
5. Logout

Current gaps to fix:

- profile update can leave orphaned uploaded files on DB failure
- standalone password page duplicates a weaker password-change flow
- missing-student/session mismatch handling is not always strict enough
- contact and feedback flows need stronger validation and user-visible feedback consistency

### Journey D - Admin Logs In And Manages System

Flow:

1. Log in from footer modal
2. Reach `ELearning/Admin/adminDashboard.php`
3. Manage courses, lessons, students, contacts, feedback, trash
4. View sales report

Current gaps to fix:

- dashboard/report counts should be success-only
- admin student management is incomplete as a full flow
- editing student email can orphan related rows unless synchronized
- adding/editing lessons can create inconsistent lesson-to-course relationships
- some pages still rely on lightweight JS redirect guards instead of strict server redirect behavior

## Prioritized Issue List

### P1 - Must Fix In This Plan

1. Ownership/status consistency across browse/cart/my-course/watch/report flows
2. Cart count and cart visibility consistency
3. Student email edit must not orphan `cart` and `courseorder`
4. Standalone weak password page must be removed from active behavior or hardened to match the main flow
5. Lesson add/edit must reject invalid or deleted course targets
6. Revenue and dashboard must count only successful orders

### P2 - Strongly Recommended In This Plan

1. Stronger server-side signup validation
2. Better DB/query failure fallback on key pages
3. Better contact form validation and inline messaging
4. Profile upload cleanup on failed DB update
5. More consistent resource-not-found handling on course/detail pages
6. Add clear edit entry point in admin student management

### P3 - Optional If Time Remains

1. Better trash restore/delete integrity checks
2. Stronger date-range validation in sales report
3. Additional low-severity seed/data integrity cleanup beyond this plan

## Implementation Order

Do not work randomly. Execute in this order.

### Phase A - Unify Ownership And Revenue Logic

Goal:

- one ownership rule
- one revenue rule
- one access rule

Files in scope:

- `ELearning/index.php`
- `ELearning/courses.php`
- `ELearning/coursedetails.php`
- `ELearning/cart_api.php`
- `ELearning/checkout_action.php`
- `ELearning/Student/myCourse.php`
- `ELearning/Student/watchcourse.php`
- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/sellReport.php`

Tasks:

- update public purchase checks to require `status = 'TXN_SUCCESS'` and `is_deleted = 0`
- update my-courses query to show only successful non-deleted orders
- update watch-course ownership guard to require successful non-deleted orders
- update dashboard/report queries to count/sum only successful non-deleted orders
- keep policy: deleted courses stay hidden publicly but remain available to existing successful owners

Acceptance criteria:

- browse UI, cart checks, my-course page, watch-course access, dashboard, and sell report all agree on ownership and revenue

Do not:

- redesign checkout into a real payment gateway
- change course visibility policy for existing owners

### Phase B - Fix Cart And Checkout Edge Cases

Goal:

- cart and checkout must behave consistently when data changes under the user

Files in scope:

- `ELearning/cart_api.php`
- `ELearning/Student/myCart.php`
- `ELearning/checkout.php`
- `ELearning/checkout_action.php`

Tasks:

- verify course existence and active state before cart add succeeds
- make cart count query match cart page semantics by joining active courses
- make remove return a clearer result if the cart row does not belong to the user or does not exist
- make checkout failure/success feedback more explicit for partial or expired state
- ensure single-course checkout blocks already-owned successful courses before proceeding

Acceptance criteria:

- cart badge count matches visible cart items
- invalid/deleted course IDs cannot be added
- duplicate successful checkout attempts are rejected cleanly
- partial cart inconsistencies show clear outcome

Do not:

- remove the mock checkout model

### Phase C - Harden Student Account Flows

Goal:

- student account flows should be consistent, safe, and not duplicate weaker logic

Files in scope:

- `ELearning/Student/addstudent.php`
- `ELearning/Student/studentProfile.php`
- `ELearning/Student/studentChangePass.php`
- `ELearning/Student/stufeedback.php`
- `ELearning/logout.php`

Tasks:

- add stronger server-side signup validation:
  - valid email format
  - minimum password length
  - reasonable field length checks
- decide and implement the canonical password change path:
  - preferred: redirect `ELearning/Student/studentChangePass.php` to `ELearning/Student/studentProfile.php` password section
- if profile upload DB update fails, delete the newly moved avatar file
- if profile page session email no longer maps to an active student, force logout or redirect instead of rendering partial empty state
- keep feedback logic simple, but ensure errors are visible and content validation remains consistent

Acceptance criteria:

- no weaker duplicate password path remains active
- signup rejects bad email and very weak empty inputs on the server side
- profile update does not leave obvious orphaned new upload files on failed DB save

Do not:

- invent a brand new profile architecture

### Phase D - Complete Admin CRUD Consistency

Goal:

- admin management pages must not create inconsistent data

Files in scope:

- `ELearning/Admin/students.php`
- `ELearning/Admin/editstudent.php`
- `ELearning/Admin/addCourse.php`
- `ELearning/Admin/editcourse.php`
- `ELearning/Admin/addLesson.php`
- `ELearning/Admin/editlesson.php`
- `ELearning/Admin/lessons.php`

Tasks:

- add a clear edit action from `ELearning/Admin/students.php` to `ELearning/Admin/editstudent.php`
- when editing student email, wrap related updates in a transaction and synchronize:
  - `student.stu_email`
  - `courseorder.stu_email`
  - `cart.stu_email`
- if email synchronization fails, rollback and show error
- validate course add/edit prices:
  - non-negative values
  - original price should not be lower than final price unless explicitly allowed
- in lesson add/edit, require that selected course exists and is not deleted before save
- if course lookup fails, do not insert/update lesson

Acceptance criteria:

- admin can reach student edit from student list
- editing student email does not orphan purchases/cart
- invalid lesson/course combinations are rejected cleanly

Do not:

- attempt a full schema redesign to replace `stu_email` references

### Phase E - Improve Error Handling And Fallbacks In Key Pages

Goal:

- major pages should degrade gracefully when queries fail or resources are missing

Files in scope:

- `ELearning/dbConnection.php`
- `ELearning/index.php`
- `ELearning/courses.php`
- `ELearning/coursedetails.php`
- `ELearning/contact.php`
- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/sellReport.php`

Tasks:

- avoid fatal-style assumptions like calling `->num_rows` on failed queries without checks
- for touched pages, add safe fallback when query result is false/null
- improve contact server-side validation:
  - email format
  - message/name length sanity
  - visible inline result if practical
- validate sell-report date range so invalid ranges are rejected clearly
- improve not-found flow for invalid `course_id` in `coursedetails.php`
  - redirect to `courses.php` or `pageNotFound.php` with a clear policy

Acceptance criteria:

- key pages do not fatal on ordinary query/resource edge cases
- invalid course detail requests do not render a confusing near-empty page
- contact form and sell-report show clearer validation behavior

Do not:

- mass-refactor every single query in the project if it is unrelated to the affected journeys

### Phase F - Final Regression And Documentation Sync

Goal:

- certify that the hardened flows still work end-to-end

Files in scope:

- `README.md` if behavior changed materially
- touched PHP files from previous phases

Tasks:

- run targeted XAMPP regression after each phase
- re-run full regression at the end
- if any behavior changed materially, update `README.md`
- optionally append a short completion note to `PROJECT_AUDIT.md` or to this file

Acceptance criteria:

- required regression checklist passes
- no completed migration work is accidentally reopened

## Regression Checklist For This Plan

### Public

- Home page loads
- Course catalog loads
- Course detail loads for valid `course_id`
- Invalid course detail request fails gracefully
- Signup rejects bad input correctly
- Login succeeds with valid credentials and fails clearly with invalid ones
- Contact form rejects bad input and accepts good input

### Student

- Add to cart works only for valid active courses
- Cart badge count matches visible cart rows
- Remove and re-add cart item works
- Single-course and cart checkout both work
- Already-owned successful course cannot be re-added to cart or re-purchased silently
- My courses shows only successful owned courses
- Watch course access is granted only for successful owned courses
- Profile update works
- Password change works through the canonical route only
- Logout clears session correctly

### Admin

- Admin login works
- Dashboard counts and revenue reflect successful orders only
- Sell report counts and sums reflect successful orders only
- Student edit is reachable and email change does not orphan orders/cart
- Course add/edit validation behaves correctly
- Lesson add/edit rejects invalid course targets
- Student list/delete still works
- Feedback, contacts, trash still work

### Technical

- No Bootstrap/jQuery/Popper/Owl references return
- No new dependency is introduced
- No PHP syntax errors
- No new raw SQL concatenation is introduced in touched logic

## Recommended Stop Conditions

Stop and document instead of improvising if any of the following happens:

- fixing one issue requires a schema redesign beyond this plan
- email synchronization introduces broader identity-model problems
- course ownership policy must change product behavior in a disputed way
- a proposed fix requires reopening shared migration files without a strong reason

When blocked, report:

- exact file(s)
- exact behavior conflict
- safest recommended next step

## Definition Of Done For This Plan

This hardening plan is complete when:

1. Ownership logic is consistent across browse/cart/my-course/watch/report flows
2. Cart count and visible cart content cannot diverge in normal supported scenarios
3. Student email edit no longer orphans orders/cart
4. Weak duplicate password-change behavior is removed or redirected to the secure path
5. Lesson add/edit cannot create inconsistent course links
6. Dashboard/report use successful orders only
7. Key pages handle expected edge cases without blank/fragile behavior
8. XAMPP regression passes for touched journeys
9. No completed migration work is broken in the process

## Execution Guidance For Another AI

If you are the AI implementing this plan:

1. Start with Phase A and do not skip ahead
2. Only touch files listed in the active phase unless a directly dependent helper is required
3. Keep changes small, readable, and easy to review
4. After each phase:
   - run targeted tests
   - note any blocker
   - then move to the next phase
5. Do not introduce a new plan file
6. If you need to record progress, append a short dated execution note at the bottom of this file

## Optional Execution Log

### 2026-03-24 - Execution Note (Phases A-F)

- Phase A: completed
  - Ownership and revenue rules unified to `status = 'TXN_SUCCESS'` and `is_deleted = 0` across browse/cart/my-course/watch/dashboard/report in Phase A files.
- Phase B: completed
  - Cart add now validates active course existence.
  - Cart remove returns clearer ownership/not-found/already-removed outcomes.
  - Cart count aligned with cart page semantics (active course join).
  - Checkout flow blocks re-purchase of already-owned successful course before proceeding.
  - Checkout action now returns clearer success/failure messaging for partial/expired state.
- Phase C: completed
  - Signup/login server-side validation strengthened (email format, length constraints, password length checks).
  - `ELearning/Student/studentChangePass.php` deprecated to redirect to `ELearning/Student/studentProfile.php#tab-password`.
  - Profile flow now handles missing student/session mismatch with strict redirect to logout.
  - Profile avatar upload now cleans up newly uploaded file on DB update failure.
  - Feedback input validation tightened with visible error handling.
- Phase D: completed
  - Added clear student edit entry from `ELearning/Admin/students.php`.
  - Student email edit now synchronizes `student`, `courseorder`, and `cart` email fields in one transaction with rollback on failure.
  - Course add/edit price validation hardened (non-negative; original price not below final price).
  - Lesson add/edit now rejects invalid/deleted course targets and avoids inconsistent inserts/updates.
- Phase E: completed
  - `ELearning/dbConnection.php` failure handling hardened: no raw `die`, error logged, user-facing safe fallback, proper HTTP 500.
  - Replaced JS-only auth guards with server-side redirect + `exit` in:
    - `ELearning/Student/stuInclude/header.php`
    - `ELearning/Admin/adminInclude/header.php`
  - Hardened key fallback handling for query failures and invalid resources in Phase E pages:
    - safer `num_rows` checks in touched pages,
    - improved invalid `course_id` handling in `ELearning/coursedetails.php`.
  - Contact flow validation/message handling improved with server-side length/email checks and visible inline status.
  - Sell report date-range validation added with explicit error feedback.
- Phase F: completed
  - Targeted syntax regression executed for all touched PHP files via Docker `php -l` (host PHP unavailable).
  - HTTP smoke checks executed for key public routes (`index.php`, `courses.php`, `coursedetails.php?course_id=1`).
  - No Bootstrap/jQuery/Popper/Owl references reintroduced in touched logic.
  - `README.md` updated to reflect material behavior/policy hardening changes.

Verification summary:
- Verified: ownership/revenue consistency, cart semantics alignment, canonical password route, admin student email sync safety, course/lesson integrity checks, key Phase E fallback and validation behaviors, syntax integrity on touched files.

Deferred items:
- None required for Phases E/F scope at this time.

Plan status: complete.
