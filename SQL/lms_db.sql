-- CTU E-Learning bootstrap
-- Plan 01: Domain and Schema foundation
-- Select database `lms_db` before importing this file.
-- Seed credentials:
--   Admin: admin@gmail.com / admin
--   Instructor: chau.instructor@example.com / instructor123
--   Students: cap@example.com / 123456

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `session_attendance`;
DROP TABLE IF EXISTS `quiz_attempt`;
DROP TABLE IF EXISTS `learning_progress`;
DROP TABLE IF EXISTS `enrollment`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `order_item`;
DROP TABLE IF EXISTS `order_master`;
DROP TABLE IF EXISTS `learning_item`;
DROP TABLE IF EXISTS `replay_asset`;
DROP TABLE IF EXISTS `live_session`;
DROP TABLE IF EXISTS `quiz_answer`;
DROP TABLE IF EXISTS `quiz_question`;
DROP TABLE IF EXISTS `quiz`;
DROP TABLE IF EXISTS `course_section`;
DROP TABLE IF EXISTS `instructor`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `lesson`;
DROP TABLE IF EXISTS `courseorder`;
DROP TABLE IF EXISTS `contact_message`;
DROP TABLE IF EXISTS `course`;
DROP TABLE IF EXISTS `student`;
DROP TABLE IF EXISTS `admin`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `admin` (
  `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_name` VARCHAR(120) NOT NULL,
  `admin_email` VARCHAR(191) NOT NULL,
  `admin_pass` VARCHAR(255) NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_email` (`admin_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `student` (
  `stu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stu_name` VARCHAR(100) NOT NULL,
  `stu_email` VARCHAR(191) NOT NULL,
  `stu_pass` VARCHAR(255) NOT NULL,
  `stu_occ` VARCHAR(150) NOT NULL DEFAULT '',
  `stu_img` VARCHAR(255) NOT NULL DEFAULT '',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stu_id`),
  UNIQUE KEY `uq_student_email` (`stu_email`),
  KEY `idx_student_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `instructor` (
  `ins_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ins_name` VARCHAR(150) NOT NULL,
  `ins_email` VARCHAR(191) NOT NULL,
  `ins_pass` VARCHAR(255) NOT NULL,
  `ins_bio` TEXT DEFAULT NULL,
  `ins_img` VARCHAR(255) NOT NULL DEFAULT '',
  `ins_status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ins_id`),
  UNIQUE KEY `uq_instructor_email` (`ins_email`),
  KEY `idx_instructor_status` (`ins_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `course` (
  `course_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_name` VARCHAR(255) NOT NULL,
  `course_desc` LONGTEXT NOT NULL,
  `course_author` VARCHAR(150) NOT NULL,
  `course_img` VARCHAR(255) NOT NULL,
  `course_duration` VARCHAR(100) NOT NULL,
  `course_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `course_original_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `instructor_id` INT UNSIGNED DEFAULT NULL,
  `course_status` ENUM('draft', 'pending_review', 'published', 'archived') NOT NULL DEFAULT 'published',
  `course_type` ENUM('self_paced', 'blended') NOT NULL DEFAULT 'self_paced',
  `published_at` DATETIME DEFAULT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_id`),
  KEY `idx_course_instructor` (`instructor_id`),
  KEY `idx_course_status` (`course_status`),
  KEY `idx_course_deleted` (`is_deleted`),
  CONSTRAINT `fk_course_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`ins_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lesson` (
  `lesson_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_name` VARCHAR(255) NOT NULL,
  `lesson_desc` TEXT DEFAULT NULL,
  `lesson_link` VARCHAR(1000) NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `course_name` VARCHAR(255) NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lesson_id`),
  KEY `idx_lesson_course` (`course_id`),
  KEY `idx_lesson_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cart` (
  `cart_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stu_email` VARCHAR(191) NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `added_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `uq_cart_student_course` (`stu_email`, `course_id`),
  KEY `idx_cart_student_deleted` (`stu_email`, `is_deleted`),
  KEY `idx_cart_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `courseorder` (
  `co_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` VARCHAR(100) NOT NULL,
  `stu_email` VARCHAR(191) NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'TXN_SUCCESS',
  `respmsg` TEXT DEFAULT NULL,
  `amount` INT UNSIGNED NOT NULL DEFAULT 0,
  `order_date` DATE NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`co_id`),
  KEY `idx_courseorder_student_status` (`stu_email`, `status`, `is_deleted`),
  KEY `idx_courseorder_course` (`course_id`),
  KEY `idx_courseorder_date` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `feedback` (
  `f_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `f_content` TEXT NOT NULL,
  `stu_id` INT UNSIGNED NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`f_id`),
  KEY `idx_feedback_student` (`stu_id`),
  KEY `idx_feedback_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contact_message` (
  `c_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(150) NOT NULL DEFAULT '',
  `email` VARCHAR(191) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`c_id`),
  KEY `idx_contact_created` (`created_at`),
  KEY `idx_contact_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `course_section` (
  `section_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `section_title` VARCHAR(255) NOT NULL,
  `section_position` INT UNSIGNED NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_id`),
  KEY `idx_course_section_course_position` (`course_id`, `section_position`),
  KEY `idx_course_section_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz` (
  `quiz_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED DEFAULT NULL,
  `quiz_title` VARCHAR(255) NOT NULL,
  `quiz_description` TEXT DEFAULT NULL,
  `pass_score` DECIMAL(5,2) NOT NULL DEFAULT 70.00,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
  `content_status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  KEY `idx_quiz_course` (`course_id`),
  KEY `idx_quiz_section` (`section_id`),
  KEY `idx_quiz_status` (`content_status`),
  CONSTRAINT `fk_quiz_section` FOREIGN KEY (`section_id`) REFERENCES `course_section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz_question` (
  `question_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('single_choice') NOT NULL DEFAULT 'single_choice',
  `question_position` INT UNSIGNED NOT NULL,
  `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `idx_quiz_question_quiz_position` (`quiz_id`, `question_position`),
  CONSTRAINT `fk_quiz_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz_answer` (
  `answer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `answer_text` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `answer_position` INT UNSIGNED NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`answer_id`),
  KEY `idx_quiz_answer_question_position` (`question_id`, `answer_position`),
  CONSTRAINT `fk_quiz_answer_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_question` (`question_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `live_session` (
  `live_session_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED DEFAULT NULL,
  `instructor_id` INT UNSIGNED DEFAULT NULL,
  `session_title` VARCHAR(255) NOT NULL,
  `session_description` TEXT DEFAULT NULL,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME NOT NULL,
  `join_url` VARCHAR(1000) NOT NULL,
  `platform_name` VARCHAR(100) NOT NULL,
  `session_status` ENUM('scheduled', 'live', 'ended', 'replay_available', 'cancelled') NOT NULL DEFAULT 'scheduled',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`live_session_id`),
  KEY `idx_live_session_course` (`course_id`),
  KEY `idx_live_session_course_instructor_status_start` (`course_id`, `instructor_id`, `session_status`, `start_at`),
  KEY `idx_live_session_section` (`section_id`),
  CONSTRAINT `fk_live_session_section` FOREIGN KEY (`section_id`) REFERENCES `course_section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_live_session_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`ins_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `replay_asset` (
  `replay_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `live_session_id` INT UNSIGNED NOT NULL,
  `recording_url` VARCHAR(1000) NOT NULL,
  `recording_provider` VARCHAR(100) NOT NULL,
  `available_at` DATETIME NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`replay_id`),
  UNIQUE KEY `uq_replay_live_session` (`live_session_id`),
  CONSTRAINT `fk_replay_asset_live_session` FOREIGN KEY (`live_session_id`) REFERENCES `live_session` (`live_session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `learning_item` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NOT NULL,
  `item_title` VARCHAR(255) NOT NULL,
  `item_type` ENUM('video', 'article', 'document', 'quiz', 'live_session', 'replay') NOT NULL,
  `item_position` INT UNSIGNED NOT NULL,
  `is_preview` TINYINT(1) NOT NULL DEFAULT 0,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `content_status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `video_url` VARCHAR(1000) DEFAULT NULL,
  `article_content` LONGTEXT DEFAULT NULL,
  `document_url` VARCHAR(1000) DEFAULT NULL,
  `quiz_id` INT UNSIGNED DEFAULT NULL,
  `live_session_id` INT UNSIGNED DEFAULT NULL,
  `replay_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_learning_item_course_section` (`course_id`, `section_id`),
  KEY `idx_learning_item_type` (`item_type`),
  KEY `idx_learning_item_position` (`course_id`, `section_id`, `item_position`),
  CONSTRAINT `fk_learning_item_section` FOREIGN KEY (`section_id`) REFERENCES `course_section` (`section_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_item_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_item_live_session` FOREIGN KEY (`live_session_id`) REFERENCES `live_session` (`live_session_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_item_replay` FOREIGN KEY (`replay_id`) REFERENCES `replay_asset` (`replay_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `order_master` (
  `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `order_code` VARCHAR(100) NOT NULL,
  `order_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `order_status` ENUM('pending', 'awaiting_verification', 'paid', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uq_order_master_code` (`order_code`),
  KEY `idx_order_master_student_status` (`student_id`, `order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `order_item` (
  `order_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `unit_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `item_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item_order_course` (`order_id`, `course_id`),
  CONSTRAINT `fk_order_item_order_master` FOREIGN KEY (`order_id`) REFERENCES `order_master` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payment` (
  `payment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_reference` VARCHAR(120) NOT NULL,
  `payment_proof_url` VARCHAR(1000) DEFAULT NULL,
  `payment_status` ENUM('pending', 'submitted', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  `verified_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `idx_payment_order_status` (`order_id`, `payment_status`),
  KEY `idx_payment_verified_by` (`verified_by_admin_id`),
  CONSTRAINT `fk_payment_order_master` FOREIGN KEY (`order_id`) REFERENCES `order_master` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `enrollment` (
  `enrollment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `enrollment_status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
  `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  `progress_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `uq_enrollment_student_course` (`student_id`, `course_id`),
  KEY `idx_enrollment_status` (`enrollment_status`),
  CONSTRAINT `fk_enrollment_order_master` FOREIGN KEY (`order_id`) REFERENCES `order_master` (`order_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `learning_progress` (
  `progress_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `progress_status` ENUM('not_started', 'in_progress', 'completed', 'passed') NOT NULL DEFAULT 'not_started',
  `last_accessed_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `watch_percent` DECIMAL(5,2) DEFAULT NULL,
  `last_position_seconds` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `uq_learning_progress_student_item` (`student_id`, `item_id`),
  KEY `idx_learning_progress_student_course_item` (`student_id`, `course_id`, `item_id`),
  CONSTRAINT `fk_learning_progress_item` FOREIGN KEY (`item_id`) REFERENCES `learning_item` (`item_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz_attempt` (
  `attempt_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED DEFAULT NULL,
  `attempt_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `passed` TINYINT(1) NOT NULL DEFAULT 0,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` DATETIME DEFAULT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_quiz_attempt_student_quiz` (`student_id`, `quiz_id`),
  KEY `idx_quiz_attempt_course` (`course_id`),
  CONSTRAINT `fk_quiz_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_quiz_attempt_item` FOREIGN KEY (`item_id`) REFERENCES `learning_item` (`item_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `session_attendance` (
  `attendance_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `live_session_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `joined_at` DATETIME DEFAULT NULL,
  `left_at` DATETIME DEFAULT NULL,
  `attendance_status` VARCHAR(50) NOT NULL DEFAULT 'registered',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  KEY `idx_session_attendance_live_session` (`live_session_id`),
  KEY `idx_session_attendance_student` (`student_id`),
  CONSTRAINT `fk_session_attendance_live_session` FOREIGN KEY (`live_session_id`) REFERENCES `live_session` (`live_session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`admin_id`, `admin_name`, `admin_email`, `admin_pass`) VALUES
(1, 'CTU Super Admin', 'admin@gmail.com', '$2b$12$oX5cHFwtjgWIpUze110GQ.Uj9QnQlkQfyB1l3ND6ekPYGWMYpd3RK'),
(2, 'Operations Admin', 'operations.admin@example.com', '$2b$12$oX5cHFwtjgWIpUze110GQ.Uj9QnQlkQfyB1l3ND6ekPYGWMYpd3RK');

INSERT INTO `instructor` (`ins_id`, `ins_name`, `ins_email`, `ins_pass`, `ins_bio`, `ins_img`, `ins_status`) VALUES
(1, 'Nguyễn Minh Châu', 'chau.instructor@example.com', '$2b$12$uXWj1c2zaNJOOl39MGoy5uHRQKvalNBs7BuDkfmEYHwuT7niouBNm', 'Chuyên gia UI/UX với hơn 8 năm làm sản phẩm SaaS và mentoring cho đội ngũ thiết kế trẻ.', 'image/stu/student2.jpg', 'active'),
(2, 'Trần Hoàng Long', 'long.live@example.com', '$2b$12$uXWj1c2zaNJOOl39MGoy5uHRQKvalNBs7BuDkfmEYHwuT7niouBNm', 'Giảng viên frontend thiên về workshop live, tối ưu hóa lộ trình học theo từng buổi thực chiến.', 'image/stu/student3.jpg', 'active'),
(3, 'Lê Bảo Ngọc', 'ngoc.creator@example.com', '$2b$12$uXWj1c2zaNJOOl39MGoy5uHRQKvalNBs7BuDkfmEYHwuT7niouBNm', 'Creator nội dung học trực tuyến, chuyên đóng gói khóa học theo format gọn, dễ demo và dễ rollout.', 'image/stu/student4.jpg', 'active');

INSERT INTO `student` (`stu_id`, `stu_name`, `stu_email`, `stu_pass`, `stu_occ`, `stu_img`) VALUES
(1, 'Cáp Nguyễn', 'cap@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Junior UI Designer', 'image/stu/student1.jpg'),
(2, 'Hoàn Trần', 'hoan.thanh@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Frontend Intern', 'image/stu/student2.jpg'),
(3, 'Lan Phạm', 'lan.cart@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'QA Fresher', 'image/stu/student3.jpg'),
(4, 'Minh Trần', 'minh.tran@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Sinh viên CNTT', 'image/stu/student4.jpg'),
(5, 'Khánh Võ', 'xacminh.pay@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Kế toán nội bộ', 'image/stu/default_avatar.png'),
(6, 'Nhật Lê', 'rejected.order@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Nhân viên vận hành', 'image/stu/pexels-photo-120222.jpeg'),
(7, 'Thảo Phan', 'thao.live@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Frontend Developer', 'image/stu/avatar_student_1_1773470896038.png'),
(8, 'Thành Đỗ', 'thanh.multi@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Data Analyst', 'image/stu/avatar_student_2_1773470913282.png'),
(9, 'Duyên Bùi', 'duyen.new@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Sinh viên Marketing', 'image/stu/avatar_student_3_1773470934821.png'),
(10, 'An Hồ', 'an.quiz@example.com', '$2b$12$cV3zrKGVe756d/kg7e3hS.iU2NcaI4W0O7AuYFC3HW1UA4GvLioHe', 'Freelancer', 'image/stu/default_avatar.png');

INSERT INTO `course` (`course_id`, `course_name`, `course_desc`, `course_author`, `course_img`, `course_duration`, `course_price`, `course_original_price`, `instructor_id`, `course_status`, `course_type`, `published_at`) VALUES
(1, 'Thiết kế UI Figma từ Zero đến Prototype', 'Khóa học self-paced giúp người mới đi từ tư duy bố cục, wireframe đến prototype hoàn chỉnh cho landing page bán khóa học.', 'Nguyễn Minh Châu', 'image/courseimg/Banner1.jpeg', '6 tuần', 349000, 499000, 1, 'published', 'self_paced', '2026-02-10 09:00:00'),
(2, 'React Frontend Live Bootcamp', 'Khóa blended kết hợp video nền tảng, workshop live và replay để học viên luyện dự án React theo từng tuần.', 'Trần Hoàng Long', 'image/courseimg/angular.jpg', '8 tuần', 899000, 1200000, 2, 'published', 'blended', '2026-02-18 08:30:00'),
(3, 'Excel văn phòng cho người đi làm', 'Bản nháp khóa học tối ưu thao tác báo cáo nội bộ bằng Excel, đang ở giai đoạn hoàn thiện outline và bài tập.', 'Lê Bảo Ngọc', 'image/courseimg/bgimage1.jpg', '4 tuần', 299000, 449000, 3, 'draft', 'self_paced', NULL),
(4, 'Laravel Team Project Workflow', 'Khóa đang chờ review, tập trung vào quy trình làm việc nhóm, phân chia backlog và API CRUD cho dự án web nội bộ.', 'Nguyễn Minh Châu', 'image/courseimg/php.jpg', '7 tuần', 649000, 790000, 1, 'pending_review', 'self_paced', NULL),
(5, 'Python Data Analysis cho người mới', 'Lộ trình học dữ liệu thực dụng với file mẫu bán lẻ, Pandas và cách đọc insight dành cho người làm sản phẩm.', 'Lê Bảo Ngọc', 'image/courseimg/Python.jpg', '5 tuần', 559000, 699000, 3, 'published', 'self_paced', '2026-01-28 10:15:00'),
(6, 'Guitar cơ bản trong 14 ngày', 'Khóa nhập môn guitar dành cho người bận rộn, tập trung vào tư thế tay, nhịp điệu cơ bản và lịch luyện ngắn mỗi ngày.', 'Trần Hoàng Long', 'image/courseimg/Guitar.jpg', '14 ngày', 289000, 399000, 2, 'published', 'self_paced', '2026-01-20 17:00:00'),
(7, 'Machine Learning Fundamentals', 'Khóa nền tảng về thuật ngữ, metric và tư duy huấn luyện mô hình dành cho người mới bước vào AI/ML.', 'Lê Bảo Ngọc', 'image/courseimg/Machine.jpg', '6 tuần', 749000, 990000, 3, 'published', 'self_paced', '2026-02-05 14:00:00'),
(8, 'Vue Dashboard cho team nội bộ', 'Khóa đã lưu trữ dùng cho mục đích tham khảo nội bộ về cấu trúc dashboard, component reuse và checklist bàn giao.', 'Trần Hoàng Long', 'image/courseimg/vue.jpg', '3 tuần', 459000, 599000, 2, 'archived', 'self_paced', '2025-12-01 09:45:00');

INSERT INTO `course_section` (`section_id`, `course_id`, `section_title`, `section_position`) VALUES
(1, 1, 'Khởi động với tư duy UI', 1),
(2, 1, 'Wireframe và cấu trúc màn hình', 2),
(3, 1, 'Prototype và review', 3),
(4, 2, 'Chuẩn bị bootcamp', 1),
(5, 2, 'Workshop live số 1 và 2', 2),
(6, 2, 'Workshop tổng kết và replay', 3),
(7, 3, 'Bản nháp chương trình học', 1),
(8, 4, 'Đặt vấn đề và phạm vi dự án', 1),
(9, 4, 'Tài liệu phối hợp nhóm', 2),
(10, 5, 'Tư duy phân tích', 1),
(11, 5, 'Làm sạch và trực quan hóa', 2),
(12, 6, 'Nền tảng guitar', 1),
(13, 7, 'Khái niệm cốt lõi', 1),
(14, 7, 'Đánh giá mô hình', 2),
(15, 8, 'Kiến trúc dashboard', 1);

INSERT INTO `quiz` (`quiz_id`, `course_id`, `section_id`, `quiz_title`, `quiz_description`, `pass_score`, `max_attempts`, `content_status`) VALUES
(1, 1, 3, 'Quiz tổng kết Figma Prototype', 'Bộ câu hỏi nhanh để xác nhận học viên đã nắm wireframe, prototype và checklist review cơ bản.', 70.00, 3, 'published'),
(2, 2, 6, 'Quiz tốt nghiệp React Live Bootcamp', 'Quiz cuối khóa dùng để kiểm tra kiến thức React nền tảng sau khi hoàn thành video, live session và replay.', 75.00, 3, 'published');

INSERT INTO `quiz_question` (`question_id`, `quiz_id`, `question_text`, `question_type`, `question_position`, `points`) VALUES
(1, 1, 'Mục tiêu chính của wireframe là gì?', 'single_choice', 1, 20.00),
(2, 1, 'Prototype trong Figma thường được dùng để làm gì?', 'single_choice', 2, 20.00),
(3, 1, 'Khi review một màn hình checkout, nhóm nên ưu tiên kiểm tra điều gì?', 'single_choice', 3, 20.00),
(4, 1, 'Khi thiết kế mobile first, điều gì cần được ưu tiên?', 'single_choice', 4, 20.00),
(5, 1, 'Design system giúp ích nhiều nhất ở điểm nào?', 'single_choice', 5, 20.00),
(6, 2, 'Hook useState trong React dùng để làm gì?', 'single_choice', 1, 20.00),
(7, 2, 'React Router thường được dùng trong trường hợp nào?', 'single_choice', 2, 20.00),
(8, 2, 'Component React sẽ re-render khi nào?', 'single_choice', 3, 20.00),
(9, 2, 'Sau khi hoàn thành dự án React, bước nào thường cần cho deploy frontend tĩnh?', 'single_choice', 4, 20.00),
(10, 2, 'Mục tiêu của code splitting là gì?', 'single_choice', 5, 20.00);

INSERT INTO `quiz_answer` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `answer_position`) VALUES
(1, 1, 'Tô màu đầy đủ để trình bày với khách hàng', 0, 1),
(2, 1, 'Sắp xếp cấu trúc và luồng trước khi làm UI chi tiết', 1, 2),
(3, 1, 'Xuất file PDF để in ngay', 0, 3),
(4, 1, 'Chèn animation để bài trình bày sinh động hơn', 0, 4),
(5, 2, 'Mô phỏng luồng tương tác giữa các màn hình', 1, 1),
(6, 2, 'Nén ảnh trước khi upload lên server', 0, 2),
(7, 2, 'Sinh mã HTML tự động cho toàn bộ dự án', 0, 3),
(8, 2, 'Thay thế hoàn toàn bước user testing', 0, 4),
(9, 3, 'Màu nền website có giống banner hay không', 0, 1),
(10, 3, 'Spacing, hierarchy và CTA có rõ ràng hay không', 1, 2),
(11, 3, 'Số lượng icon trên thanh menu', 0, 3),
(12, 3, 'Loại font cài trên máy người dùng', 0, 4),
(13, 4, 'Ưu tiên nội dung quan trọng trên màn hình nhỏ', 1, 1),
(14, 4, 'Thiết kế desktop trước rồi thu nhỏ tùy ý', 0, 2),
(15, 4, 'Luôn dùng nhiều cột từ màn hình đầu tiên', 0, 3),
(16, 4, 'Bỏ qua khoảng cách giữa các vùng nội dung', 0, 4),
(17, 5, 'Giữ tính nhất quán giao diện và thành phần', 1, 1),
(18, 5, 'Thay thế hoàn toàn backlog sprint', 0, 2),
(19, 5, 'Giảm số lượng thành viên trong nhóm', 0, 3),
(20, 5, 'Bắt buộc đổi màu logo theo từng sprint', 0, 4),
(21, 6, 'Quản lý state cục bộ của component', 1, 1),
(22, 6, 'Tạo route động phía server', 0, 2),
(23, 6, 'Biến CSS thành JavaScript', 0, 3),
(24, 6, 'Tự động tối ưu hình ảnh khi build', 0, 4),
(25, 7, 'Điều hướng giữa các trang hoặc màn hình theo route', 1, 1),
(26, 7, 'Lưu mật khẩu người dùng trong session', 0, 2),
(27, 7, 'Kết nối trực tiếp tới MySQL trong browser', 0, 3),
(28, 7, 'Upload video lên CDN', 0, 4),
(29, 8, 'Khi state hoặc props thay đổi', 1, 1),
(30, 8, 'Chỉ khi refresh toàn bộ trình duyệt', 0, 2),
(31, 8, 'Chỉ khi file CSS bị thay đổi', 0, 3),
(32, 8, 'Chỉ khi admin tạo khóa học mới', 0, 4),
(33, 9, 'Build production assets trước khi đưa lên hosting', 1, 1),
(34, 9, 'Xóa package.json để giảm dung lượng', 0, 2),
(35, 9, 'Đổi toàn bộ file sang PHP', 0, 3),
(36, 9, 'Tắt cache của trình duyệt vĩnh viễn', 0, 4),
(37, 10, 'Giảm bundle tải ban đầu bằng cách chia nhỏ mã', 1, 1),
(38, 10, 'Thay thế hoàn toàn quá trình testing', 0, 2),
(39, 10, 'Bắt buộc dùng nhiều framework cùng lúc', 0, 3),
(40, 10, 'Làm mọi component render đồng thời', 0, 4);

INSERT INTO `live_session` (`live_session_id`, `course_id`, `section_id`, `instructor_id`, `session_title`, `session_description`, `start_at`, `end_at`, `join_url`, `platform_name`, `session_status`) VALUES
(1, 2, 5, 2, 'Workshop 1: Routing và layout', 'Buổi live nền tảng để học viên dựng routing, layout chung và giải đáp thắc mắc trước sprint đầu tiên.', '2026-03-05 19:30:00', '2026-03-05 21:00:00', 'https://meet.jit.si/ctu-react-bootcamp-routing', 'Jitsi Meet', 'ended'),
(2, 2, 5, 2, 'Workshop 2: State management thực chiến', 'Buổi live đã kết thúc, tập trung vào state lifting, custom hook và cách tổ chức state cho dashboard nhỏ.', '2026-03-12 19:30:00', '2026-03-12 21:00:00', 'https://meet.jit.si/ctu-react-bootcamp-state', 'Jitsi Meet', 'replay_available'),
(3, 2, 6, 2, 'Workshop 3: Deploy và tối ưu', 'Buổi tổng kết bootcamp, review quy trình build production, deploy và tối ưu tải trang cho frontend.', '2026-02-28 19:30:00', '2026-02-28 21:00:00', 'https://meet.jit.si/ctu-react-bootcamp-deploy', 'Jitsi Meet', 'replay_available'),
(4, 2, 6, 2, 'Q&A cohort mới tháng 4', 'Buổi live hỏi đáp mở cho cohort mới, dùng để demo khả năng lên lịch lớp trực tiếp trong khóa blended.', '2026-04-05 19:30:00', '2026-04-05 20:30:00', 'https://meet.jit.si/ctu-react-bootcamp-april-qa', 'Jitsi Meet', 'scheduled');

INSERT INTO `replay_asset` (`replay_id`, `live_session_id`, `recording_url`, `recording_provider`, `available_at`) VALUES
(1, 2, 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'YouTube', '2026-03-13 09:00:00'),
(2, 3, 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 'YouTube', '2026-03-01 09:00:00');

INSERT INTO `learning_item` (`item_id`, `course_id`, `section_id`, `item_title`, `item_type`, `item_position`, `is_preview`, `is_required`, `content_status`, `video_url`, `article_content`, `document_url`, `quiz_id`, `live_session_id`, `replay_id`) VALUES
(1, 1, 1, 'Chào mừng và tổng quan Figma', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', NULL, NULL, NULL, NULL, NULL),
(2, 1, 1, 'Tư duy UI và brief dự án', 'article', 2, 1, 1, 'published', NULL, '<h3>Tư duy UI trước khi vẽ</h3><p>Hãy xác định mục tiêu trang, chân dung người học và hành động chính cần thúc đẩy trước khi bắt đầu dựng layout.</p><ul><li>Xác định CTA chính</li><li>Ưu tiên nội dung theo mức quan trọng</li><li>Đồng bộ với thông điệp bán khóa học</li></ul>', NULL, NULL, NULL, NULL),
(3, 1, 2, 'Vẽ khung wireframe trên Figma', 'video', 1, 0, 1, 'published', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', NULL, NULL, NULL, NULL, NULL),
(4, 1, 2, 'Checklist design review', 'document', 2, 0, 1, 'published', NULL, NULL, 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', NULL, NULL, NULL),
(5, 1, 3, 'Dựng prototype cho luồng mua hàng', 'video', 1, 0, 1, 'published', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', NULL, NULL, NULL, NULL, NULL),
(6, 1, 3, 'Quiz tổng kết Figma Prototype', 'quiz', 2, 0, 1, 'published', NULL, NULL, NULL, 1, NULL, NULL),
(7, 2, 4, 'Định hướng React Live Bootcamp', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', NULL, NULL, NULL, NULL, NULL),
(8, 2, 4, 'Checklist trước buổi live đầu tiên', 'article', 2, 1, 1, 'published', NULL, '<h3>Chuẩn bị trước buổi live</h3><p>Đảm bảo đã cài Node.js, clone source mẫu và đọc trước checklist câu hỏi để buổi workshop đi nhanh hơn.</p><ul><li>Cài Node LTS</li><li>Kiểm tra camera và mic</li><li>Đăng nhập link họp trước 10 phút</li></ul>', NULL, NULL, NULL, NULL),
(9, 2, 4, 'Thiết lập Vite và React trong 15 phút', 'video', 3, 0, 1, 'published', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', NULL, NULL, NULL, NULL, NULL),
(10, 2, 5, 'Workshop live 1 - Routing và layout', 'live_session', 1, 0, 1, 'published', NULL, NULL, NULL, NULL, 1, NULL),
(11, 2, 5, 'Workshop live 2 - State management thực chiến', 'live_session', 2, 0, 1, 'published', NULL, NULL, NULL, NULL, 2, NULL),
(12, 2, 5, 'Replay workshop state management', 'replay', 3, 0, 1, 'published', NULL, NULL, NULL, NULL, NULL, 1),
(13, 2, 6, 'Workshop live 3 - Deploy và tối ưu', 'live_session', 1, 0, 1, 'published', NULL, NULL, NULL, NULL, 3, NULL),
(14, 2, 6, 'Replay workshop deploy và tối ưu', 'replay', 2, 0, 1, 'published', NULL, NULL, NULL, NULL, NULL, 2),
(15, 2, 6, 'Quiz tốt nghiệp React Bootcamp', 'quiz', 3, 0, 1, 'published', NULL, NULL, NULL, 2, NULL, NULL),
(16, 3, 7, 'Mở đầu khóa Excel văn phòng', 'video', 1, 1, 1, 'draft', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', NULL, NULL, NULL, NULL, NULL),
(17, 3, 7, 'Danh sách bài tập dự kiến', 'article', 2, 0, 0, 'draft', NULL, '<h3>Outline nháp</h3><p>Khóa học đang hoàn thiện phần bài tập tổng hợp báo cáo doanh số, bảng lương và báo cáo vận hành văn phòng.</p>', NULL, NULL, NULL, NULL),
(18, 4, 8, 'Giới thiệu dự án Laravel team', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=9No-FiEInLA', NULL, NULL, NULL, NULL, NULL),
(19, 4, 8, 'Quy trình review code và issue', 'article', 2, 0, 1, 'published', NULL, '<h3>Review code theo checklist</h3><p>Mỗi merge request cần có mô tả thay đổi, ảnh chụp màn hình và checklist kiểm thử tối thiểu trước khi reviewer xác nhận.</p>', NULL, NULL, NULL, NULL),
(20, 4, 9, 'Template backlog sprint', 'document', 1, 0, 1, 'published', NULL, NULL, 'https://www.orimi.com/pdf-test.pdf', NULL, NULL, NULL),
(21, 4, 9, 'Demo tạo API CRUD đầu tiên', 'video', 2, 0, 1, 'published', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', NULL, NULL, NULL, NULL, NULL),
(22, 5, 10, 'Mở đầu Python Data Analysis', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', NULL, NULL, NULL, NULL, NULL),
(23, 5, 10, 'Tư duy phân tích dữ liệu bằng bảng tính', 'article', 2, 0, 1, 'published', NULL, '<h3>Nhìn dữ liệu như một câu chuyện</h3><p>Trước khi viết code, hãy xác định câu hỏi kinh doanh và chỉ số cần theo dõi để tránh phân tích lan man.</p>', NULL, NULL, NULL, NULL),
(24, 5, 11, 'Bộ dữ liệu mẫu bán lẻ', 'document', 1, 0, 1, 'published', NULL, NULL, 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', NULL, NULL, NULL),
(25, 5, 11, 'Làm sạch dữ liệu với Pandas', 'video', 2, 0, 1, 'published', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', NULL, NULL, NULL, NULL, NULL),
(26, 6, 12, 'Cầm đàn đúng cách cho người mới', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', NULL, NULL, NULL, NULL, NULL),
(27, 6, 12, 'Nhịp điệu cơ bản với 4 hợp âm', 'video', 2, 0, 1, 'published', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', NULL, NULL, NULL, NULL, NULL),
(28, 6, 12, 'Lịch luyện tập 14 ngày', 'article', 3, 0, 1, 'published', NULL, '<h3>Lịch luyện tập ngắn</h3><p>Mỗi ngày dành 15 phút cho tay phải, 10 phút chuyển hợp âm và 5 phút chơi theo nhịp metronome.</p>', NULL, NULL, NULL, NULL),
(29, 7, 13, 'Tổng quan Machine Learning Fundamentals', 'video', 1, 1, 1, 'published', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', NULL, NULL, NULL, NULL, NULL),
(30, 7, 13, 'Sổ tay thuật ngữ AI và ML', 'document', 2, 0, 1, 'published', NULL, NULL, 'https://www.orimi.com/pdf-test.pdf', NULL, NULL, NULL),
(31, 7, 14, 'Chọn metric đánh giá mô hình', 'article', 1, 0, 1, 'published', NULL, '<h3>Metric phù hợp phụ thuộc mục tiêu</h3><p>Với bài toán mất cân bằng dữ liệu, accuracy không đủ. Hãy xem thêm precision, recall và F1.</p>', NULL, NULL, NULL, NULL),
(32, 7, 14, 'Demo huấn luyện mô hình đầu tiên', 'video', 2, 0, 1, 'published', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', NULL, NULL, NULL, NULL, NULL),
(33, 8, 15, 'Kiến trúc Vue dashboard cho team nội bộ', 'video', 1, 0, 1, 'published', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', NULL, NULL, NULL, NULL, NULL),
(34, 8, 15, 'Checklist bàn giao dashboard', 'article', 2, 0, 1, 'published', NULL, '<h3>Checklist bàn giao</h3><p>Kiểm tra role quyền truy cập, file cấu hình môi trường, tài liệu component và luồng deploy trước khi archive khóa học.</p>', NULL, NULL, NULL, NULL);

INSERT INTO `lesson` (`lesson_id`, `lesson_name`, `lesson_desc`, `lesson_link`, `course_id`, `course_name`) VALUES
(1, 'Chào mừng và tổng quan Figma', 'Giới thiệu lộ trình học và cách dùng Figma hiệu quả cho người mới.', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', 1, 'Thiết kế UI Figma từ Zero đến Prototype'),
(2, 'Vẽ khung wireframe trên Figma', 'Demo dựng wireframe nhanh cho landing page bán khóa học.', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 1, 'Thiết kế UI Figma từ Zero đến Prototype'),
(3, 'Dựng prototype cho luồng mua hàng', 'Mô phỏng tương tác từ trang khóa học tới checkout.', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 1, 'Thiết kế UI Figma từ Zero đến Prototype'),
(4, 'Định hướng React Live Bootcamp', 'Video mở đầu giúp học viên biết cách theo kịp các buổi live.', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', 2, 'React Frontend Live Bootcamp'),
(5, 'Thiết lập Vite và React trong 15 phút', 'Chuẩn bị môi trường trước khi vào workshop live.', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 2, 'React Frontend Live Bootcamp'),
(6, 'Mở đầu khóa Excel văn phòng', 'Video draft giới thiệu outline khóa học Excel.', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 3, 'Excel văn phòng cho người đi làm'),
(7, 'Giới thiệu dự án Laravel team', 'Overview về quy trình làm việc nhóm và phạm vi dự án.', 'https://www.youtube.com/watch?v=9No-FiEInLA', 4, 'Laravel Team Project Workflow'),
(8, 'Demo tạo API CRUD đầu tiên', 'Video demo route, controller và response JSON cơ bản.', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 4, 'Laravel Team Project Workflow'),
(9, 'Mở đầu Python Data Analysis', 'Giới thiệu khóa học, dữ liệu mẫu và kỳ vọng đầu ra.', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 5, 'Python Data Analysis cho người mới'),
(10, 'Làm sạch dữ liệu với Pandas', 'Thực hành drop null, normalize cột và kiểm tra dữ liệu bất thường.', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 5, 'Python Data Analysis cho người mới'),
(11, 'Cầm đàn đúng cách cho người mới', 'Thiết lập tư thế tay trái, tay phải và nhịp đều.', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', 6, 'Guitar cơ bản trong 14 ngày'),
(12, 'Nhịp điệu cơ bản với 4 hợp âm', 'Hướng dẫn tập chuyển hợp âm chậm với metronome.', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 6, 'Guitar cơ bản trong 14 ngày'),
(13, 'Tổng quan Machine Learning Fundamentals', 'Giải thích lộ trình học AI/ML cho người mới bắt đầu.', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 7, 'Machine Learning Fundamentals'),
(14, 'Demo huấn luyện mô hình đầu tiên', 'Dựng pipeline mẫu từ chuẩn bị dữ liệu đến đánh giá mô hình.', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 7, 'Machine Learning Fundamentals'),
(15, 'Kiến trúc Vue dashboard cho team nội bộ', 'Video tham khảo về component reuse trong dashboard nội bộ.', 'https://www.youtube.com/watch?v=ysz5S6PUM-U', 8, 'Vue Dashboard cho team nội bộ');

INSERT INTO `cart` (`cart_id`, `stu_email`, `course_id`, `added_date`, `is_deleted`) VALUES
(1, 'lan.cart@example.com', 1, '2026-03-23 20:15:00', 0),
(2, 'lan.cart@example.com', 2, '2026-03-23 20:20:00', 0);

INSERT INTO `order_master` (`order_id`, `student_id`, `order_code`, `order_total`, `order_status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 1, 'OM-20260301-0001', 349000, 'paid', 0, '2026-03-01 09:15:00', '2026-03-01 09:40:00'),
(2, 2, 'OM-20260220-0002', 899000, 'paid', 0, '2026-02-20 20:00:00', '2026-02-20 20:30:00'),
(3, 5, 'OM-20260323-0003', 559000, 'awaiting_verification', 0, '2026-03-23 10:10:00', '2026-03-23 10:25:00'),
(4, 6, 'OM-20260322-0004', 749000, 'failed', 0, '2026-03-22 11:00:00', '2026-03-22 15:00:00'),
(5, 6, 'OM-20260320-0005', 289000, 'cancelled', 0, '2026-03-20 16:10:00', '2026-03-20 16:45:00'),
(6, 3, 'OM-20260318-0006', 1248000, 'pending', 0, '2026-03-18 21:00:00', '2026-03-18 21:05:00'),
(7, 8, 'OM-20260115-0007', 848000, 'paid', 0, '2026-01-15 08:15:00', '2026-01-15 08:40:00'),
(8, 7, 'OM-20260305-0008', 899000, 'paid', 0, '2026-03-05 19:05:00', '2026-03-05 19:25:00');

INSERT INTO `order_item` (`order_item_id`, `order_id`, `course_id`, `unit_price`, `item_status`) VALUES
(1, 1, 1, 349000, 'active'),
(2, 2, 2, 899000, 'active'),
(3, 3, 5, 559000, 'verification_pending'),
(4, 4, 7, 749000, 'failed'),
(5, 5, 6, 289000, 'cancelled'),
(6, 6, 1, 349000, 'pending'),
(7, 6, 2, 899000, 'pending'),
(8, 7, 5, 559000, 'active'),
(9, 7, 6, 289000, 'active'),
(10, 8, 2, 899000, 'active');

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `payment_reference`, `payment_proof_url`, `payment_status`, `verified_by_admin_id`, `verified_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'bank_transfer', 'MB-20260301-0001', 'image/courseimg/QRTRAN.png', 'verified', 1, '2026-03-01 09:35:00', 'Đã xác minh chuyển khoản thủ công cho khóa Figma.', '2026-03-01 09:20:00', '2026-03-01 09:35:00'),
(2, 2, 'qr_transfer', 'QR-20260220-0002', 'image/courseimg/QRTRAN.png', 'verified', 1, '2026-02-20 20:28:00', 'Đơn React Bootcamp đã được admin xác thực đầy đủ.', '2026-02-20 20:05:00', '2026-02-20 20:28:00'),
(3, 3, 'bank_transfer', 'MB-20260323-0099', 'image/courseimg/QRTRAN.png', 'submitted', NULL, NULL, 'Học viên đã nộp minh chứng, đang chờ admin đối soát.', '2026-03-23 10:15:00', '2026-03-23 10:20:00'),
(4, 4, 'qr_transfer', 'QR-20260322-0044', 'image/courseimg/QRTRAN.png', 'rejected', 2, '2026-03-22 14:50:00', 'Mã tham chiếu không khớp và minh chứng thanh toán không hợp lệ.', '2026-03-22 11:05:00', '2026-03-22 14:50:00'),
(5, 5, 'bank_transfer', 'CANCEL-20260320-0005', NULL, 'pending', NULL, NULL, 'Đơn đã bị hủy trước khi học viên gửi minh chứng thanh toán.', '2026-03-20 16:12:00', '2026-03-20 16:20:00'),
(6, 6, 'qr_transfer', 'PENDING-20260318-0006', NULL, 'pending', NULL, NULL, 'Giỏ hàng đã tạo order chờ nhưng chưa gửi minh chứng.', '2026-03-18 21:01:00', '2026-03-18 21:03:00'),
(7, 7, 'bank_transfer', 'MB-20260115-0007', 'image/courseimg/QRTRAN.png', 'verified', 1, '2026-01-15 08:35:00', 'Đơn nhiều khóa học đã được xác minh để phục vụ demo học viên đa enrollment.', '2026-01-15 08:20:00', '2026-01-15 08:35:00'),
(8, 8, 'momo', 'MOMO-20260305-0008', 'image/courseimg/QRTRAN.png', 'verified', 2, '2026-03-05 19:20:00', 'Thanh toán ví điện tử thành công cho học viên theo lớp live.', '2026-03-05 19:10:00', '2026-03-05 19:20:00');

INSERT INTO `courseorder` (`co_id`, `order_id`, `stu_email`, `course_id`, `status`, `respmsg`, `amount`, `order_date`, `is_deleted`, `created_at`) VALUES
(1, 'OM-20260301-0001', 'cap@example.com', 1, 'TXN_SUCCESS', 'QR: demo-figma | Txn Success', 349000, '2026-03-01', 0, '2026-03-01 09:40:00'),
(2, 'OM-20260220-0002', 'hoan.thanh@example.com', 2, 'TXN_SUCCESS', 'QR: react-bootcamp | Txn Success', 899000, '2026-02-20', 0, '2026-02-20 20:30:00'),
(3, 'OM-20260115-0007-1', 'thanh.multi@example.com', 5, 'TXN_SUCCESS', 'QR: python-data | Txn Success', 559000, '2026-01-15', 0, '2026-01-15 08:40:00'),
(4, 'OM-20260115-0007-2', 'thanh.multi@example.com', 6, 'TXN_SUCCESS', 'QR: guitar-basic | Txn Success', 289000, '2026-01-15', 0, '2026-01-15 08:40:00'),
(5, 'OM-20260305-0008', 'thao.live@example.com', 2, 'TXN_SUCCESS', 'QR: react-live | Txn Success', 899000, '2026-03-05', 0, '2026-03-05 19:25:00');

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `course_id`, `order_id`, `enrollment_status`, `granted_at`, `completed_at`, `progress_percent`) VALUES
(1, 1, 1, 1, 'active', '2026-03-01 09:40:00', NULL, 42.50),
(2, 2, 2, 2, 'active', '2026-02-20 20:30:00', '2026-03-15 09:20:00', 100.00),
(3, 8, 5, 7, 'active', '2026-01-15 08:40:00', NULL, 82.00),
(4, 8, 6, 7, 'active', '2026-01-15 08:40:00', '2026-02-01 20:00:00', 100.00),
(5, 7, 2, 8, 'active', '2026-03-05 19:25:00', NULL, 63.00);

INSERT INTO `learning_progress` (`progress_id`, `student_id`, `course_id`, `item_id`, `progress_status`, `last_accessed_at`, `completed_at`, `watch_percent`, `last_position_seconds`) VALUES
(1, 1, 1, 1, 'completed', '2026-03-01 10:00:00', '2026-03-01 10:00:00', 100.00, 320),
(2, 1, 1, 2, 'completed', '2026-03-01 10:25:00', '2026-03-01 10:25:00', NULL, NULL),
(3, 1, 1, 3, 'in_progress', '2026-03-02 19:20:00', NULL, 55.00, 410),
(4, 1, 1, 4, 'not_started', NULL, NULL, NULL, NULL),
(5, 1, 1, 5, 'not_started', NULL, NULL, NULL, NULL),
(6, 1, 1, 6, 'not_started', NULL, NULL, NULL, NULL),
(7, 2, 2, 7, 'completed', '2026-02-21 09:00:00', '2026-02-21 09:00:00', 100.00, 280),
(8, 2, 2, 8, 'completed', '2026-02-21 09:25:00', '2026-02-21 09:25:00', NULL, NULL),
(9, 2, 2, 9, 'completed', '2026-02-22 09:30:00', '2026-02-22 09:30:00', 100.00, 600),
(10, 2, 2, 10, 'completed', '2026-03-05 21:10:00', '2026-03-05 21:10:00', NULL, NULL),
(11, 2, 2, 11, 'completed', '2026-03-12 21:02:00', '2026-03-12 21:02:00', NULL, NULL),
(12, 2, 2, 12, 'completed', '2026-03-13 09:30:00', '2026-03-13 09:30:00', 100.00, 1200),
(13, 2, 2, 13, 'completed', '2026-02-28 21:02:00', '2026-02-28 21:02:00', NULL, NULL),
(14, 2, 2, 14, 'completed', '2026-03-01 10:00:00', '2026-03-01 10:00:00', 100.00, 1180),
(15, 2, 2, 15, 'passed', '2026-03-15 09:20:00', '2026-03-15 09:20:00', NULL, NULL),
(16, 8, 5, 22, 'completed', '2026-01-16 08:45:00', '2026-01-16 08:45:00', 100.00, 240),
(17, 8, 5, 23, 'completed', '2026-01-16 09:10:00', '2026-01-16 09:10:00', NULL, NULL),
(18, 8, 5, 24, 'completed', '2026-01-17 08:00:00', '2026-01-17 08:00:00', NULL, NULL),
(19, 8, 5, 25, 'in_progress', '2026-01-18 20:30:00', NULL, 62.00, 510),
(20, 8, 6, 26, 'completed', '2026-01-20 20:10:00', '2026-01-20 20:10:00', 100.00, 260),
(21, 8, 6, 27, 'completed', '2026-01-22 20:15:00', '2026-01-22 20:15:00', 100.00, 420),
(22, 8, 6, 28, 'completed', '2026-01-23 20:00:00', '2026-01-23 20:00:00', NULL, NULL),
(23, 7, 2, 7, 'completed', '2026-03-05 20:00:00', '2026-03-05 20:00:00', 100.00, 280),
(24, 7, 2, 8, 'completed', '2026-03-06 20:05:00', '2026-03-06 20:05:00', NULL, NULL),
(25, 7, 2, 9, 'completed', '2026-03-06 21:00:00', '2026-03-06 21:00:00', 100.00, 600),
(26, 7, 2, 10, 'completed', '2026-03-05 21:05:00', '2026-03-05 21:05:00', NULL, NULL),
(27, 7, 2, 11, 'not_started', NULL, NULL, NULL, NULL),
(28, 7, 2, 15, 'not_started', NULL, NULL, NULL, NULL);

INSERT INTO `quiz_attempt` (`attempt_id`, `quiz_id`, `student_id`, `course_id`, `item_id`, `attempt_number`, `score`, `max_score`, `passed`, `started_at`, `submitted_at`, `is_deleted`) VALUES
(1, 1, 1, 1, 6, 1, 55.00, 100.00, 0, '2026-03-02 20:00:00', '2026-03-02 20:12:00', 0),
(2, 2, 2, 2, 15, 1, 92.00, 100.00, 1, '2026-03-15 09:00:00', '2026-03-15 09:18:00', 0),
(3, 2, 7, 2, 15, 1, 68.00, 100.00, 0, '2026-03-18 20:00:00', '2026-03-18 20:16:00', 0);

INSERT INTO `session_attendance` (`attendance_id`, `live_session_id`, `student_id`, `joined_at`, `left_at`, `attendance_status`) VALUES
(1, 2, 2, '2026-03-12 19:31:00', '2026-03-12 21:00:00', 'attended'),
(2, 2, 7, '2026-03-12 19:40:00', '2026-03-12 20:55:00', 'joined_late'),
(3, 3, 2, '2026-02-28 19:32:00', '2026-02-28 20:58:00', 'attended');

INSERT INTO `feedback` (`f_id`, `f_content`, `stu_id`, `is_deleted`, `created_at`) VALUES
(1, 'Khóa Figma có lộ trình rõ ràng, xem xong có thể tự dựng prototype cho landing page bán khóa học của nhóm mình.', 1, 0, '2026-03-05 10:00:00'),
(2, 'React Bootcamp có live session và replay nên mình dễ bù bài khi bận ca làm. Nội dung thực hành rất sát dự án.', 2, 0, '2026-03-16 09:15:00'),
(3, 'Lịch live rõ ràng, có checklist trước buổi học nên mình theo kịp dù mới quay lại học frontend.', 7, 0, '2026-03-10 20:00:00'),
(4, 'Khóa Python có dữ liệu mẫu dễ hiểu, đủ để demo từ bước đọc dữ liệu đến làm sạch.', 8, 0, '2026-01-20 08:30:00'),
(5, 'Phần guitar chia bài ngắn nên rất hợp với người đi làm, mỗi ngày 15 phút vẫn giữ được nhịp luyện.', 8, 0, '2026-02-02 19:45:00'),
(6, 'Mình thích cách hệ thống giữ lại replay sau workshop, cảm giác học linh hoạt hơn nhiều.', 10, 0, '2026-03-21 11:00:00');

INSERT INTO `contact_message` (`c_id`, `name`, `subject`, `email`, `message`, `created_at`, `is_deleted`) VALUES
(1, 'Ngọc Anh', 'Hỏi lịch live khóa React', 'ngocanh@example.com', 'Cho mình hỏi lịch workshop live của khóa React Frontend Live Bootcamp có học vào tối thứ bảy hay không?', '2026-03-23 08:45:00', 0),
(2, 'Phòng đào tạo nội bộ', 'Đề nghị demo cho doanh nghiệp', 'training@example.com', 'Bên mình muốn xin lịch demo nhanh để đánh giá khả năng dùng hệ thống cho các khóa nội bộ có replay và kiểm tra thủ công thanh toán.', '2026-03-20 14:10:00', 0),
(3, 'Khả Hân', 'Chứng nhận hoàn thành', 'khanh@example.com', 'Nếu học xong và pass quiz cuối khóa thì hệ thống có thể hiện tiến độ hoàn thành để đối chiếu nội bộ không?', '2026-03-19 19:20:00', 0),
(4, 'Hoàng Vũ', 'Lỗi thanh toán minh chứng', 'vu@example.com', 'Mình đã gửi ảnh chuyển khoản nhưng chưa thấy xác nhận, mong admin kiểm tra giúp đơn hàng chờ xác minh.', '2026-03-24 07:30:00', 0);

COMMIT;
