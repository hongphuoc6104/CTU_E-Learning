# Project Expansion Plan 02 - Storefront, Cart, Order, Payment

Generated: 2026-03-24

This plan covers the public and student-facing commerce side of the expanded product.

The project remains a course-selling website.
The difference is that payment and enrollment now follow explicit states instead of the current simplified mock-success flow.

## Objective

Upgrade the storefront and checkout journey so that:

- students buy courses through proper order states
- payment is submitted and later verified
- enrollment is created only after verified payment
- the website still feels like a course-commerce site

## Dependencies

This plan assumes Plan 01 foundation exists or is being implemented in the same wave.

Required dependencies:

- `order_master`
- `order_item`
- `payment`
- `enrollment`
- updated `course` publishing fields

## Scope In This Plan

### Public pages to extend

- `ELearning/index.php`
- `ELearning/courses.php`
- `ELearning/coursedetails.php`

### Student commerce pages to extend

- `ELearning/cart_api.php`
- `ELearning/Student/myCart.php`
- `ELearning/checkout.php`
- `ELearning/checkout_action.php`
- add student order/payment history pages if needed

### Auth support pages that may need targeted updates

- `ELearning/login.php`
- `ELearning/signup.php`
- `ELearning/Student/addstudent.php`

## Target Commerce Flow

### Guest to student conversion

1. Guest browses public pages
2. Guest views course detail
3. Guest signs up or logs in
4. Student adds a course to cart or buys directly

### Cart and order creation

1. Student adds one or more published active courses to cart
2. Student opens cart
3. Student creates order from:
   - cart, or
   - single-course buy now
4. System creates `order_master` + `order_item`
5. System sets:
   - `order_status = pending`
   - `payment_status = pending`

### Payment submission

1. Student selects payment method
2. Student sees transfer instructions or QR info
3. Student submits:
   - payment reference, and/or
   - proof image/file
4. System updates:
   - `payment_status = submitted`
   - `order_status = awaiting_verification`

### Payment verification

1. Admin reviews payment
2. If valid:
   - `payment_status = verified`
   - `order_status = paid`
   - create `enrollment`
3. If invalid:
   - `payment_status = rejected`
   - `order_status = failed`

## Mandatory Business Rules

1. Public course visibility uses only published, active courses
2. Students can add only published, active, not-yet-owned courses to cart
3. Ownership is determined by `enrollment`, not by cart rows or payment submission alone
4. A course becomes available in "My Courses" only after successful verified payment and active enrollment
5. Cart rows, orders, and enrollments must not drift logically

## UI And Route Requirements

### Public pages

- course cards must show one of these states:
  - not logged in -> buy CTA
  - logged in but not enrolled -> add to cart / buy now
  - enrolled -> go to course / already owned

### Cart page

- list only valid active cart rows tied to active published courses
- show subtotal and order creation CTA
- allow remove from cart

### Checkout page

- must no longer imply instant success
- must show:
  - order summary
  - payment instructions
  - payment submission form
  - current order status

### Student order history page

Add a dedicated page if needed to show:

- order code
- order date
- order total
- payment status
- order status
- proof status

## Data Validation Rules

### Cart validation

- reject deleted/unpublished courses
- reject already enrolled courses
- reject duplicate cart rows

### Order validation

- validate all order items before creating the order
- freeze price snapshot in `order_item`

### Payment validation

- payment proof file type/size must be validated if uploads are used
- payment reference length/format must be validated
- if payment submission is incomplete, keep order in `pending`

## Error Handling Requirements

1. If a course is unpublished or deleted before checkout, show a clear order error and stop
2. If a student submits incomplete payment info, show inline validation
3. If admin rejects payment, student must see the reason and next step
4. If enrollment creation fails after payment verification, rollback and show an admin error state

## Existing Files To Build On

- `ELearning/cart_api.php`
- `ELearning/Student/myCart.php`
- `ELearning/checkout.php`
- `ELearning/checkout_action.php`

These files should be evolved, not randomly replaced.

## New Files Allowed In This Plan

If needed, add focused new pages such as:

- `ELearning/Student/myOrders.php`
- `ELearning/Student/orderDetails.php`

Keep naming consistent with the current project style.

## Explicit Out Of Scope For Plan 02

- real payment gateway
- coupons/discount engine
- refunds workflow beyond status placeholder
- instructor course authoring
- student progress inside course player

## Acceptance Criteria

1. Student can add valid courses to cart
2. Student can create order from cart or direct purchase
3. Student can submit payment proof/details
4. Admin can later verify payment and trigger enrollment
5. My Courses only shows enrolled/paid courses
6. Public ownership state is correct everywhere
7. Existing public browsing flow remains intact

## Handoff Note For The Next AI

Do not implement learning content types in this plan.
This plan is about selling courses and granting access only after valid payment state transitions.
