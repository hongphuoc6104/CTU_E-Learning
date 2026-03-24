-- Plan 06 seed validation checks (preparation lane)
-- Run this after importing SQL/lms_db.sql into database lms_db.
-- This script is read-only and emits PASS/FAIL rows for rollout preparation.

DROP TEMPORARY TABLE IF EXISTS plan06_seed_checks;

CREATE TEMPORARY TABLE plan06_seed_checks (
  check_id VARCHAR(64) NOT NULL,
  status VARCHAR(8) NOT NULL,
  actual_count INT NOT NULL,
  expected_min INT NOT NULL,
  notes VARCHAR(255) NOT NULL
);

INSERT INTO plan06_seed_checks (check_id, status, actual_count, expected_min, notes)
SELECT
  t.check_id,
  CASE WHEN t.actual_count >= t.expected_min THEN 'PASS' ELSE 'FAIL' END AS status,
  t.actual_count,
  t.expected_min,
  t.notes
FROM (
  SELECT 'A01_admin_active' AS check_id, q.cnt AS actual_count, 1 AS expected_min, 'At least 1 active admin' AS notes
  FROM (SELECT COUNT(*) AS cnt FROM admin WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A02_instructor_active', q.cnt, 2, 'At least 2 active instructors'
  FROM (SELECT COUNT(*) AS cnt FROM instructor WHERE is_deleted = 0 AND ins_status = 'active') q

  UNION ALL
  SELECT 'A03_student_active', q.cnt, 8, 'At least 8 active students'
  FROM (SELECT COUNT(*) AS cnt FROM student WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A04_course_published', q.cnt, 2, 'Published courses exist'
  FROM (SELECT COUNT(*) AS cnt FROM course WHERE is_deleted = 0 AND course_status = 'published') q

  UNION ALL
  SELECT 'A05_course_draft', q.cnt, 1, 'Draft course exists'
  FROM (SELECT COUNT(*) AS cnt FROM course WHERE is_deleted = 0 AND course_status = 'draft') q

  UNION ALL
  SELECT 'A06_course_pending_review', q.cnt, 1, 'Pending-review course exists'
  FROM (SELECT COUNT(*) AS cnt FROM course WHERE is_deleted = 0 AND course_status = 'pending_review') q

  UNION ALL
  SELECT 'A07_learning_items_total', q.cnt, 20, 'Learning item baseline volume exists'
  FROM (SELECT COUNT(*) AS cnt FROM learning_item WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A08_learning_item_type_diversity', q.cnt, 6, 'All 6 required learning item types exist'
  FROM (SELECT COUNT(DISTINCT item_type) AS cnt FROM learning_item WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A09_order_status_diversity', q.cnt, 5, 'Order status diversity exists'
  FROM (SELECT COUNT(DISTINCT order_status) AS cnt FROM order_master WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A10_payment_status_diversity', q.cnt, 4, 'Payment status diversity exists'
  FROM (SELECT COUNT(DISTINCT payment_status) AS cnt FROM payment) q

  UNION ALL
  SELECT 'A11_enrollment_status_diversity', q.cnt, 2, 'Enrollment status diversity for final certification'
  FROM (SELECT COUNT(DISTINCT enrollment_status) AS cnt FROM enrollment) q

  UNION ALL
  SELECT 'A12_live_status_diversity', q.cnt, 3, 'Live session status diversity exists'
  FROM (SELECT COUNT(DISTINCT session_status) AS cnt FROM live_session WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A13_replay_asset_exists', q.cnt, 1, 'Replay assets exist'
  FROM (SELECT COUNT(*) AS cnt FROM replay_asset WHERE is_deleted = 0 AND TRIM(COALESCE(recording_url, '')) <> '') q

  UNION ALL
  SELECT 'A14_quiz_attempt_exists', q.cnt, 1, 'Quiz attempts exist'
  FROM (SELECT COUNT(*) AS cnt FROM quiz_attempt WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'A15_cred_admin_main', q.cnt, 1, 'Seed admin account admin@gmail.com exists'
  FROM (SELECT COUNT(*) AS cnt FROM admin WHERE admin_email = 'admin@gmail.com' AND is_deleted = 0) q

  UNION ALL
  SELECT 'A16_cred_instructor_main', q.cnt, 1, 'Seed instructor account exists'
  FROM (SELECT COUNT(*) AS cnt FROM instructor WHERE ins_email = 'chau.instructor@example.com' AND is_deleted = 0) q

  UNION ALL
  SELECT 'A17_cred_student_main', q.cnt, 1, 'Seed student account exists'
  FROM (SELECT COUNT(*) AS cnt FROM student WHERE stu_email = 'cap@example.com' AND is_deleted = 0) q

  UNION ALL
  SELECT 'B01_dataset_admin', q.cnt, 1, 'Mandatory dataset: admin'
  FROM (SELECT COUNT(*) AS cnt FROM admin WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'B02_dataset_instructor', q.cnt, 1, 'Mandatory dataset: instructors'
  FROM (SELECT COUNT(*) AS cnt FROM instructor WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'B03_dataset_student_multistate', q.cnt, 1, 'Mandatory dataset: students in multiple states'
  FROM (
    SELECT CASE
      WHEN np.no_purchase_students > 0 AND pp.paid_students > 0 THEN 1
      ELSE 0
    END AS cnt
    FROM (
      SELECT COUNT(*) AS no_purchase_students
      FROM student s
      WHERE s.is_deleted = 0
        AND NOT EXISTS (
          SELECT 1 FROM order_master om
          WHERE om.student_id = s.stu_id AND om.is_deleted = 0
        )
        AND NOT EXISTS (
          SELECT 1 FROM courseorder co
          WHERE co.stu_email = s.stu_email AND co.is_deleted = 0 AND co.status = 'TXN_SUCCESS'
        )
    ) np,
    (
      SELECT COUNT(DISTINCT om.student_id) AS paid_students
      FROM order_master om
      WHERE om.is_deleted = 0 AND om.order_status = 'paid'
    ) pp
  ) q

  UNION ALL
  SELECT 'B04_dataset_course_multistate', q.cnt, 3, 'Mandatory dataset: courses in multiple states'
  FROM (SELECT COUNT(DISTINCT course_status) AS cnt FROM course WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'B05_dataset_item_multitype', q.cnt, 6, 'Mandatory dataset: learning items in multiple types'
  FROM (SELECT COUNT(DISTINCT item_type) AS cnt FROM learning_item WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'B06_dataset_payment_multistate', q.cnt, 4, 'Mandatory dataset: payments in multiple states'
  FROM (SELECT COUNT(DISTINCT payment_status) AS cnt FROM payment) q

  UNION ALL
  SELECT 'B07_dataset_enrollment_multistate', q.cnt, 2, 'Mandatory dataset: enrollments in multiple states'
  FROM (SELECT COUNT(DISTINCT enrollment_status) AS cnt FROM enrollment) q

  UNION ALL
  SELECT 'B08_dataset_live_multistate', q.cnt, 3, 'Mandatory dataset: live sessions in multiple states'
  FROM (SELECT COUNT(DISTINCT session_status) AS cnt FROM live_session WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'B09_dataset_replay_links', q.cnt, 1, 'Mandatory dataset: replay links'
  FROM (SELECT COUNT(*) AS cnt FROM replay_asset WHERE is_deleted = 0 AND TRIM(COALESCE(recording_url, '')) <> '') q

  UNION ALL
  SELECT 'B10_dataset_quiz_attempts', q.cnt, 1, 'Mandatory dataset: quiz attempts'
  FROM (SELECT COUNT(*) AS cnt FROM quiz_attempt WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'C01_req_published_selfpaced_mix', q.cnt, 1, 'Required set #1: published self-paced with video/article/document/quiz'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM course c
    WHERE c.is_deleted = 0
      AND c.course_status = 'published'
      AND c.course_type = 'self_paced'
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'video'
      )
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'article'
      )
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'document'
      )
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'quiz'
      )
  ) q

  UNION ALL
  SELECT 'C02_req_published_blended_live_replay', q.cnt, 1, 'Required set #2: blended course with live and replay'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM course c
    WHERE c.is_deleted = 0
      AND c.course_status = 'published'
      AND c.course_type = 'blended'
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'live_session'
      )
      AND EXISTS (
        SELECT 1 FROM learning_item li
        WHERE li.course_id = c.course_id AND li.is_deleted = 0 AND li.content_status = 'published' AND li.item_type = 'replay'
      )
  ) q

  UNION ALL
  SELECT 'C03_req_draft_instructor_course', q.cnt, 1, 'Required set #3: draft instructor course'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM course c
    INNER JOIN instructor i ON i.ins_id = c.instructor_id AND i.is_deleted = 0
    WHERE c.is_deleted = 0 AND c.course_status = 'draft'
  ) q

  UNION ALL
  SELECT 'C04_req_pending_review_course', q.cnt, 1, 'Required set #4: pending-review instructor course'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM course c
    INNER JOIN instructor i ON i.ins_id = c.instructor_id AND i.is_deleted = 0
    WHERE c.is_deleted = 0 AND c.course_status = 'pending_review'
  ) q

  UNION ALL
  SELECT 'C05_req_student_no_purchase', q.cnt, 1, 'Required set #5: student with no purchases'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM student s
    WHERE s.is_deleted = 0
      AND NOT EXISTS (
        SELECT 1 FROM order_master om
        WHERE om.student_id = s.stu_id AND om.is_deleted = 0
      )
      AND NOT EXISTS (
        SELECT 1 FROM courseorder co
        WHERE co.stu_email = s.stu_email AND co.is_deleted = 0 AND co.status = 'TXN_SUCCESS'
      )
  ) q

  UNION ALL
  SELECT 'C06_req_student_cart_no_paid', q.cnt, 1, 'Required set #6: student with cart and no paid order'
  FROM (
    SELECT COUNT(DISTINCT s.stu_id) AS cnt
    FROM student s
    WHERE s.is_deleted = 0
      AND EXISTS (
        SELECT 1 FROM cart c
        WHERE c.stu_email = s.stu_email AND c.is_deleted = 0
      )
      AND NOT EXISTS (
        SELECT 1 FROM order_master om
        WHERE om.student_id = s.stu_id AND om.is_deleted = 0 AND om.order_status = 'paid'
      )
      AND NOT EXISTS (
        SELECT 1 FROM courseorder co
        WHERE co.stu_email = s.stu_email AND co.is_deleted = 0 AND co.status = 'TXN_SUCCESS'
      )
  ) q

  UNION ALL
  SELECT 'C07_req_paid_partial_progress', q.cnt, 1, 'Required set #7: paid enrollment with partial progress'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM enrollment e
    LEFT JOIN order_master om ON om.order_id = e.order_id
    WHERE e.progress_percent > 0
      AND e.progress_percent < 100
      AND e.enrollment_status = 'active'
      AND (e.order_id IS NULL OR (om.is_deleted = 0 AND om.order_status = 'paid'))
  ) q

  UNION ALL
  SELECT 'C08_req_completed_with_quiz_pass', q.cnt, 1, 'Required set #8: completed progress with quiz pass'
  FROM (
    SELECT COUNT(DISTINCT e.enrollment_id) AS cnt
    FROM enrollment e
    WHERE e.progress_percent >= 100
      AND EXISTS (
        SELECT 1 FROM quiz_attempt qa
        WHERE qa.student_id = e.student_id
          AND qa.course_id = e.course_id
          AND qa.passed = 1
          AND qa.is_deleted = 0
      )
  ) q

  UNION ALL
  SELECT 'C09_req_awaiting_verification', q.cnt, 1, 'Required set #9: awaiting verification with submitted payment metadata'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM order_master om
    INNER JOIN payment p ON p.order_id = om.order_id
    WHERE om.is_deleted = 0
      AND om.order_status = 'awaiting_verification'
      AND p.payment_status = 'submitted'
      AND (
        TRIM(COALESCE(p.payment_reference, '')) <> ''
        OR TRIM(COALESCE(p.payment_proof_url, '')) <> ''
      )
  ) q

  UNION ALL
  SELECT 'C10_req_failed_and_cancelled', q.cnt, 1, 'Required set #10: failed/rejected and cancelled order examples'
  FROM (
    SELECT CASE
      WHEN x.failed_rejected_count > 0 AND x.cancelled_count > 0 THEN 1
      ELSE 0
    END AS cnt
    FROM (
      SELECT
        SUM(CASE WHEN om.order_status = 'failed' AND p.payment_status = 'rejected' THEN 1 ELSE 0 END) AS failed_rejected_count,
        SUM(CASE WHEN om.order_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
      FROM order_master om
      LEFT JOIN payment p ON p.order_id = om.order_id
      WHERE om.is_deleted = 0
    ) x
  ) q

  UNION ALL
  SELECT 'D01_compat_lesson_rows', q.cnt, 1, 'Compatibility: legacy lesson rows still present'
  FROM (SELECT COUNT(*) AS cnt FROM lesson WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'D02_compat_learning_item_rows', q.cnt, 1, 'Compatibility: new learning item rows present'
  FROM (SELECT COUNT(*) AS cnt FROM learning_item WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'D03_compat_legacy_orders', q.cnt, 1, 'Compatibility: legacy courseorder rows present'
  FROM (SELECT COUNT(*) AS cnt FROM courseorder WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'D04_compat_new_orders', q.cnt, 1, 'Compatibility: new order_master rows present'
  FROM (SELECT COUNT(*) AS cnt FROM order_master WHERE is_deleted = 0) q

  UNION ALL
  SELECT 'D05_compat_enrollment_from_paid', q.cnt, 1, 'Compatibility bridge: paid orders linked to enrollments'
  FROM (
    SELECT COUNT(*) AS cnt
    FROM enrollment e
    INNER JOIN order_master om ON om.order_id = e.order_id
    WHERE om.is_deleted = 0 AND om.order_status = 'paid'
  ) q
) t;

SELECT check_id, status, actual_count, expected_min, notes
FROM plan06_seed_checks
ORDER BY check_id;

SELECT status, COUNT(*) AS check_count
FROM plan06_seed_checks
GROUP BY status
ORDER BY status;
