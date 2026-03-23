# Project Expansion Plan 03 - Learning Experience, Multi-Content, Progress

Generated: 2026-03-24

This plan covers what happens after a student is enrolled in a course.

The main thesis value of the expansion lives here.

## Objective

Convert the current course player from a lesson-video list into a structured learning experience with multiple item types and measurable student progress.

## Dependencies

This plan assumes Plan 01 exists.

Required dependencies:

- `course_section`
- `learning_item`
- `enrollment`
- `learning_progress`
- quiz tables
- live/replay tables for later integration

## Scope In This Plan

### Student pages to update

- `ELearning/Student/myCourse.php`
- `ELearning/Student/watchcourse.php`

### Related display logic that may need updates

- public course detail previews if item-type summaries are shown

## Target Learning Model

A course contains:

- sections/modules
- learning items inside sections

Each learning item can be one of:

- `video`
- `article`
- `document`
- `quiz`
- `live_session`
- `replay`

## Student Learning Flow

1. Student enters My Courses
2. Student opens an enrolled course
3. Course player shows:
   - section list
   - item list per section
   - item type labels/icons
   - progress summary
4. Student opens each item type
5. System records progress
6. Course progress is recalculated continuously

## Item-Type Behavior Requirements

### Video

- render embedded or hosted video
- allow mark-as-complete automatically or manually based on current app simplicity
- store progress in `learning_progress`

### Article

- render rich text/article content
- student can read and mark complete

### Document/PDF

- render download/view link
- student can mark complete after opening or explicitly using a button

### Quiz

- render questions
- accept answers
- grade score
- record attempts
- set item progress to `passed` when pass score is met

### Live session

- if status is `scheduled`, show date/time and join button
- if status is `live`, show join button prominently
- if status is `ended`, show ended state
- if status is `replay_available`, also show replay item/button

### Replay

- render recording URL
- allow the student to watch later
- can count as complete if the business rule says replay is required

## Progress Rules

### Item progress

Each item stores one of:

- `not_started`
- `in_progress`
- `completed`
- `passed`

### Course progress

Course progress should be computed from required items only.

Recommended rule:

- `video`, `article`, `document`, `replay` -> `completed`
- `quiz` -> `passed`
- `live_session` -> either:
  - counted complete after attendance, or
  - counted complete after replay is watched

Preferred simpler policy for this project:

- count `live_session` as completed when student joins or when replay is completed

## UI Requirements

### My Courses page

Add the following to each enrolled course card:

- progress percentage
- completed item count
- next item CTA

### Course player page

The player page should support:

- left navigation: sections and items
- right content area: render current item by type
- progress summary at top or side
- complete/next actions

Do not redesign the app into a huge SPA.
Use server-rendered PHP with lightweight vanilla JS where needed.

## Error Handling Requirements

1. If student does not have enrollment, block access cleanly
2. If item type is invalid or missing, show a visible fallback state
3. If a document or replay URL is missing, show unavailable state, not broken empty content
4. If quiz submission is malformed, keep the student on the page with feedback
5. If course has no sections/items yet, show a clean empty course state

## Existing Files To Build On

- `ELearning/Student/myCourse.php`
- `ELearning/Student/watchcourse.php`

These are the natural foundations for the new learning experience.

## Suggested New Files

Add only if needed:

- `ELearning/Student/quizAttempt.php`
- `ELearning/Student/markProgress.php`

If these are added, keep them small and single-purpose.

## Explicit Out Of Scope For Plan 03

- instructor authoring UI
- payment/admin verification
- certificate generation
- advanced analytics dashboards

## Acceptance Criteria

1. A course can contain multiple content types
2. Student can open and complete multiple content types from one course player
3. Progress is stored per item and summarized per course
4. Quiz attempts are recorded and scored
5. Existing enrolled-course flow still works

## Handoff Note For The Next AI

This plan is about the student learning experience after enrollment, not about course selling or admin approval.
