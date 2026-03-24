# Project Expansion Plan 04 - Instructor Role, Course Authoring, Live Class, Replay

Generated: 2026-03-24

This plan introduces the instructor role and the live-class lifecycle.

Important product rule:

Live classes are part of a course.
They are not a separate product line.

## Objective

Allow instructors to create course content, schedule live sessions, and publish replay links after class, while keeping admin in control of final publishing and payment verification.

## Execution Status Update

Current repository status:

- the instructor area is already implemented under `ELearning/Instructor/`
- instructor authentication, dashboard access, owned-course protection, draft course creation, course editing, section management, learning-item management, live-session scheduling, replay update flow, and instructor-side student listing are already present in runtime PHP files
- instructor access is restricted to owned courses and active instructor accounts through shared instructor auth helpers
- live sessions use external URLs only, and replay remains URL metadata only

Implemented files now include at least:

- `ELearning/Instructor/instructorLogin.php`
- `ELearning/Instructor/instructorDashboard.php`
- `ELearning/Instructor/courses.php`
- `ELearning/Instructor/addCourse.php`
- `ELearning/Instructor/editCourse.php`
- `ELearning/Instructor/sections.php`
- `ELearning/Instructor/learningItems.php`
- `ELearning/Instructor/liveSessions.php`
- `ELearning/Instructor/students.php`
- `ELearning/Instructor/instructorInclude/auth.php`

Plan 04 can now be treated as functionally complete for the current wave.

Remaining completion focus for this plan:

- keep instructor-side ownership rules, live-session validation, and replay flow stable while Plan 05 extends admin review and operations
- avoid expanding this plan into admin approval, instructor payment verification, or attendance automation
- only apply targeted fixes here if they directly affect instructor auth, owned-course safety, live scheduling, or replay publishing

Important implementation note:

- this plan stops at instructor submission and instructor-side operations
- admin approval/publishing and broader operational controls should continue in Plan 05 instead of reopening this plan

## Dependencies

This plan assumes Plan 01 exists.

Recommended dependencies before UI implementation:

- `instructor`
- `course.course_status`
- `course_section`
- `learning_item`
- `live_session`
- `replay_asset`

## Scope In This Plan

### New instructor area

Recommended new module:

- `ELearning/Instructor/`

Possible pages:

- `Instructor/index.php`
- `Instructor/instructorLogin.php`
- `Instructor/instructorDashboard.php`
- `Instructor/courses.php`
- `Instructor/addCourse.php`
- `Instructor/editCourse.php`
- `Instructor/sections.php`
- `Instructor/learningItems.php`
- `Instructor/liveSessions.php`
- `Instructor/students.php`

## Instructor Business Scope

### Instructor can

- log in
- create draft course
- edit owned course
- create sections
- create learning items by type
- schedule live sessions
- add replay URL after live session
- submit course for review
- see enrolled students in own published/approved courses

### Instructor cannot

- verify payments
- publish directly without admin review if admin review policy is enabled
- edit another instructor's course
- access admin-only reporting areas

## Live Session Model

### Scheduling flow

1. Instructor selects a course and section
2. Instructor creates a live session item with:
   - title
   - description
   - start time
   - end time
   - platform name
   - external join URL
3. System stores live session as `scheduled`
4. Student sees the session in the course timeline

### Runtime flow

1. Before start time:
   - show upcoming state
2. During live time:
   - show join CTA
3. After end time:
   - show ended state until replay is available

### Replay flow

1. Instructor or admin enters recording URL after class
2. System sets:
   - live session status to `replay_available`
   - replay asset link
3. Student sees replay as playable review content

## Mandatory Constraints

1. Do not implement internal streaming/video room
2. Do not implement automatic recording
3. Use external live links only (Zoom, Google Meet, etc.)
4. Replay is stored as URL metadata only, not as a full recording engine

## Authoring Rules

### Course lifecycle

- `draft`
- `pending_review`
- `published`
- `archived`

### Instructor draft flow

1. create draft course
2. add sections
3. add learning items
4. add live sessions where needed
5. validate completeness
6. submit for review

### Replay rules

- replay cannot exist without a linked live session
- replay should not be visible until recording URL is present

## Error Handling Requirements

1. Instructor must be blocked from editing courses they do not own
2. Invalid live session times must be rejected
3. Missing join URL must block creation of live session
4. Missing replay URL must keep the session in `ended`, not `replay_available`
5. If a course is submitted with no meaningful content, reject submission with clear feedback

## Existing Files To Reuse Or Mirror

Use the admin course/lesson pages only as UI/flow references.
Do not copy them blindly.

Reference only:

- `ELearning/Admin/addCourse.php`
- `ELearning/Admin/editcourse.php`
- `ELearning/Admin/addLesson.php`
- `ELearning/Admin/editlesson.php`

## Explicit Out Of Scope For Plan 04

- full attendance automation
- auto-generated certificates
- direct instructor payout
- instructor-led payment verification

## Acceptance Criteria

1. Instructor can log in and manage only owned courses
2. Instructor can create sections and mixed learning items
3. Instructor can schedule live sessions with external meeting links
4. Instructor can attach replay URL after session end
5. Submitted courses can move into admin review pipeline

## Handoff Note For The Next AI

Do not implement admin approval in this plan.
This plan stops at instructor submission and instructor-side content operations.
