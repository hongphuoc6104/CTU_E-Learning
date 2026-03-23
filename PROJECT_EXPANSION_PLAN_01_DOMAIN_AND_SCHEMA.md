# Project Expansion Plan 01 - Domain And Schema

Generated: 2026-03-24

This plan defines the foundational domain model and database changes for the expansion wave.

Do not implement storefront pages, student learning pages, instructor UI, or admin review screens in this plan except for the minimum needed to support schema verification.

## Objective

Create the data foundation for:

- course-commerce with order/payment states
- course sections and multi-format learning items
- student enrollments and learning progress
- instructor-owned courses
- live sessions and replay links

## Current Baseline

The current project already has:

- `student`
- `admin`
- `course`
- `lesson`
- `cart`
- `courseorder`
- `feedback`
- `contact_message`

The current product works, so this plan must be additive-first.

## Important Scope Decision

Do not replace the whole identity model with a generic `users` table in this wave.

Chosen approach for implementation simplicity:

- keep current `student` table
- keep current `admin` table
- add `instructor` table

This avoids a risky full auth rewrite.

## Mandatory SQL Seed Requirement

This plan must not stop at schema only.

The schema foundation must ship with default SQL seed data that is polished enough for demo and regression use.

The final SQL bootstrap for this expansion must include:

- structure
- constraints/indexes
- curated default fake data

The seed data must be importable by default with no extra manual setup.

## Target New Domain Objects

### New core tables

- `instructor`
- `course_section`
- `learning_item`
- `quiz`
- `quiz_question`
- `quiz_answer`
- `quiz_attempt`
- `order_master`
- `order_item`
- `payment`
- `enrollment`
- `learning_progress`
- `live_session`
- `session_attendance`
- `replay_asset`

### Existing tables to extend

- `course`
  - add course ownership and publishing fields
- `student`
  - no broad redesign in this phase unless a field is strictly required

## Required Data Model Decisions

### 1. Instructor model

Create `instructor` with at least:

- `ins_id`
- `ins_name`
- `ins_email`
- `ins_pass`
- `ins_bio`
- `ins_img`
- `ins_status` (`active`, `blocked`)
- timestamps
- `is_deleted`

### 2. Course publishing model

Extend `course` with at least:

- `instructor_id`
- `course_status` (`draft`, `pending_review`, `published`, `archived`)
- `course_type` (`self_paced`, `blended`)
- `published_at`

Keep current fields like name, description, price, duration, image.

### 3. Course structure model

Create `course_section`:

- `section_id`
- `course_id`
- `section_title`
- `section_position`
- `is_deleted`

Create `learning_item`:

- `item_id`
- `course_id`
- `section_id`
- `item_title`
- `item_type` (`video`, `article`, `document`, `quiz`, `live_session`, `replay`)
- `item_position`
- `is_preview`
- `is_required`
- `content_status` (`draft`, `published`)
- `is_deleted`

For content storage:

- `video_url`
- `article_content`
- `document_url`
- `quiz_id`
- `live_session_id`
- `replay_id`

You may either:

- keep these nullable columns in `learning_item`, or
- split some content details into subtype tables

Preferred for this project scope:

- keep `learning_item` simple with nullable type-specific fields
- use dedicated extra tables only for quiz/live/replay where needed

### 4. Quiz model

Create:

- `quiz`
- `quiz_question`
- `quiz_answer`
- `quiz_attempt`

Minimum behavior:

- one learning item can point to one quiz
- quiz has pass score
- student attempts are recorded with score and pass/fail

### 5. Order and payment model

Create `order_master`:

- `order_id`
- `student_id`
- `order_code`
- `order_total`
- `order_status` (`pending`, `awaiting_verification`, `paid`, `failed`, `cancelled`, `refunded`)
- timestamps
- `is_deleted`

Create `order_item`:

- `order_item_id`
- `order_id`
- `course_id`
- `unit_price`
- `item_status`

Create `payment`:

- `payment_id`
- `order_id`
- `payment_method`
- `payment_reference`
- `payment_proof_url`
- `payment_status` (`pending`, `submitted`, `verified`, `rejected`)
- `verified_by_admin_id`
- `verified_at`
- `notes`

### 6. Enrollment model

Create `enrollment`:

- `enrollment_id`
- `student_id`
- `course_id`
- `order_id`
- `enrollment_status` (`active`, `expired`, `revoked`)
- `granted_at`
- `completed_at`
- `progress_percent`

### 7. Progress model

Create `learning_progress`:

- `progress_id`
- `student_id`
- `course_id`
- `item_id`
- `progress_status` (`not_started`, `in_progress`, `completed`, `passed`)
- `last_accessed_at`
- `completed_at`
- optional lightweight metrics such as watch percentage or last position

### 8. Live session model

Create `live_session`:

- `live_session_id`
- `course_id`
- `section_id`
- `instructor_id`
- `session_title`
- `session_description`
- `start_at`
- `end_at`
- `join_url`
- `platform_name`
- `session_status` (`scheduled`, `live`, `ended`, `replay_available`, `cancelled`)
- `is_deleted`

Create `session_attendance`:

- `attendance_id`
- `live_session_id`
- `student_id`
- `joined_at`
- `left_at`
- `attendance_status`

Create `replay_asset`:

- `replay_id`
- `live_session_id`
- `recording_url`
- `recording_provider`
- `available_at`
- `is_deleted`

## Migration Rules

### Rule 1 - Do not destroy current tables yet

Do not delete or repurpose these immediately:

- `lesson`
- `courseorder`

They may continue to support legacy flows during transition.

### Rule 2 - Backfill old data safely

Backfill strategy:

- each existing `course` gets a default section during migration if needed
- each existing `lesson` becomes one `learning_item` of type `video`
- successful current purchases can be backfilled into `enrollment`
- current `courseorder` may be kept for history while new order flow uses `order_master` + `order_item`

### Rule 3 - Add indexes

Add indexes for:

- `student.stu_email`
- `admin.admin_email`
- `instructor.ins_email`
- `course.instructor_id`
- `course.course_status`
- `learning_item.course_id`, `section_id`, `item_type`, `item_position`
- `order_master.student_id`, `order_status`
- `order_item.order_id`, `course_id`
- `payment.order_id`, `payment_status`
- `enrollment.student_id`, `course_id`
- `learning_progress.student_id`, `course_id`, `item_id`
- `live_session.course_id`, `instructor_id`, `session_status`, `start_at`

### Rule 4 - Add foreign keys only where safe

Use foreign keys carefully.

Preferred:

- use FK for new tables where soft-delete policy will not fight the schema
- avoid aggressive cascading deletes

## Required Default Seed Data Design

Create at least 10 curated fake datasets directly in SQL.

These are not just 10 random rows.
Each dataset should represent a coherent scenario.

### Dataset 1 - Published self-paced design course

Must include:

- one instructor-owned published course
- one or more sections
- video lesson
- article lesson
- document lesson
- quiz lesson

### Dataset 2 - Published blended/live course

Must include:

- one instructor-owned published course
- mixed learning items
- at least one `live_session` in `scheduled`
- at least one `replay_asset` linked to an ended live session

### Dataset 3 - Draft instructor course

Must include:

- one course in `draft`
- incomplete but valid enough structure for preview in admin/instructor areas

### Dataset 4 - Pending-review instructor course

Must include:

- one course in `pending_review`
- at least one section and multiple item types

### Dataset 5 - Student with no purchases

Must include:

- one active student with no orders and no enrollments

### Dataset 6 - Student with cart but unpaid state

Must include:

- one active student
- cart rows for active published courses
- optional pending order that is not yet paid

### Dataset 7 - Student with paid enrollment and partial progress

Must include:

- one active student
- verified payment
- paid order
- active enrollment
- progress rows showing partial completion

### Dataset 8 - Student with paid enrollment and completed progress

Must include:

- one active student
- verified payment
- paid order
- completed enrollment or very high progress
- passed quiz attempt

### Dataset 9 - Awaiting verification order

Must include:

- one order in `awaiting_verification`
- one payment in `submitted`
- realistic payment proof metadata placeholder

### Dataset 10 - Failed or rejected commerce example

Must include:

- one rejected payment or failed order
- if possible, also include one cancelled order example

## Seed Data Content Quality Rules

1. Use consistent names, emails, titles, and descriptions
2. Use Vietnamese-friendly content where practical to match the project tone
3. Keep demo data realistic and easy to understand in the UI
4. Use clearly different titles for each course so testing is easy
5. Seed credentials must be documented in `README.md` once the expansion is implemented
6. Use stable URLs for external content examples:
   - YouTube for video/replay where needed
   - public PDF/document URLs only if they are stable, or local shipped docs if the project chooses that route later
7. Seed dates should cover different states:
   - future live session
   - past ended live session
   - old paid order
   - recent pending verification order

## Required Seed Row Families

Minimum recommended seeded counts:

- `admin`: 1 to 2
- `instructor`: 2 to 3
- `student`: 10+
- `course`: 8+
- `course_section`: 12+
- `learning_item`: 30+
- `quiz`: 2+
- `quiz_question`: 10+
- `quiz_answer`: 40+
- `order_master`: 6+
- `order_item`: 8+
- `payment`: 6+
- `enrollment`: 4+
- `learning_progress`: enough to show partial and completed learning states
- `live_session`: 3+
- `replay_asset`: 2+

## Seed Data Must Support Demo Scenarios

The seeded SQL must allow a fresh project import to demonstrate:

- guest browsing published courses
- student login and cart
- admin payment verification queue
- student enrolled course playback and progress
- instructor-owned course states
- live session upcoming view
- replay view after session end

## Files Expected To Change In This Plan

## Files Expected To Change In This Plan

- `SQL/lms_db.sql`
- possibly a new migration SQL file if needed
- very small compatibility adjustments in PHP only if needed for migration smoke checks

## Explicit Out Of Scope For Plan 01

- redesigning public pages
- instructor UI pages
- student learning UI
- admin review workflow UI
- deep checkout UI changes

## Acceptance Criteria

1. New tables exist for instructor, sections, learning items, orders, payments, enrollments, progress, live sessions, and replay
2. `course` supports instructor ownership and publish state
3. Old lesson/video data can be mapped into the new structure safely
4. The database import still succeeds on a fresh setup
5. No existing stable runtime flow is broken just by the schema addition

## Handoff Note For The Next AI

Do not build pages here.
Build the schema and migration foundation first, then stop.
