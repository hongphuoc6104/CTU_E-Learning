# Project Expansion Plan 06 - Migration, Testing, Rollout

Generated: 2026-03-24

This plan explains how to implement the expansion safely without breaking the already-working project.

## Objective

Provide the execution order, compatibility strategy, migration strategy, and regression process for the expansion wave.

## Execution Status Update

Current repository status:

- Plans 01 to 04 are already implemented to the point that migration/testing work can begin in parallel with Plan 05
- this plan should not yet perform final cleanup while Plan 05 is still being implemented
- this plan is now safe to execute as a preparation stream for testing, rollout notes, migration notes, and documentation planning

This plan is ready for partial parallel execution now.

Safe work to do now:

- build/update regression checklist
- build/update migration notes and compatibility checklist
- validate seed-data coverage and documented demo scenarios
- prepare final documentation updates and rollout gates

Work that should still wait until Plan 05 stabilizes:

- final legacy deprecation
- final release signoff
- final full-wave certification

Important implementation note:

- treat this plan as a safety and release-prep lane while Plan 05 remains the main implementation lane
- do not convert this plan into a feature plan for admin/instructor/student behavior changes unless a test or rollout blocker requires a targeted fix

## Preparation Lane Snapshot (Current Wave)

This snapshot is intentionally preparation-only and does not certify final release.

### Preparation artifacts produced now

- `PLAN06_MIGRATION_BACKFILL_CHECKLIST.md`
- `PLAN06_REGRESSION_MATRIX_EXECUTABLE.md`
- `PLAN06_SEED_DATA_VALIDATION_SUMMARY.md`
- `PLAN06_DOCS_UPDATE_CHECKLIST.md`
- `PLAN06_RELEASE_GATES_AND_BLOCKERS.md`
- `SQL/plan06_seed_validation_checks.sql`

### Real blockers found during preparation

- Plan 05 is still the active implementation lane and has not reached stabilization signoff
- admin reporting remains partially legacy-first in several screens and cannot be final-certified yet
- seed data currently has enrollment records in only one status (`active`), which blocks full seed-state certification for final rollout

### Explicitly deferred until Plan 05 stabilization

- legacy route deprecation
- compatibility code cleanup
- final release signoff and full-wave certification

## Core Principle

Do not attempt a big-bang rewrite.

Migrate in layers.

## Recommended Delivery Sequence

### Stage 1 - Schema foundation

Use Plan 01.

Deliverables:

- new tables
- new statuses
- safe backfill scripts or SQL seed extensions

Do not switch all pages to the new model yet.

### Stage 2 - New order/payment flow

Use Plan 02.

Deliverables:

- order/payment tables working
- student order creation and payment submission
- admin verification path ready

Temporary compatibility allowed:

- old legacy course purchase history may still exist while new orders are adopted

### Stage 3 - New learning model

Use Plan 03.

Deliverables:

- course sections
- learning items
- progress tracking
- quiz basics

Temporary compatibility allowed:

- existing `lesson` rows can be backfilled as `video` learning items

### Stage 4 - Instructor and live class

Use Plan 04.

Deliverables:

- instructor auth and course ownership
- live session scheduling
- replay URL flow

### Stage 5 - Admin review and operations

Use Plan 05.

Deliverables:

- course review queue
- payment verification queue
- operational reports

### Stage 6 - Final integration and cleanup

Deliverables:

- docs updated
- legacy paths redirected or deprecated safely
- full regression passed

## Compatibility Strategy

### Existing lesson data

Use one of these safe options:

1. Backfill each `lesson` into `learning_item` type `video`
2. Keep `lesson` read-only during transition and migrate runtime reads in a controlled step

Preferred:

- backfill and then switch runtime reads to the new model when ready

### Existing purchases

If current students already have valid purchases:

- backfill them into `enrollment`
- keep legacy rows for history/reference if needed

### Existing public/admin/student flows

Do not remove stable current routes until replacement routes are tested.

## Testing Strategy

## Mandatory Seed-Data Validation

The final rollout is not valid unless the SQL bootstrap already contains the required default fake data.

Before certifying the expansion wave, verify that a fresh SQL import contains curated test-ready data for:

- admin
- instructors
- students in multiple states
- courses in multiple states
- learning items in multiple types
- payments in multiple states
- enrollments in multiple states
- live sessions in multiple states
- replay links
- quiz attempts

The SQL import must be enough to run the core regression scenarios without manual DB edits.

### Test pass after every stage

1. Public browse still works
2. Student auth still works
3. Admin auth still works
4. Existing purchased-course access is not broken

### Final regression matrix

#### Guest

- home page
- course catalog
- course detail
- signup
- login

#### Student commerce

- add to cart
- cart remove
- create order
- submit payment proof
- see order status
- verify seeded pending/submitted payment records are visible and testable

#### Student learning

- enrolled course visible
- open course player
- open video/article/document item
- take quiz
- progress updates
- view live session state
- open replay after availability
- verify at least one partial-progress student and one completed-progress student from seed data

#### Instructor

- login
- create draft course
- add section
- add item types
- add live session
- add replay URL
- submit for review
- verify seeded instructor-owned draft and pending-review courses exist

#### Admin

- login
- review course
- verify payment
- enrollment creation result
- reporting
- manage instructors
- verify seeded order/payment state diversity exists in admin screens

#### Seed integrity

- all 10 required curated datasets exist after fresh SQL import
- seeded actors can log in with documented credentials
- seeded published courses are visible publicly
- seeded pending-review and draft courses are visible only in appropriate operational views

### Technical checks

- no reintroduced Bootstrap/jQuery dependencies
- no broken schema import
- no PHP syntax errors
- no broken path/case issues

## Rollout Rules

1. Merge schema first
2. Merge order/payment flow next
3. Merge student course player upgrade after data backfill is ready
4. Merge instructor/admin modules only after foundation is stable
5. Do not delete compatibility code until the replacement is verified

## Documentation Requirements

At the end of the expansion wave, update:

- `README.md`
- setup steps for new schema
- actor/role descriptions
- payment flow explanation
- live session / replay explanation
- seeded demo accounts and seeded demo scenarios

## Definition Of Done For This Expansion Wave

The expansion wave is complete when:

1. New schema exists and imports cleanly
2. Public storefront still works
3. New order/payment lifecycle works
4. Enrollment grants course access correctly
5. Student learning works with multiple item types
6. Instructor can manage owned courses and live sessions
7. Admin can review courses and verify payments
8. Replay workflow works after live session end
9. Final regression matrix passes
10. Required default seed datasets exist and support the documented demo/test scenarios

## Handoff Note For The Next AI

This file is the release-and-safety plan.
It should normally be used after implementation workstreams are mostly finished.
