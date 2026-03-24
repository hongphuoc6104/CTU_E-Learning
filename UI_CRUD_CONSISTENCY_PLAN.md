# UI CRUD Consistency Plan

Generated: 2026-03-24

## Why This Plan Exists

The expansion wave added many new student, instructor, and admin screens across Plans 03, 04, and 05.

Functionally, those plans are already in place.
However, the UI layer is still inconsistent in several important ways:

- old pages and new pages do not feel like one coherent product
- button styles, card styles, spacing, badges, and table/list patterns are not unified
- some CRUD actions exist in code but are hard to discover in the UI
- some screens still mix English labels, Vietnamese labels, and inconsistent terminology
- some new pages feel more like internal tools than polished product screens

This plan is for visual consistency, CRUD discoverability, responsive cleanup, and UI-language normalization.

This is NOT a domain-model plan and NOT a business-logic expansion plan.

## Core Objective

Standardize the interface across public, student, instructor, and admin areas so that:

- users can clearly see the primary action on each screen
- CRUD actions are visible and understandable
- colors and status badges are used consistently
- labels and helper text are consistently Vietnamese
- mobile/tablet layouts remain usable without horizontal-scroll-heavy workflows
- existing business logic keeps working without being rewritten

## Current State Assumption

The following are already functionally implemented and must be preserved:

- Plan 03 learning experience
- Plan 04 instructor workflow
- Plan 05 admin operations
- current final-lane regression work under `ACTIVE_EXECUTION_*`

This plan should improve UI/UX consistency and missing CRUD discoverability on top of those features, not rebuild them.

## Execution Status Update

Current repository status:

- this plan has already started and several high-priority fixes are already in progress or partially implemented
- completed/partially completed work includes:
  - fixed-header offset cleanup for public pages using `mainInclude/header.php`
  - redesigned `ELearning/login.php`
  - redesigned `ELearning/signup.php`
  - redesigned `ELearning/Instructor/instructorLogin.php`
  - initial Vietnamese-label cleanup in instructor/admin shared navigation
- however, this plan is NOT complete yet

Still open:

- admin payment action discoverability consistency
- admin review/dashboard consistency
- broader Vietnamese label normalization across admin/instructor screens
- wider CRUD visibility audit and responsive cleanup across remaining instructor/admin pages

This plan should remain in the project root until those UI/CRUD consistency tasks are actually finished.

## Hard Constraints

1. Keep the current primary color direction and product look.
2. Keep the stack:
   - PHP
   - MySQL
   - Tailwind CSS
   - Vanilla JavaScript
3. Do not introduce Bootstrap, jQuery, Popper, or any component framework.
4. Do not rewrite the app into a SPA.
5. Do not change core business rules while doing visual cleanup.
6. Do not change order/payment/enrollment/course status enums.
7. Do not remove CSRF/session/auth checks.
8. Do not rename routes, form field names, POST keys, or query parameters unless every caller is updated safely.
9. Do not silently remove existing CRUD actions just because they are visually messy.
10. If an action already exists in code but is hard to discover, improve visibility first before adding new logic.

## Critical Safety Rules For Any AI Executing This Plan

### Rule 1 - UI first, logic second

Default assumption:

- change layout
- change spacing
- change hierarchy
- change labels
- change button placement
- change responsive presentation

Do NOT change business logic unless the current UI literally cannot expose the already-approved action.

### Rule 2 - Preserve form contracts

When editing forms:

- keep `name="..."` fields stable
- keep hidden fields stable
- keep CSRF token fields stable
- keep existing submit targets stable unless absolutely necessary
- keep success/error messaging behavior intact

### Rule 3 - Preserve runtime decisions

Do not alter conditions such as:

- when a button is shown for `awaiting_verification`
- when a course is `draft`, `pending_review`, `published`, `archived`
- who can verify payments
- who can publish courses
- who can access instructor/admin/student screens

### Rule 4 - Avoid touching SQL in this plan

This plan should not modify:

- `SQL/lms_db.sql`
- migration/check SQL files

unless a tiny UI-facing seed/data tweak is explicitly requested later.

### Rule 5 - Avoid broad CSS breakage

If adding shared UI styles:

- prefer small reusable utility classes or a narrow shared CSS layer
- avoid sweeping changes that could restyle every historical page unexpectedly
- verify impact on both old and new pages

## Approved UI Language Rules

All important workflow labels should use consistent Vietnamese.

### Status dictionary

Use these labels consistently:

- `draft` -> `Bản nháp`
- `pending_review` -> `Chờ duyệt`
- `published` -> `Đã xuất bản`
- `archived` -> `Lưu trữ`
- `pending` -> `Chờ nộp thanh toán`
- `awaiting_verification` -> `Chờ xác minh`
- `paid` -> `Đã thanh toán`
- `failed` -> `Thất bại`
- `cancelled` -> `Đã hủy`
- `refunded` -> `Đã hoàn tiền`
- `verified` -> `Đã xác minh`
- `rejected` -> `Đã từ chối`
- `scheduled` -> `Đã lên lịch`
- `live` -> `Đang diễn ra`
- `ended` -> `Đã kết thúc`
- `replay_available` -> `Có replay`

### CRUD action labels

Prefer these labels:

- `Tạo mới`
- `Chỉnh sửa`
- `Xem chi tiết`
- `Xóa`
- `Khôi phục`
- `Duyệt`
- `Từ chối`
- `Xác minh`
- `Khóa`
- `Mở khóa`
- `Gắn replay`
- `Tiếp tục học`

Avoid mixing English action labels on user-facing buttons if a Vietnamese equivalent already exists.

## Approved Visual Rules

### Buttons

The whole project should converge to these button roles:

- Primary action:
  - solid primary background
  - white text
  - bold label
- Secondary action:
  - white/light background
  - border
  - slate text
- Destructive action:
  - red background or red outline depending on emphasis
- Success action:
  - emerald background for confirm/verify/approve flows

### Cards

Use one shared pattern where practical:

- rounded-xl or rounded-2xl
- white background
- soft border `border-slate-100` or `border-slate-200`
- small shadow, not heavy glow everywhere
- consistent internal padding

### Status badges

Use a consistent pill/badge shape:

- rounded-full or rounded-lg
- small uppercase or compact bold text
- color mapping should be stable across admin/instructor/student screens

### Tables vs cards

- desktop: tables are acceptable for dense admin data
- tablet/mobile: important workflows should avoid unusable wide tables
- if a page is action-heavy, consider stacked cards/detail blocks on smaller screens

## High-Priority Problems To Solve

### 1. Admin payment action discoverability

Priority files:

- `ELearning/Admin/payments.php`
- `ELearning/Admin/paymentDetails.php`

Required fixes:

- make `Xác minh` and `Từ chối` obvious when a payment is actionable
- make non-actionable states clearly explain why buttons are hidden
- improve hierarchy between payment proof, order status, payment status, and action panel

### 2. Admin review and operations consistency

Priority files:

- `ELearning/Admin/courseReview.php`
- `ELearning/Admin/courses.php`
- `ELearning/Admin/instructors.php`
- `ELearning/Admin/liveSessions.php`
- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/adminInclude/header.php`

Required fixes:

- unify badges, action buttons, cards, and tables
- reduce “many tiny actions in one row” where possible
- make dashboard workflow priority obvious
- make review actions and status transitions easier to scan

### 3. Instructor CRUD consistency

Priority files:

- `ELearning/Instructor/instructorInclude/header.php`
- `ELearning/Instructor/courses.php`
- `ELearning/Instructor/addCourse.php`
- `ELearning/Instructor/editCourse.php`
- `ELearning/Instructor/sections.php`
- `ELearning/Instructor/learningItems.php`
- `ELearning/Instructor/liveSessions.php`
- `ELearning/Instructor/students.php`

Required fixes:

- unify course/section/item/live CRUD patterns
- reduce horizontal-scroll-heavy patterns where possible
- split large forms into clearer groups
- make “next action” obvious (edit, add item, schedule live, submit review)

### 4. Public and student consistency with newer screens

Priority files:

- `ELearning/mainInclude/header.php`
- `ELearning/Student/stuInclude/header.php`
- `ELearning/index.php`
- `ELearning/courses.php`
- `ELearning/coursedetails.php`
- `ELearning/Student/myCart.php`
- `ELearning/Student/orderDetails.php`
- `ELearning/Student/myCourse.php`
- `ELearning/Student/watchcourse.php`
- `ELearning/login.php`
- `ELearning/signup.php`

Required fixes:

- keep card hierarchy consistent with newer instructor/admin work
- standardize CTA sizes, icon spacing, and helper text
- make step/progress/status visualization feel like one product family

## Missing CRUD / Visibility Audit Targets

The AI executing this plan must explicitly verify these before claiming completion:

- admin can clearly see both `Xác minh` and `Từ chối` actions on actionable payment states
- admin can clearly see review decision actions on pending-review courses
- instructor can clearly see edit/add/submit actions on owned courses
- live-session replay attachment action is visible and understandable
- users can tell when actions are unavailable and why

If a CRUD action is genuinely missing in code, the AI may implement it ONLY if:

- it is already implied by existing business scope from Plans 04/05
- it does not require schema expansion
- it does not rewrite existing workflows

## Files Allowed To Change

### Most likely

- `ELearning/mainInclude/header.php`
- `ELearning/mainInclude/footer.php`
- `ELearning/Student/stuInclude/header.php`
- `ELearning/Student/myCart.php`
- `ELearning/Student/orderDetails.php`
- `ELearning/Student/myCourse.php`
- `ELearning/Student/watchcourse.php`
- `ELearning/login.php`
- `ELearning/signup.php`
- `ELearning/courses.php`
- `ELearning/coursedetails.php`
- `ELearning/index.php`
- `ELearning/Admin/adminInclude/header.php`
- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/payments.php`
- `ELearning/Admin/paymentDetails.php`
- `ELearning/Admin/courseReview.php`
- `ELearning/Admin/instructors.php`
- `ELearning/Admin/liveSessions.php`
- `ELearning/Admin/courses.php`
- `ELearning/Instructor/instructorInclude/header.php`
- `ELearning/Instructor/courses.php`
- `ELearning/Instructor/addCourse.php`
- `ELearning/Instructor/editCourse.php`
- `ELearning/Instructor/sections.php`
- `ELearning/Instructor/learningItems.php`
- `ELearning/Instructor/liveSessions.php`
- `ELearning/Instructor/students.php`
- `ELearning/css/tailwind.css`

### Allowed new files if truly needed

- a small shared UI stylesheet such as `ELearning/css/ui-consistency.css`
- a tiny shared UI helper include if needed for repeated badge/button meta

## Files Not To Change By Default

- `SQL/*.sql`
- auth/session bootstrap files
- database helper behavior unrelated to UI exposure
- payment/order status logic unless needed to expose an already-approved action
- quiz/progress/enrollment logic

## Recommended Execution Order

### Phase 0 - UI audit and token alignment

Required output:

- list current button patterns
- list current badge patterns
- list current card/table patterns
- list inconsistent Vietnamese labels
- choose one normalized pattern set before broad edits

### Phase 1 - Shared shell alignment

Do first:

- public header/footer
- student header
- admin header
- instructor header

Goal:

- same spacing language
- same action/button density
- same top-level navigation behavior

### Phase 2 - Admin critical action screens

Do next:

- `payments.php`
- `paymentDetails.php`
- `courseReview.php`
- `courses.php`

Goal:

- critical admin actions are visible and understandable
- destructive/approval actions are grouped well
- hidden-state explanations are clear

### Phase 3 - Instructor CRUD screens

Do after admin critical screens:

- `courses.php`
- `learningItems.php`
- `liveSessions.php`
- `sections.php`
- `addCourse.php`
- `editCourse.php`

Goal:

- consistent CRUD language
- consistent forms
- responsive action layout

### Phase 4 - Public/student visual convergence

Do after admin/instructor action cleanup:

- align cards, CTA hierarchy, status badges, and step blocks across public/student flows

### Phase 5 - Responsive polish and leftovers

Only after critical CRUD visibility is fixed:

- table-to-card fallback where needed
- confirm-modal polish
- empty states
- helper text polish

## Acceptance Criteria

1. New and old pages share a recognizable common visual language.
2. Primary, secondary, destructive, success actions look consistent.
3. Status badges are visually and semantically consistent.
4. Vietnamese labels are normalized across public, student, instructor, and admin areas.
5. Key admin CRUD actions are easy to find, especially payment verify/reject and course review actions.
6. Instructor CRUD flows are visually coherent and mobile/tablet-usable.
7. No existing business flow is broken by the UI cleanup.

## Required Verification Before Claiming Completion

The AI executing this plan must verify at least:

- student can still log in, buy, open order details, and upload proof
- instructor can still log in, create/edit course, manage items/live sessions
- admin can still log in, review courses, verify payments, reject payments
- UI labels remain readable and correct in Vietnamese
- no high-priority page loses its main CTA

## Mandatory Reporting Format

When starting, report:

- Current plan file
- Objective
- Files expected to change
- Guardrails acknowledged
- High-priority screens to fix first

When finishing, report:

- Completed plan
- UI rules applied
- Files changed
- CRUD visibility fixes completed
- Tests or smoke checks run
- Remaining polish items
- Ready for rollout: yes/no

## Important Final Note

If this plan conflicts with active release-blocker work, the AI must:

- keep release-blocker logic intact
- prefer minimal UI-only changes
- stop short of altering business logic unless the owner explicitly approves it

This plan exists to unify and expose what is already implemented, not to invent new business scope.
