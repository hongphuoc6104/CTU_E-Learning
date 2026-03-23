# Project Expansion Plan Index

Generated: 2026-03-24

This document is the master index for the next product expansion wave.

The product remains a web platform that sells courses.
Inside each course, the student will learn through multiple content types instead of fixed video-only lessons.

Core product shape after expansion:

- Public storefront for selling courses
- Paid enrollments after payment verification
- Rich course structure with multiple learning content types
- Instructor role for course creation and live-class management
- Admin role for review, payment verification, publishing, and reporting

## Relationship To Existing Plan Files

- `PROJECT_EXPANSION_PLAN_INDEX.md`
  - master roadmap for the next expansion wave
- `PROJECT_EXPANSION_PLAN_01_DOMAIN_AND_SCHEMA.md`
  - data model and foundation
- `PROJECT_EXPANSION_PLAN_02_STOREFRONT_CART_PAYMENT.md`
  - customer storefront and commerce flows
- `PROJECT_EXPANSION_PLAN_03_LEARNING_EXPERIENCE.md`
  - learning content, progress, quiz, replay
- `PROJECT_EXPANSION_PLAN_04_INSTRUCTOR_AND_LIVE_CLASS.md`
  - instructor role and live-session workflow
- `PROJECT_EXPANSION_PLAN_05_ADMIN_OPERATIONS.md`
  - admin approval, moderation, reporting, operations
- `PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`
  - migration order, rollout, regression, release gates

Previous audit and hardening waves are already completed and are treated as baseline context rather than active plan files in the repository.

## Global Product Scope

### Product definition

The project remains a course-commerce website.

Students buy courses.
Each course contains many learning items.
Learning items are no longer limited to fixed video links.

### Supported roles in this expansion

- Guest
- Student
- Instructor
- Admin

### Supported learning item types in this expansion

- Video lesson
- Article lesson
- Document/PDF lesson
- Quiz lesson
- Live session lesson
- Replay lesson

### Supported payment model in this expansion

- Internal order system with payment states
- Admin verification of submitted payment proof
- No real payment gateway integration in this wave

## Primary Theme Of The Thesis/Product

Transform the current website from a simple course-selling site with fixed lesson videos into a digital learning platform with:

- multi-format learning content
- structured course progression
- payment/order lifecycle management
- instructor participation
- live sessions with replay links

## Final Expansion Goals

1. Keep the product recognizable as a course-commerce website
2. Add richer learning value inside each course
3. Add an instructor-facing authoring workflow
4. Add operational control for admins
5. Keep the implementation realistic for the current PHP/MySQL codebase

## Global Constraints

These are mandatory for every plan file.

1. Keep the stack:
   - PHP + MySQL
   - HTML + CSS + Tailwind CSS + Vanilla JavaScript
2. Keep server-rendered PHP architecture
3. Do not reintroduce Bootstrap, jQuery, Popper, or Owl Carousel
4. Do not convert this into a SPA or framework migration
5. Do not implement real live streaming or real payment gateway integration in this wave
6. Use external meeting links for live classes
7. Use recording URLs for replay after live sessions end
8. Prefer additive schema migration over destructive redesign
9. Keep already-working current flows alive while migrating
10. Seed data is mandatory: the final SQL bootstrap must include polished default fake data for demo and testing

## Mandatory Seed Data Policy

The expansion wave must ship with curated default seed data inside SQL bootstrap files.

This is not optional.

Requirements:

1. At least 10 complete fake datasets must be created by default in SQL
2. The 10 datasets must be realistic, readable, and test-ready
3. Seed data must cover all major actors and workflows, not just random records
4. A fresh SQL import must be enough to demo and test the main product without manual data entry
5. Seed data must include enough coverage for:
   - guest storefront browsing
   - student registration/login replacement test accounts
   - cart and checkout
   - paid enrollments
   - in-progress and completed learning progress
   - instructor-managed courses
   - scheduled live sessions
   - ended sessions with replay links
   - pending payment verification
   - admin review/publishing

## Required Seed Dataset Coverage

The final SQL bootstrap must include at least these 10 curated datasets:

1. One published self-paced beginner course with video + article + PDF + quiz
2. One published blended course with live sessions and replay links
3. One draft instructor course
4. One pending-review instructor course
5. One student with no purchases yet
6. One student with cart items but no paid orders
7. One student with paid enrollment and partial progress
8. One student with paid enrollment and completed progress including quiz pass
9. One order awaiting payment verification with attached proof metadata
10. One rejected/failed order history example plus one cancelled example if possible

Recommended additional seed coverage:

- at least 2 instructors
- at least 1 admin
- at least 8 to 12 students total
- at least 6 to 10 courses total across mixed states
- at least 20 to 40 learning items across different item types
- at least 2 live sessions in different states
- at least 2 replay assets
- at least 2 quizzes with multiple attempts

## Chosen Strategy For Scope Control

To avoid AI overload and reduce implementation mistakes, the work is split into multiple detailed plans.

Each plan should be executed as a focused workstream.

Do not try to implement all plans in one pass.

## Recommended Execution Order

1. `PROJECT_EXPANSION_PLAN_01_DOMAIN_AND_SCHEMA.md`
2. `PROJECT_EXPANSION_PLAN_02_STOREFRONT_CART_PAYMENT.md`
3. `PROJECT_EXPANSION_PLAN_03_LEARNING_EXPERIENCE.md`
4. `PROJECT_EXPANSION_PLAN_04_INSTRUCTOR_AND_LIVE_CLASS.md`
5. `PROJECT_EXPANSION_PLAN_05_ADMIN_OPERATIONS.md`
6. `PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`

## Suggested AI Assignment Strategy

If multiple AIs are used, assign one plan per AI only when dependencies are satisfied.

Safe pattern:

- AI 1: Plan 01 only
- AI 2: Plan 02 after Plan 01 foundation is approved
- AI 3: Plan 03 after Plan 01 foundation is approved
- AI 4: Plan 04 after Plan 01 and core auth direction are approved
- AI 5: Plan 05 after Plans 02 to 04 stabilize
- AI 6: Plan 06 at the end

If using only one AI, execute the plans in strict order.

## Global Non-Goals

These are not part of the approved expansion scope unless explicitly requested later:

- Real-time streaming engine
- Auto-recording pipeline
- Marketplace revenue-sharing engine
- Subscription billing system
- Full social community/forum platform
- Mobile app
- Full unified enterprise RBAC redesign

## Global Definition Of Done

The expansion wave is complete when all of the following are true:

1. The site still works as a course-selling web platform
2. Courses support multiple learning content types
3. Payment/order states control enrollment correctly
4. Instructors can create and manage their course content and live sessions
5. Students can join live sessions and watch replay links later
6. Admins can approve courses and verify payments
7. Student learning progress works across required item types
8. Existing stable flows are not broken
9. Final regression and documentation updates are complete

## How Another AI Should Use These Files

1. Read this index first
2. Read only the next required detailed plan file
3. Execute one plan at a time
4. Do not open unrelated plans while implementing a focused workstream unless the plan explicitly requires it
5. When a plan is complete, report back before moving to the next one

6. Respect the mandatory SQL seed-data policy in this file and in Plan 01 / Plan 06
