# Project Audit And Execution Plan

Generated: 2026-03-23
Updated: 2026-03-24

This file is the single source of truth for the next agent or developer.

It serves two purposes:

1. Record the most important defects already found in the project
2. Define the approved execution plan for migrating the project to the target stack below

Do not create a second planning file unless explicitly requested.

## Approved Target Stack

The project must converge to this stack only:

- Backend: `PHP + MySQL`
- Frontend: `HTML + CSS + Tailwind CSS + Vanilla JavaScript`

The following frontend dependencies must be removed completely:

- `Bootstrap`
- `jQuery`
- `Popper`
- `Owl Carousel`

Allowed supporting frontend dependencies:

- `Tailwind CSS`
- one icon library only if still needed, preferably keep current icons consistent during migration

## Approved Technical Direction

These decisions are already made. The next agent should follow them without reopening the debate.

1. Use current documentation, not legacy patterns
   - PHP should target a currently supported branch, recommended minimum `PHP 8.3+`
   - Tailwind should follow the current docs line (`v4.x`)
   - Replace `$.ajax` and other jQuery patterns with `fetch()` and modern DOM APIs

2. Do not keep the hybrid frontend stack
   - Remove mixed `Bootstrap + Tailwind + jQuery`
   - End state must be `Tailwind + custom CSS + vanilla JS`

3. Do not use Tailwind Play CDN for the final maintained implementation
   - Tailwind docs state Play CDN is for development only, not production
   - The preferred end state is Tailwind built to a static CSS asset

4. Preserve the backend architecture
   - Keep server-rendered PHP pages
   - Do not migrate to React, Vue, Angular, Node, Laravel, or another backend framework

5. Security and correctness fixes are still mandatory during frontend migration
   - Do not ship UI refactors that reintroduce SQL injection, weak auth, or CSRF gaps

## External References Used For This Direction

- Tailwind CSS docs: current installation guidance indicates build-based setup is preferred; Play CDN is development-only
- PHP supported versions page: use a supported modern branch; recommended minimum is `PHP 8.3+`
- MDN Fetch API docs: use `fetch()` as the standard replacement for `XMLHttpRequest` / jQuery AJAX

## Executive Summary

The project has completed the migration away from Bootstrap/jQuery at the runtime and disk level, and the runtime regression pass has now been executed successfully in a XAMPP-based environment. The only remaining follow-up is optional low-severity data-integrity hardening if the team chooses to close it.

Current reality:

- Public, student, admin, and checkout shells now run on Tailwind-style markup with vanilla JavaScript behavior
- Shared layouts no longer load Bootstrap, jQuery, Popper, or Owl at runtime
- Core auth/cart/admin-login flows use `fetch()` + DOM APIs instead of jQuery AJAX
- Tailwind now has a static build pipeline and compiled asset in the repo
- Legacy frontend dependency files have been removed from disk
- Seeded lesson links in `SQL/lms_db.sql` now use valid remote sources instead of missing local videos
- Runtime regression has now been executed successfully with XAMPP PHP + XAMPP MariaDB
- The plan can be considered complete unless the team wants to continue with optional low-severity data-integrity hardening

Therefore the next agent must not do random page-by-page cleanup. The work must follow the execution plan in this file.

## Current Dependency Map

### Shared includes and layout files

- Public:
  - `ELearning/mainInclude/header.php`
  - `ELearning/mainInclude/footer.php`
- Student:
  - `ELearning/Student/stuInclude/header.php`
  - `ELearning/Student/stuInclude/footer.php`
- Admin:
  - `ELearning/Admin/adminInclude/header.php`
  - `ELearning/Admin/adminInclude/footer.php`

These shared files are the highest-leverage migration points. Removing Bootstrap/jQuery from them affects most pages at once.

### Frontend dependencies still present

Current active frontend stack:

- `Tailwind`
  - build pipeline present via `package.json`, `tailwind.config.js`, `ELearning/css/tailwind.input.css`
  - compiled asset present at `ELearning/css/tailwind.css`
- icon assets
  - local Font Awesome bundle still used by the app UI

Removed from runtime and removed from disk:

- `Bootstrap`
- `jQuery`
- `Popper`
- `Owl Carousel`

### Highest-risk remaining hotspots

There are no remaining high-risk migration hotspots for the approved plan.

Only optional follow-up remains:

1. Low-severity data-integrity hardening
   - `SQL/lms_db.sql`
   - only if the team decides to close `AUD-18` instead of deferring it

## Approved Execution Plan

The next agent must execute the migration in the following phases and order.

Do not skip the order unless a blocking issue forces it.

### Phase 1 - Freeze Standards And Establish Frontend Baseline

Goal: define the final frontend architecture and stop further drift.

Required outcomes:

- Confirm the project standard is `PHP + MySQL + HTML + CSS + Tailwind + Vanilla JS`
- Keep server-rendered PHP pages
- Remove any assumption that Bootstrap/jQuery remain temporary dependencies
- Prepare for Tailwind build output instead of CDN-only usage

Required implementation decisions:

- Tailwind should be built into a static CSS asset for the maintained final state
- Avoid introducing any new frontend runtime dependency unless explicitly justified
- Keep one icon strategy only

Acceptance criteria:

- README and code direction no longer suggest Bootstrap or jQuery are part of the long-term stack

### Phase 2 - Clean Shared Layouts First

Goal: refactor the shared public/student/admin layout files before touching isolated pages.

Files in scope first:

- `ELearning/mainInclude/header.php`
- `ELearning/mainInclude/footer.php`
- `ELearning/Student/stuInclude/header.php`
- `ELearning/Student/stuInclude/footer.php`
- `ELearning/Admin/adminInclude/header.php`
- `ELearning/Admin/adminInclude/footer.php`

Tasks:

- Remove Bootstrap CSS imports
- Remove jQuery, Popper, and Bootstrap JS imports
- Remove Owl CSS/JS imports if no longer used
- Keep CSRF meta tags and session bootstrap intact
- Replace any Bootstrap modal/dropdown behavior with vanilla JS components
- Keep path references case-correct for Linux (`Admin/`, not `admin/`)

Acceptance criteria:

- Shared includes render without loading Bootstrap, jQuery, Popper, or Owl assets
- Public, student, and admin shells still load correctly

### Phase 3 - Migrate Core Frontend Behavior To Vanilla JS

Goal: remove jQuery from the app logic layer.

Files in scope:

- `ELearning/js/ajaxrequest.js`
- `ELearning/js/adminajaxrequest.js`
- `ELearning/js/custom.js`
- inline JS in pages such as `ELearning/Student/myCart.php`

Tasks:

- Replace `$(document).ready(...)` with DOM-ready or deferred-script patterns
- Replace `$.ajax(...)` with `fetch()`
- Replace jQuery selectors/events with `querySelector`, `querySelectorAll`, `addEventListener`
- Replace jQuery UI feedback patterns with plain DOM rendering
- Remove old code targeting dead selectors and outdated layouts

Acceptance criteria:

- No application JS depends on jQuery APIs
- Signup, login, cart add/remove/count, and admin AJAX actions still work

### Phase 4 - Rewrite Remaining Bootstrap-Heavy Pages

Goal: remove legacy Bootstrap markup from pages that still strongly depend on it.

Priority order:

1. `ELearning/contact.php`
2. `ELearning/Student/studentChangePass.php`
3. `ELearning/Admin/editstudent.php`
4. `ELearning/studentRegistration.php`
5. any remaining modal/dialog page in shared footer/layout

Tasks:

- Replace `container`, `row`, `col-*`, `form-group`, `form-control`, `btn`, modal classes
- Rewrite layout and form styling in Tailwind
- Keep validation/security logic intact or improve it during rewrite
- Remove inline behavior that was only needed because of Bootstrap

Acceptance criteria:

- These pages contain no Bootstrap classes and remain functionally correct

### Phase 5 - Normalize Tailwind Usage And Build Pipeline

Goal: move from CDN convenience to a maintainable Tailwind setup.

Tasks:

- Set up Tailwind according to current docs for a static build output
- Generate a dedicated compiled CSS asset
- Replace runtime CDN Tailwind usage in shared headers with the built asset
- Keep custom CSS only for project-specific styles that Tailwind should not express directly
- Remove CSS written only to patch Bootstrap/Tailwind conflicts

Notes:

- The final project should not depend on Tailwind Play CDN in production-like operation
- If temporary CDN usage is needed during migration, remove it before completion

Acceptance criteria:

- Tailwind styles come from a built static asset, not the Play CDN
- Shared headers no longer load Tailwind CDN scripts

### Phase 6 - Remove Legacy Dependencies And Dead Assets

Goal: delete the old frontend stack only after replacement is complete.

Delete candidates once unused:

- `ELearning/css/bootstrap.min.css`
- `ELearning/js/bootstrap.min.js`
- `ELearning/js/jquery.min.js`
- `ELearning/js/popper.min.js`
- `ELearning/css/owl.min.css`
- `ELearning/css/owl.theme.min.css`
- `ELearning/js/owl.min.js`
- `ELearning/js/testyslider.js`
- `ELearning/css/testyslider.css`

Also review:

- old custom scripts that target dead markup
- duplicate font imports
- mixed icon-loading strategies

Acceptance criteria:

- No deleted dependency is still referenced by any page
- Global search confirms no runtime dependency remains on Bootstrap/jQuery/Popper/Owl

### Phase 7 - Preserve And Complete Security/Correctness Fixes

Goal: ensure frontend migration does not overshadow critical backend/app fixes.

Still-required correctness/security work includes:

- Prepared statements everywhere dynamic SQL remains
- No `$_REQUEST` in new or updated flows
- CSRF coverage for all state-changing actions
- Session hardening kept intact
- Checkout must remain server-authoritative
- Soft-delete behavior must stay consistent
- File uploads must be validated safely

Baseline target list from the original audit (most of these are now handled; use the verified review and next execution focus sections below for the current open work):

- `ELearning/Student/studentChangePass.php`
- `ELearning/Admin/addCourse.php`
- `ELearning/paymentstatus.php`
- `ELearning/Admin/adminPaymentStatus.php`
- `ELearning/prepare_soft_delete.php`

Acceptance criteria:

- No regression against previously fixed security issues
- No remaining obvious raw-SQL concatenation in high-risk flows

### Phase 8 - Repair Asset And Seed Drift

Goal: align UI references, assets, and sample data.

Tasks:

- Resolve missing references such as banner/fallback images
- Decide whether seeded lessons use shipped local videos or valid remote sources
- Remove or replace references to missing files
- Validate public and admin image fallbacks

Acceptance criteria:

- No broken key images on major pages
- Seeded lesson playback works for supported lesson types

### Phase 9 - Final Regression And Documentation Pass

Goal: leave the repo in a consistent maintainable state.

Tasks:

- Update `README.md` to reflect the new stack and run process
- Document PHP version expectations
- Document Tailwind build workflow
- Remove outdated references to old frontend dependencies
- Run a release checklist and verify the major flows

Acceptance criteria:

- A new developer can run the project and understand the stack from `README.md` alone

### Execution Log - 2026-03-24 (Verified Review)

Status snapshot verified against the current worktree:

- Phase 1: completed
  - `README.md` now reflects the approved target stack (`PHP + MySQL + HTML + CSS + Tailwind CSS + Vanilla JavaScript`)
  - project direction now targets `PHP 8.3+`
  - no extra planning file was introduced

- Phase 2: completed
  - shared includes no longer load Bootstrap/jQuery/Popper/Owl at runtime
  - verified in:
    - `ELearning/mainInclude/header.php`
    - `ELearning/mainInclude/footer.php`
    - `ELearning/Student/stuInclude/header.php`
    - `ELearning/Student/stuInclude/footer.php`
    - `ELearning/Admin/adminInclude/header.php`
    - `ELearning/Admin/adminInclude/footer.php`
  - admin modal behavior in public footer was rewritten to vanilla JS
  - Linux path issue was corrected to `Admin/...`

- Phase 3: completed
  - `ELearning/js/ajaxrequest.js` migrated to `fetch()` + DOM APIs
  - `ELearning/js/adminajaxrequest.js` migrated to `fetch()` + DOM APIs
  - `ELearning/js/custom.js` no longer uses jQuery
  - inline cart-removal behavior in `ELearning/Student/myCart.php` no longer depends on jQuery
  - app runtime no longer requires jQuery for signup/login/cart/admin-login flows

- Phase 4: substantially completed for priority pages
  - verified Tailwind/vanilla rewrites in:
    - `ELearning/contact.php`
    - `ELearning/Student/studentChangePass.php`
    - `ELearning/Admin/editstudent.php`
    - `ELearning/studentRegistration.php`
  - no Bootstrap-heavy runtime markup remains in the reviewed priority pages

- Phase 5: completed
  - Tailwind static pipeline is present:
    - `package.json`
    - `tailwind.config.js`
    - `ELearning/css/tailwind.input.css`
    - `ELearning/css/tailwind.css`
  - shared/public/student/admin pages now reference built Tailwind CSS instead of Tailwind Play CDN
  - the final direction is aligned with the approved Tailwind build-based approach

- Phase 6: completed
  - after a final reference audit, legacy dependency files were removed from disk:
    - `ELearning/css/bootstrap.min.css`
    - `ELearning/js/bootstrap.min.js`
    - `ELearning/js/jquery.min.js`
    - `ELearning/js/popper.min.js`
    - `ELearning/css/owl.min.css`
    - `ELearning/css/owl.theme.min.css`
    - `ELearning/js/owl.min.js`
    - `ELearning/js/testyslider.js`
    - `ELearning/css/testyslider.css`
  - app source no longer contains runtime references to Bootstrap/jQuery/Popper/Owl in reviewed PHP and JS files

- Phase 7: completed for the remaining planned blockers
  - verified improvements and preserved fixes include:
    - `csrf.php`
    - `session_bootstrap.php`
    - `password_verify(...)`
    - `session_regenerate_id(true)`
    - server-authoritative pending checkout flow in `ELearning/checkout.php` and `ELearning/checkout_action.php`
    - cart re-add fix in `ELearning/cart_api.php` using `ON DUPLICATE KEY UPDATE is_deleted = 0`
    - avatar upload validation in `ELearning/Student/studentProfile.php`
    - admin utility SQL cleanup in:
      - `ELearning/Admin/trash.php`
      - `ELearning/Admin/adminInclude/header.php`
    - legacy public schema mutation script removed from web root:
      - `ELearning/prepare_soft_delete.php`
    - legacy Paytm-facing status screens replaced with disabled/safe messaging in:
      - `ELearning/paymentstatus.php`
      - `ELearning/Admin/adminPaymentStatus.php`
  - no `$_REQUEST` usage remains in the PHP app source

- Phase 8: completed
  - stale banner/default image references were cleaned up in the main app flows
  - admin fallback image issue was corrected
  - seeded lesson media strategy was switched to valid remote sources in `SQL/lms_db.sql`
  - `SQL/lms_db.sql` no longer references `lessonvid/` or `video*.mp4` for seeded lesson playback

- Phase 9: completed
  - `README.md` is materially better and aligned with the target stack
  - static verification completed:
    - no runtime legacy dependency references found in app source
    - no `lessonvid/` seed references remain in `SQL/lms_db.sql`
    - XAMPP PHP CLI is available at `/opt/lampp/bin/php`
    - PHP lint passed with `TOTAL_ERRORS=0`
  - runtime regression executed successfully in a XAMPP-based environment using:
    - XAMPP PHP CLI server on `127.0.0.1:8080`
    - XAMPP MariaDB on `127.0.0.1:3306`
  - verified runtime flows:
    - public home, courses, course details, signup page, contact page
    - student signup/login, cart add/remove/re-add/count, checkout, my courses, watch course
    - student profile update, password change, re-login, feedback submit
    - admin login, dashboard, feedback, contacts, trash pages
    - admin add/search/delete course, add/search/delete lesson, edit student

### Status By Audit Finding

- AUD-01: resolved
- AUD-02: resolved
- AUD-03: resolved
- AUD-04: resolved
- AUD-05: resolved
- AUD-06: resolved
- AUD-07: resolved
- AUD-08: resolved
- AUD-09: resolved
- AUD-10: resolved
- AUD-11: resolved
- AUD-12: resolved
- AUD-13: resolved
- AUD-14: resolved
- AUD-15: resolved
- AUD-16: resolved
- AUD-17: resolved
- AUD-18: partial
- AUD-19: resolved

### Remaining Blockers / Incomplete Areas

There are no blocking items left for the main migration plan.

Deferred optional follow-up:

- Low-severity seed/data-integrity hardening remains available if the team wants to close `AUD-18`
  - affected area:
    - `SQL/lms_db.sql`
  - impact: does not block project completion for the approved migration plan

### Next Execution Focus

The main migration plan is now complete.

Only optional follow-up remains:

1. Decide whether to close or defer the remaining low-severity data-integrity work
   - specifically `AUD-18` around seed consistency and relational hardening

2. Do not reopen completed phases unless a regression is found

## Required Regression Checklist

The next agent should use this checklist repeatedly during migration.

### Public flows

- Home page loads
- Courses listing loads
- Course details page loads
- Signup works
- Student login works
- Contact form works

### Student flows

- Cart add works
- Cart remove works
- Cart re-add after removal works
- Checkout works
- Purchased courses page works
- Watch course page works
- Student profile update works
- Student password change works

### Admin flows

- Admin login works
- Dashboard loads
- Course CRUD works
- Lesson CRUD works
- Student edit works
- Feedback/contacts/trash pages work

### Technical checks

- No Bootstrap/jQuery/Popper/Owl assets are loaded in final state
- No case-sensitive broken links (`Admin/` vs `admin/`)
- No missing critical assets
- No PHP syntax errors

## Detailed Findings

The findings below are the original baseline audit findings. Use the verified execution log and status summary above as the current source of truth for what is already fixed, partially fixed, or still open.

### AUD-01 - Critical - Database bootstrap is inconsistent with application code

**Summary**

The application expects schema fields and tables that are not present in the main SQL dump. A clean import from the documented instructions is not enough to run the current code.

**Evidence**

- `SQL/lms_db.sql:51` - `course` table has no `is_deleted`
- `SQL/lms_db.sql:82` - `courseorder` table has no `is_deleted`
- `SQL/lms_db.sql:167` - `student` table has no `is_deleted`
- `SQL/fix_missing_db_updates.sql:4` - creates `contact_message`
- `SQL/fix_missing_db_updates.sql:16` - adds missing `is_deleted` columns
- `ELearning/index.php:101` - queries `course WHERE is_deleted=0`
- `ELearning/courses.php:22` - queries `course WHERE is_deleted=0`
- `ELearning/Admin/adminDashboard.php:13` - counts only non-deleted rows
- `ELearning/Admin/contacts.php:20` - queries `contact_message`
- `ELearning/Admin/trash.php:44` - queries `contact_message`
- `README.md:75` - still instructs users to import `cart_table.sql`, which is no longer present in `SQL/`

**Impact**

- Fresh installs fail with SQL errors
- Different developers can end up with different schemas
- Bugs appear environment-specific even though the cause is missing schema updates

**Required Fix**

- Merge all required schema changes into one supported install path
- Either:
  - update `SQL/lms_db.sql` so it already contains `contact_message` and all required `is_deleted` columns, or
  - keep a formal ordered migration system and document the exact migration order
- Remove outdated setup instructions from `README.md`
- Remove or protect any runtime schema mutation scripts

**Acceptance Test**

- Drop `lms_db`
- Follow `README.md` only
- App should load public, admin, student, cart, contact, and trash pages without any manual SQL patching

---

### AUD-02 - Critical - Checkout/order creation trusts browser input and can be forged

**Summary**

The checkout flow accepts order id, student email, amount, and checkout type directly from POST data, then marks the transaction as successful without server-side verification.

**Evidence**

- `ELearning/checkout_action.php:11` - trusts `$_POST['ORDER_ID']`
- `ELearning/checkout_action.php:12` - trusts `$_POST['CUST_ID']`
- `ELearning/checkout_action.php:13` - trusts `$_POST['TXN_AMOUNT']`
- `ELearning/checkout_action.php:15` - hardcodes `TXN_SUCCESS`
- `ELearning/checkout.php:91` - exposes customer email in form
- `ELearning/checkout.php:96` - exposes amount in form

**Impact**

- A user can forge successful orders without paying
- A user can tamper with amount or email
- Revenue reports become untrustworthy

**Required Fix**

- Never trust posted email or amount
- Build order records server-side before payment
- Use the logged-in session identity only
- Recalculate prices from the database only
- If using a mock payment flow, still verify against a server-side pending order record
- Use prepared statements for all checkout queries

**Acceptance Test**

- Tampering hidden form values must not change the purchased user, course, or amount
- Orders must only be created from server-side state

---

### AUD-03 - Critical - Single-course checkout can enroll the wrong course

**Summary**

The selected course for direct purchase is stored in session on the course detail page, but the checkout form only posts the price, not the course id.

**Evidence**

- `ELearning/coursedetails.php:24` - stores `$_SESSION['course_id']`
- `ELearning/coursedetails.php:79` - posts checkout form
- `ELearning/coursedetails.php:80` - posts only `course_price` as `id`
- `ELearning/checkout_action.php:60` - reads `$_SESSION['course_id']`

**Impact**

- User can end up enrolled in a stale course from a previous page view
- Purchase result depends on session state instead of explicit server-side order state

**Required Fix**

- Stop using session as the source of truth for the selected course
- Create a pending order row server-side with exact course id and amount
- Post only a server-generated order token/id
- Validate that the pending order belongs to the current logged-in user

**Acceptance Test**

- Open course A, then course B, then attempt purchase: only the intended course should be enrolled

---

### AUD-04 - High - Cart checkout inflates order amounts and processes soft-deleted cart rows

**Summary**

Cart checkout uses the full cart total for every inserted `courseorder` row and does not filter out soft-deleted cart entries.

**Evidence**

- `ELearning/checkout_action.php:38` - selects cart rows without `is_deleted=0`
- `ELearning/checkout_action.php:48` - inserts one row per course but reuses the same full `amount`
- `ELearning/Admin/adminDashboard.php:16` - sums `courseorder.amount`
- `ELearning/Admin/sellReport.php:28` - sums `courseorder.amount`

**Impact**

- Revenue is overstated
- A removed cart item may still be purchased

**Required Fix**

- Load only active cart rows (`is_deleted=0`)
- Recalculate item prices from the `course` table
- If keeping the current table design, store per-course price on each order row
- Prefer introducing an `orders` + `order_items` model if time permits

**Acceptance Test**

- Remove one item from cart, then checkout: removed item must not be purchased
- Admin revenue must equal the sum of actual purchased course prices, not cart total multiplied by item count

---

### AUD-05 - High - Cart soft-delete conflicts with unique constraint

**Summary**

The `cart` table has a unique key on `(stu_email, course_id)`, but the code soft-deletes cart rows instead of removing them. Re-adding the same course can fail.

**Evidence**

- `SQL/lms_db.sql:279` - `cart` table definition
- `SQL/lms_db.sql:285` - unique key `unique_cart_item`
- `ELearning/cart_api.php:27` - checks only active cart rows
- `ELearning/cart_api.php:35` - always inserts new row
- `ELearning/cart_api.php:44` - remove action sets `is_deleted=1`

**Impact**

- User may be unable to re-add a previously removed course
- Behavior depends on whether the old row still exists in soft-deleted state

**Required Fix**

- Use one of these patterns:
  - `INSERT ... ON DUPLICATE KEY UPDATE is_deleted=0, added_date=CURRENT_TIMESTAMP`, or
  - query for soft-deleted row first and revive it instead of inserting
- Make cart behavior consistent with the chosen soft-delete policy

**Acceptance Test**

- Add course to cart, remove it, add it again: the second add must succeed and show exactly one active cart row

---

### AUD-06 - High - SQL injection exists across multiple modules

**Summary**

The project still contains direct string concatenation into SQL in login, profile, cart, public, and admin code.

**Representative Evidence**

- `ELearning/Admin/admin.php:14`
- `ELearning/Student/addstudent.php:13`
- `ELearning/Student/addstudent.php:38`
- `ELearning/coursedetails.php:25`
- `ELearning/cart_api.php:19`
- `ELearning/Student/studentProfile.php:42`
- `ELearning/Admin/editstudent.php:48`
- `ELearning/Admin/courses.php:24`
- `ELearning/Admin/students.php:23`

**Impact**

- Login bypass or user enumeration risks
- Data corruption or unauthorized data access
- Admin actions become abuse-prone

**Required Fix**

- Convert every dynamic SQL statement to prepared statements
- Validate and cast ids before query execution
- Stop using `$_REQUEST`; prefer explicit `$_GET` and `$_POST`
- Add a lightweight data-access helper if repetitive prepared-statement code becomes too noisy

**Acceptance Test**

- Attempt classic payloads such as quote-based injection in login, search, detail pages, and edit forms; no query logic should break or broaden scope

---

### AUD-07 - High - Password handling and session hardening are incomplete

**Summary**

The repository still seeds plaintext passwords and login code still accepts plaintext fallback. Session hardening is also missing.

**Evidence**

- `SQL/lms_db.sql:42` - admin seeded as `admin@gmail.com / admin`
- `SQL/lms_db.sql:180` - student passwords are plaintext
- `ELearning/Admin/admin.php:19` - plaintext fallback accepted
- `ELearning/Student/addstudent.php:44` - plaintext fallback accepted
- `ELearning/logout.php:2` - destroys session only
- No `session_regenerate_id()` found in the codebase during audit

**Impact**

- Fresh installs are immediately weak from a security standpoint
- Session fixation risk remains higher than necessary
- Password migration never truly completes while fallback stays enabled

**Required Fix**

- Replace seeded plaintext passwords with hashed values or force setup-time password creation
- Remove plaintext fallback after one controlled migration step
- Call `session_regenerate_id(true)` after successful login
- Set cookie flags: `HttpOnly`, `Secure` where applicable, and `SameSite=Lax` or stricter
- On logout, also unset session data and expire the cookie explicitly

**Acceptance Test**

- Fresh seeded database must not expose plaintext credentials
- After login, session id must change
- Old session cookie must become unusable after logout

---

### AUD-08 - High - No CSRF protection on state-changing requests

**Summary**

Forms and AJAX endpoints that create, update, restore, or delete data do not use CSRF tokens.

**Representative Evidence**

- `ELearning/contact.php:25`
- `ELearning/cart_api.php:15`
- `ELearning/Admin/contacts.php:13`
- `ELearning/Admin/trash.php:17`
- `ELearning/Student/stufeedback.php:64`

**Impact**

- Logged-in users or admins can be tricked into performing unwanted actions from another site

**Required Fix**

- Add per-session CSRF tokens to all state-changing forms and AJAX calls
- Reject requests with missing or invalid tokens
- Keep `SameSite` cookies as defense-in-depth, not as the only mitigation

**Acceptance Test**

- Replaying a POST without a valid CSRF token must fail

---

### AUD-09 - High - Student avatar upload is not safely validated

**Summary**

The student profile page accepts uploaded files for avatars with no extension, MIME, or size validation before storing them inside the web root.

**Evidence**

- `ELearning/Student/studentProfile.php:34` - reads uploaded file name
- `ELearning/Student/studentProfile.php:38` - uses `basename()` only
- `ELearning/Student/studentProfile.php:41` - moves uploaded file directly

**Impact**

- Unsafe files can be uploaded into a web-accessible directory
- Large or invalid files can be stored without limit

**Required Fix**

- Restrict allowed file types and validate by MIME/content, not extension only
- Enforce a small size limit
- Generate a random server-side filename
- Prefer storing uploads outside the web root and serving via controlled endpoints if possible
- At minimum, store only validated image formats and normalize them

**Acceptance Test**

- Non-image uploads must be rejected
- Oversized uploads must be rejected
- Valid avatar upload still works

---

### AUD-10 - High - Soft-delete behavior is inconsistent across user-visible flows

**Summary**

Different modules disagree on whether soft-deleted rows should be visible or count as active, which breaks expected behavior.

**Evidence**

- `ELearning/Student/watchcourse.php:20` - lesson query ignores `is_deleted`
- `ELearning/Admin/lessons.php:15` - admin soft-deletes lessons
- `ELearning/Student/myCourse.php:31` - owned courses disappear if `course.is_deleted=1`
- `ELearning/courses.php:35` - checks purchase using non-deleted orders only
- `ELearning/checkout_action.php:31` - purchase check does not filter `is_deleted`

**Impact**

- Deleted lessons can still appear in the player
- Purchased courses can disappear from a student's library if the course is soft-deleted by admin
- "Already purchased" checks can disagree across pages

**Required Fix**

- Define explicit rules for each entity:
  - `courseorder`: a soft-deleted order should not grant ownership
  - `lesson`: a soft-deleted lesson should not be visible in playback
  - `course`: decide whether existing owners keep access even if the course is hidden from the catalog
- Apply those rules consistently in all queries

**Acceptance Test**

- Soft-deleted lesson must not appear in playback
- Soft-deleted course must follow the chosen access policy consistently across catalog and owned-course pages

---

### AUD-11 - Medium - Public pages render unescaped database content in several places

**Summary**

Some public-facing pages still echo course names and descriptions directly into HTML without escaping.

**Evidence**

- `ELearning/courses.php:60`
- `ELearning/courses.php:61`
- `ELearning/coursedetails.php:38`
- `ELearning/coursedetails.php:39`
- `ELearning/index.php:139`
- `ELearning/index.php:140`

**Impact**

- Stored XSS becomes possible if admin-created content contains HTML/JS payloads

**Required Fix**

- Use `htmlspecialchars()` consistently when rendering database content into HTML text nodes and attributes
- Only allow rich HTML if a sanitizer is explicitly introduced

**Acceptance Test**

- Course title/description containing HTML tags must render as text unless explicitly sanitized and allowed

---

### AUD-12 - Medium - Feedback UI promises per-course feedback but schema does not support it

**Summary**

The student feedback form shows a course selector, but the selected course is never stored because the `feedback` table has no `course_id` field.

**Evidence**

- `ELearning/Student/stufeedback.php:23` - loads purchased courses
- `ELearning/Student/stufeedback.php:75` - shows course selector
- `ELearning/Student/stufeedback.php:33` - inserts only `(f_content, stu_id)`
- `SQL/lms_db.sql:110` - `feedback` table has only `f_id`, `f_content`, `stu_id`
- `ELearning/Student/studentProfile.php:246` - UI checks for `course_name` even though the feedback query does not provide it

**Impact**

- UI is misleading
- Future agent may think course-linked feedback already exists when it does not

**Required Fix**

- Choose one direction:
  - remove the course selector and present feedback as general platform feedback, or
  - add `course_id` to `feedback`, migrate data, update inserts, joins, and profile/admin displays

**Acceptance Test**

- If course-linked feedback is kept, selected course must be stored and visible in profile/admin views

---

### AUD-13 - Medium - Payment status pages are legacy code disconnected from the current checkout flow

**Summary**

The repository still contains Paytm status pages, but the current checkout path creates local success orders directly and does not depend on a verified Paytm callback.

**Evidence**

- `ELearning/paymentstatus.php:10` - includes Paytm config
- `ELearning/Admin/adminPaymentStatus.php:11` - includes Paytm config
- `ELearning/PaytmKit/lib/config_paytm.php:10` - still contains placeholder merchant constants
- `ELearning/checkout_action.php:15` - marks transactions successful locally without Paytm verification

**Impact**

- Payment status screens are misleading for current local orders
- Future developers may mistake the gateway integration as production-ready

**Required Fix**

- Either remove/disable legacy Paytm pages or fully restore real gateway verification
- Document the payment mode clearly: mock checkout vs real gateway

**Acceptance Test**

- Every exposed payment-status page must match the actual payment flow used by checkout

---

### AUD-14 - Medium - Header calls happen after HTML output on payment status pages

**Summary**

The code includes HTML-rendering header files before calling PHP `header()` functions.

**Evidence**

- `ELearning/paymentstatus.php:4` then `ELearning/paymentstatus.php:5`
- `ELearning/Admin/adminPaymentStatus.php:4` then `ELearning/Admin/adminPaymentStatus.php:5`

**Impact**

- Can trigger "headers already sent" warnings/errors depending on output buffering and environment

**Required Fix**

- Move all `header()` calls before any include that may output content

**Acceptance Test**

- Pages should run with display errors enabled and produce no header warnings

---

### AUD-15 - Medium - Missing shared assets and missing seeded lesson media break UI flows

**Summary**

The app references several files that are not present in the repository, and seeded lessons point to local videos that are missing.

**Evidence**

- Missing banner image references:
  - `ELearning/courses.php:8`
  - `ELearning/coursedetails.php:8`
  - `ELearning/login.php:7`
  - `ELearning/signup.php:7`
  - `ELearning/paymentstatus.php:37`
- Missing admin fallback image reference:
  - `ELearning/Admin/courses.php:71` -> `image/courseimg/default.jpg`
- Seeded lessons reference local videos in `SQL/lms_db.sql:147`
- `ELearning/lessonvid/` currently contains only `.gitkeep`

**Impact**

- Broken banners/images in multiple pages
- Purchased local-video lessons cannot actually play from seeded data

**Required Fix**

- Add the missing shared image assets or change references to existing files
- Either ship seed lesson media, switch seed data to valid remote links, or update seed SQL to match shipped files
- Add an asset existence check to the release checklist

**Acceptance Test**

- No missing-image placeholders on public/admin pages
- Seeded purchased courses must have playable lessons

---

### AUD-16 - Medium - Public schema mutation script is exposed in web root

**Summary**

The repository contains a browser-accessible PHP script that alters the database schema.

**Evidence**

- `ELearning/prepare_soft_delete.php:1`

**Impact**

- Dangerous operational behavior if accidentally left reachable in shared environments
- Encourages runtime schema drift instead of formal migrations

**Required Fix**

- Remove it from the web root, convert it into a migration or CLI-only tool, or at minimum protect it behind admin auth and environment checks

**Acceptance Test**

- There must be no public URL that mutates schema in production/local runtime unexpectedly

---

### AUD-17 - Medium - Admin trash badge depends on connection order that is not guaranteed

**Summary**

The admin sidebar computes trash counts only if `$conn` already exists, but many admin pages include the header before including `dbConnection.php`.

**Evidence**

- `ELearning/Admin/adminDashboard.php:5` - includes header first
- `ELearning/Admin/adminDashboard.php:6` - includes db second
- `ELearning/Admin/adminInclude/header.php:133` - trash count logic runs only if `isset($conn)`

**Impact**

- Trash badge can silently show `0` or stale behavior depending on include order

**Required Fix**

- Standardize admin page bootstrap order so DB connection is available before rendering shared components, or make the header responsible for loading the connection safely once

**Acceptance Test**

- Trash badge must show the same count on every admin page

---

### AUD-18 - Low - Seed data integrity is weak and lacks relational constraints

**Summary**

The seed data contains orphan references and the schema lacks protective constraints for several relationships and unique identifiers.

**Evidence**

- `SQL/lms_db.sql:99` - `courseorder.course_id = 14`, but no course `14` exists in seeded courses
- `SQL/lms_db.sql:125` - feedback row references `stu_id = 180`, but no such student exists in seed data
- The main schema includes a unique key only for `order_id` and cart uniqueness:
  - `SQL/lms_db.sql:210`
  - `SQL/lms_db.sql:285`
- No foreign keys were found during audit
- No unique email constraints for `admin.admin_email` or `student.stu_email`

**Impact**

- Reports and joins can return incomplete or inconsistent data
- Duplicate login identifiers remain possible

**Required Fix**

- Clean up seed rows
- Add unique constraints for login emails
- Add foreign keys where the domain rules require them
- Revisit soft-delete policy before adding hard FK cascade behavior

**Acceptance Test**

- Fresh seed import should contain no orphaned references
- Duplicate email inserts should fail cleanly

---

### AUD-19 - Low - README is materially out of date

**Summary**

The documentation describes security and setup behavior that no longer matches the repository.

**Evidence**

- `README.md:44` - claims prepared statements are used across backend
- `README.md:75` - references `cart_table.sql`, which is not present in `SQL/`
- `README.md:167` - documents plaintext passwords as intentional, but code partially migrated to hashing already

**Impact**

- New developers will set up the project incorrectly
- Reviewers may trust documentation over the actual implementation and miss real risks

**Required Fix**

- Rewrite setup steps to match the real import sequence
- Document the current password migration state accurately
- Document the current payment mode accurately

**Acceptance Test**

- A new developer should be able to clone the repo and run the app by following `README.md` only

## Guardrails For The Next Agent

These are mandatory.

1. Do not reintroduce Bootstrap or jQuery while refactoring
2. Do not keep Tailwind CDN as the final maintained solution
3. Do not migrate the project to a JS SPA or non-PHP backend
4. Do not touch unrelated binary assets or documents unless required by the plan
5. Do not delete old dependencies until their replacements are fully wired and tested
6. Do not ignore Linux case-sensitive path correctness
7. Do not weaken existing security fixes while rewriting frontend code

## Execution Protocol

The next agent must follow this working protocol while executing the plan:

1. Work phase-by-phase in order
2. At the start of each phase, update this file or report externally which phase is in progress
3. Before editing shared files, check whether another agent is already assigned to them
4. Do not let multiple agents edit the same shared include at the same time
5. After finishing each phase, run targeted regression checks before moving on
6. If a phase reveals a blocker, document the blocker clearly with affected files and stop expanding scope

## Suggested Parallel Work Split

If multiple agents are used, split work by ownership to avoid merge conflicts:

- Agent 1: shared layouts only
  - `ELearning/mainInclude/header.php`
  - `ELearning/mainInclude/footer.php`
  - `ELearning/Student/stuInclude/header.php`
  - `ELearning/Student/stuInclude/footer.php`
  - `ELearning/Admin/adminInclude/header.php`
  - `ELearning/Admin/adminInclude/footer.php`

- Agent 2: public/student JavaScript and related hooks
  - `ELearning/js/ajaxrequest.js`
  - `ELearning/js/custom.js`
  - inline JS in student/public pages such as `ELearning/Student/myCart.php`

- Agent 3: admin JavaScript and Bootstrap-heavy legacy forms
  - `ELearning/js/adminajaxrequest.js`
  - `ELearning/contact.php`
  - `ELearning/Student/studentChangePass.php`
  - `ELearning/Admin/editstudent.php`
  - `ELearning/studentRegistration.php`

- Agent 4: special pages, build pipeline, and cleanup
  - `ELearning/checkout.php`
  - `ELearning/checkout_action.php`
  - `ELearning/Student/watchcourse.php`
  - Tailwind build/config files
  - `README.md`

If only one agent is used, follow the phase order exactly and ignore this split section.

## Completion Definition

The plan is complete only when all of the following are true:

1. The final stack is `PHP + MySQL + HTML + CSS + Tailwind CSS + Vanilla JavaScript`
2. Bootstrap, jQuery, Popper, and Owl Carousel are no longer loaded or referenced
3. Tailwind is delivered through a built static asset, not Play CDN
4. The regression checklist in this file passes
5. README matches the final run/build/setup flow
6. No critical or high-severity audit issue is left unaddressed in the touched areas

### Completion Certification - 2026-03-24

Status against the Completion Definition:

1. Final stack is `PHP + MySQL + HTML + CSS + Tailwind CSS + Vanilla JavaScript`
   - PASS

2. Bootstrap, jQuery, Popper, and Owl Carousel are no longer loaded or referenced
   - PASS
   - runtime references removed and legacy dependency files removed from disk

3. Tailwind is delivered through a built static asset, not Play CDN
   - PASS

4. The regression checklist in this file passes
   - PASS
   - verified with static checks plus runtime regression in a XAMPP-based environment

5. README matches the final run/build/setup flow
   - PASS

6. No critical or high-severity audit issue is left unaddressed in the touched areas
   - PASS

Overall result:

- The approved migration plan is complete.
- The project is ready to be considered complete for the target stack and migration scope in this document.
- Only optional low-severity follow-up remains if the team wants to close `AUD-18`.

## Final Instruction

Before adding new features, the next agent must complete the approved migration plan above while preserving or improving the security/correctness fixes from the audit section.

The correct destination is:

- `PHP + MySQL`
- `HTML + CSS + Tailwind CSS + Vanilla JavaScript`

and no hybrid Bootstrap/jQuery frontend should remain when the plan is done.
