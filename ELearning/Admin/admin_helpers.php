<?php

require_once(__DIR__ . '/../commerce_helpers.php');

if (!function_exists('admin_set_flash')) {
    function admin_set_flash(string $type, string $text): void
    {
        $_SESSION['admin_flash'] = [
            'type' => $type,
            'text' => $text,
        ];
    }
}

if (!function_exists('admin_pull_flash')) {
    function admin_pull_flash(): ?array
    {
        if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
            return null;
        }

        $flash = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);

        $type = isset($flash['type']) ? (string) $flash['type'] : 'info';
        $text = isset($flash['text']) ? (string) $flash['text'] : '';

        if ($text === '') {
            return null;
        }

        return [
            'type' => $type,
            'text' => $text,
        ];
    }
}

if (!function_exists('admin_is_valid_url')) {
    function admin_is_valid_url(string $url): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}

if (!function_exists('admin_course_status_meta')) {
    function admin_course_status_meta(string $status): array
    {
        $map = [
            'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-100 text-slate-700'],
            'pending_review' => ['label' => 'Chờ duyệt', 'class' => 'bg-amber-100 text-amber-800'],
            'published' => ['label' => 'Đã xuất bản', 'class' => 'bg-emerald-100 text-emerald-700'],
            'archived' => ['label' => 'Lưu trữ', 'class' => 'bg-zinc-200 text-zinc-700'],
        ];

        return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('admin_instructor_status_meta')) {
    function admin_instructor_status_meta(string $status): array
    {
        $map = [
            'active' => ['label' => 'Đang hoạt động', 'class' => 'bg-emerald-100 text-emerald-700'],
            'blocked' => ['label' => 'Đã khóa', 'class' => 'bg-red-100 text-red-700'],
        ];

        return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('admin_live_status_meta')) {
    function admin_live_status_meta(string $status): array
    {
        $map = [
            'scheduled' => ['label' => 'Sắp diễn ra', 'class' => 'bg-blue-100 text-blue-700'],
            'live' => ['label' => 'Đang live', 'class' => 'bg-emerald-100 text-emerald-700'],
            'ended' => ['label' => 'Đã kết thúc', 'class' => 'bg-slate-100 text-slate-700'],
            'replay_available' => ['label' => 'Có replay', 'class' => 'bg-violet-100 text-violet-700'],
            'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-rose-100 text-rose-700'],
        ];

        return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('admin_resolve_live_status')) {
    function admin_resolve_live_status(array $sessionRow): string
    {
        $storedStatus = (string) ($sessionRow['session_status'] ?? 'scheduled');
        if ($storedStatus === 'cancelled') {
            return 'cancelled';
        }

        $hasReplay = trim((string) ($sessionRow['recording_url'] ?? '')) !== '';
        if ($hasReplay) {
            return 'replay_available';
        }

        $startAt = strtotime((string) ($sessionRow['start_at'] ?? ''));
        $endAt = strtotime((string) ($sessionRow['end_at'] ?? ''));
        if ($startAt === false || $endAt === false || $endAt <= $startAt) {
            return $storedStatus;
        }

        $now = time();
        if ($now < $startAt) {
            return 'scheduled';
        }
        if ($now <= $endAt) {
            return 'live';
        }
        return 'ended';
    }
}

if (!function_exists('admin_validate_course_readiness')) {
    function admin_validate_course_readiness(mysqli $conn, int $courseId): array
    {
        $errors = [];
        $sectionCount = 0;
        $meaningfulItemCount = 0;
        $invalidLiveCount = 0;

        $courseStmt = $conn->prepare(
            'SELECT c.course_id, c.instructor_id, i.ins_id AS valid_instructor_id '
            . 'FROM course c '
            . 'LEFT JOIN instructor i ON i.ins_id = c.instructor_id AND i.is_deleted = 0 '
            . 'WHERE c.course_id = ? AND c.is_deleted = 0 LIMIT 1'
        );
        if ($courseStmt) {
            $courseStmt->bind_param('i', $courseId);
            $courseStmt->execute();
            $courseResult = $courseStmt->get_result();
            $courseRow = $courseResult ? $courseResult->fetch_assoc() : null;
            $courseStmt->close();

            if (!$courseRow) {
                $errors[] = 'Không tìm thấy khóa học để duyệt.';
            } elseif ((int) ($courseRow['instructor_id'] ?? 0) <= 0 || (int) ($courseRow['valid_instructor_id'] ?? 0) <= 0) {
                $errors[] = 'Khóa học chưa có instructor hợp lệ.';
            }
        } else {
            $errors[] = 'Không thể kiểm tra thông tin instructor.';
        }

        $sectionStmt = $conn->prepare('SELECT COUNT(*) AS c FROM course_section WHERE course_id = ? AND is_deleted = 0');
        if ($sectionStmt) {
            $sectionStmt->bind_param('i', $courseId);
            $sectionStmt->execute();
            $sectionResult = $sectionStmt->get_result();
            $sectionRow = $sectionResult ? $sectionResult->fetch_assoc() : null;
            $sectionCount = (int) ($sectionRow['c'] ?? 0);
            $sectionStmt->close();
        }

        if ($sectionCount <= 0) {
            $errors[] = 'Khóa học phải có ít nhất một section.';
        }

        $itemSql =
            'SELECT COUNT(*) AS c '
            . 'FROM learning_item li '
            . 'INNER JOIN course_section cs ON cs.section_id = li.section_id '
            . 'WHERE li.course_id = ? AND li.is_deleted = 0 AND cs.is_deleted = 0 '
            . 'AND ( '
            . "(li.item_type = 'video' AND TRIM(COALESCE(li.video_url, '')) <> '') OR "
            . "(li.item_type = 'article' AND TRIM(COALESCE(li.article_content, '')) <> '') OR "
            . "(li.item_type = 'document' AND TRIM(COALESCE(li.document_url, '')) <> '') OR "
            . "(li.item_type = 'quiz' AND li.quiz_id IS NOT NULL) OR "
            . "(li.item_type = 'live_session' AND li.live_session_id IS NOT NULL) OR "
            . "(li.item_type = 'replay' AND li.replay_id IS NOT NULL) "
            . ')';

        $itemStmt = $conn->prepare($itemSql);
        if ($itemStmt) {
            $itemStmt->bind_param('i', $courseId);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            $itemRow = $itemResult ? $itemResult->fetch_assoc() : null;
            $meaningfulItemCount = (int) ($itemRow['c'] ?? 0);
            $itemStmt->close();
        }

        if ($meaningfulItemCount <= 0) {
            $errors[] = 'Khóa học phải có ít nhất một learning item hợp lệ.';
        }

        $liveStmt = $conn->prepare(
            'SELECT COUNT(*) AS c FROM live_session ls '
            . 'WHERE ls.course_id = ? AND ls.is_deleted = 0 AND ('
            . 'ls.end_at <= ls.start_at '
            . "OR TRIM(COALESCE(ls.join_url, '')) = '' "
            . "OR (LOWER(TRIM(COALESCE(ls.join_url, ''))) NOT LIKE 'http://%' AND LOWER(TRIM(COALESCE(ls.join_url, ''))) NOT LIKE 'https://%')"
            . ')'
        );
        if ($liveStmt) {
            $liveStmt->bind_param('i', $courseId);
            $liveStmt->execute();
            $liveResult = $liveStmt->get_result();
            $liveRow = $liveResult ? $liveResult->fetch_assoc() : null;
            $invalidLiveCount = (int) ($liveRow['c'] ?? 0);
            $liveStmt->close();
        }

        if ($invalidLiveCount > 0) {
            $errors[] = 'Khóa học có live session chưa hợp lệ (thời gian hoặc join URL).';
        }

        return [
            'ok' => count($errors) === 0,
            'errors' => $errors,
            'section_count' => $sectionCount,
            'meaningful_item_count' => $meaningfulItemCount,
            'invalid_live_count' => $invalidLiveCount,
        ];
    }
}

if (!function_exists('admin_find_current_id')) {
    function admin_find_current_id(mysqli $conn): ?int
    {
        $adminEmail = (string) ($_SESSION['adminLogEmail'] ?? '');
        if ($adminEmail === '') {
            return null;
        }

        $adminStmt = $conn->prepare('SELECT admin_id FROM admin WHERE admin_email = ? LIMIT 1');
        if (!$adminStmt) {
            return null;
        }

        $adminStmt->bind_param('s', $adminEmail);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
        $adminStmt->close();

        return $adminRow ? (int) ($adminRow['admin_id'] ?? 0) : null;
    }
}

if (!function_exists('admin_process_payment_decision')) {
    function admin_process_payment_decision(mysqli $conn, int $orderId, string $paymentAction, ?int $adminId, string $adminNote): array
    {
        if ($orderId <= 0 || !in_array($paymentAction, ['verify', 'reject'], true)) {
            return ['ok' => false, 'message' => 'Yêu cầu xác minh thanh toán không hợp lệ.'];
        }

        $conn->begin_transaction();

        try {
            $lockStmt = $conn->prepare(
                'SELECT om.order_id, om.order_code, om.student_id, om.order_status, om.created_at, '
                . 's.stu_email, p.payment_id, p.payment_status, p.notes '
                . 'FROM order_master om '
                . 'INNER JOIN student s ON s.stu_id = om.student_id '
                . 'INNER JOIN payment p ON p.order_id = om.order_id '
                . 'WHERE om.order_id = ? AND om.is_deleted = 0 LIMIT 1 FOR UPDATE'
            );
            if (!$lockStmt) {
                throw new RuntimeException('Không thể tải đơn hàng để xác minh.');
            }

            $lockStmt->bind_param('i', $orderId);
            $lockStmt->execute();
            $lockedResult = $lockStmt->get_result();
            $lockedOrder = $lockedResult ? $lockedResult->fetch_assoc() : null;
            $lockStmt->close();

            if (!$lockedOrder) {
                throw new RuntimeException('Không tìm thấy đơn hàng cần xử lý.');
            }

            if ((string) $lockedOrder['order_status'] !== 'awaiting_verification' || (string) $lockedOrder['payment_status'] !== 'submitted') {
                throw new RuntimeException('Đơn hàng này không còn ở trạng thái chờ xác minh.');
            }

            $paymentId = (int) $lockedOrder['payment_id'];
            $verifiedAt = date('Y-m-d H:i:s');

            if ($paymentAction === 'verify') {
                $paymentStatus = 'verified';
                $orderStatus = 'paid';
                $paymentNote = $adminNote !== '' ? $adminNote : 'Admin đã xác minh thanh toán hợp lệ.';

                $updatePaymentStmt = $conn->prepare(
                    'UPDATE payment SET payment_status = ?, verified_by_admin_id = ?, verified_at = ?, notes = ? WHERE payment_id = ? LIMIT 1'
                );
                if (!$updatePaymentStmt) {
                    throw new RuntimeException('Không thể cập nhật payment.');
                }
                $updatePaymentStmt->bind_param('sissi', $paymentStatus, $adminId, $verifiedAt, $paymentNote, $paymentId);
                if (!$updatePaymentStmt->execute()) {
                    $updatePaymentStmt->close();
                    throw new RuntimeException('Không thể xác minh payment.');
                }
                $updatePaymentStmt->close();

                $updateOrderStmt = $conn->prepare('UPDATE order_master SET order_status = ? WHERE order_id = ? LIMIT 1');
                if (!$updateOrderStmt) {
                    throw new RuntimeException('Không thể cập nhật trạng thái order.');
                }
                $updateOrderStmt->bind_param('si', $orderStatus, $orderId);
                if (!$updateOrderStmt->execute()) {
                    $updateOrderStmt->close();
                    throw new RuntimeException('Không thể đánh dấu order đã thanh toán.');
                }
                $updateOrderStmt->close();

                $itemStmt = $conn->prepare('SELECT order_item_id, course_id, unit_price FROM order_item WHERE order_id = ? ORDER BY order_item_id ASC');
                if (!$itemStmt) {
                    throw new RuntimeException('Không thể tải order_item để cấp quyền học.');
                }
                $itemStmt->bind_param('i', $orderId);
                $itemStmt->execute();
                $itemsResult = $itemStmt->get_result();

                if (!$itemsResult || $itemsResult->num_rows <= 0) {
                    $itemStmt->close();
                    throw new RuntimeException('Đơn hàng không có sản phẩm hợp lệ để cấp quyền học.');
                }

                $insertEnrollmentStmt = $conn->prepare(
                    'INSERT INTO enrollment (student_id, course_id, order_id, enrollment_status, granted_at, progress_percent) VALUES (?, ?, ?, ?, ?, 0.00)'
                );
                $findEnrollmentStmt = $conn->prepare('SELECT enrollment_id FROM enrollment WHERE student_id = ? AND course_id = ? LIMIT 1');
                $findLegacyOrderStmt = $conn->prepare('SELECT 1 FROM courseorder WHERE order_id = ? AND course_id = ? LIMIT 1');
                $insertLegacyOrderStmt = $conn->prepare(
                    'INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                if (!$insertEnrollmentStmt || !$findEnrollmentStmt || !$findLegacyOrderStmt || !$insertLegacyOrderStmt) {
                    $itemStmt->close();
                    throw new RuntimeException('Không thể chuẩn bị câu lệnh cấp quyền học.');
                }

                $activeEnrollmentStatus = 'active';
                $legacyStatus = 'TXN_SUCCESS';
                $legacyResp = 'Verified payment';
                $grantedAt = $verifiedAt;
                $legacyOrderDate = date('Y-m-d', strtotime((string) $lockedOrder['created_at']));
                $stuEmail = (string) $lockedOrder['stu_email'];
                $lockedStudentId = (int) $lockedOrder['student_id'];
                $itemIndex = 0;

                while ($item = $itemsResult->fetch_assoc()) {
                    $itemIndex++;
                    $courseId = (int) $item['course_id'];
                    $unitPrice = (int) $item['unit_price'];

                    $findEnrollmentStmt->bind_param('ii', $lockedStudentId, $courseId);
                    $findEnrollmentStmt->execute();
                    $existingEnrollmentResult = $findEnrollmentStmt->get_result();
                    $existingEnrollment = $existingEnrollmentResult ? $existingEnrollmentResult->fetch_assoc() : null;
                    if ($existingEnrollment) {
                        throw new RuntimeException('Không thể xác minh: học viên đã có enrollment cho ít nhất một khóa học trong đơn này.');
                    }

                    $insertEnrollmentStmt->bind_param('iiiss', $lockedStudentId, $courseId, $orderId, $activeEnrollmentStatus, $grantedAt);
                    if (!$insertEnrollmentStmt->execute()) {
                        throw new RuntimeException('Không thể tạo enrollment mới.');
                    }

                    $legacyOrderCode = ((string) $lockedOrder['order_code']) . '-' . $itemIndex;
                    $findLegacyOrderStmt->bind_param('si', $legacyOrderCode, $courseId);
                    $findLegacyOrderStmt->execute();
                    $legacyResult = $findLegacyOrderStmt->get_result();
                    $hasLegacyOrder = $legacyResult && $legacyResult->num_rows > 0;

                    if (!$hasLegacyOrder) {
                        $insertLegacyOrderStmt->bind_param('ssissis', $legacyOrderCode, $stuEmail, $courseId, $legacyStatus, $legacyResp, $unitPrice, $legacyOrderDate);
                        if (!$insertLegacyOrderStmt->execute()) {
                            throw new RuntimeException('Không thể tạo bản ghi courseorder tương thích.');
                        }
                    }
                }

                $itemStmt->close();
                $insertEnrollmentStmt->close();
                $findEnrollmentStmt->close();
                $findLegacyOrderStmt->close();
                $insertLegacyOrderStmt->close();

                $conn->commit();
                return ['ok' => true, 'message' => 'Đã xác minh thanh toán và cấp quyền học thành công.'];
            }

            $paymentStatus = 'rejected';
            $orderStatus = 'failed';
            $paymentNote = $adminNote !== '' ? $adminNote : 'Minh chứng thanh toán chưa hợp lệ. Học viên cần gửi lại thông tin chính xác hơn.';

            $updatePaymentStmt = $conn->prepare(
                'UPDATE payment SET payment_status = ?, verified_by_admin_id = ?, verified_at = ?, notes = ? WHERE payment_id = ? LIMIT 1'
            );
            if (!$updatePaymentStmt) {
                throw new RuntimeException('Không thể cập nhật payment bị từ chối.');
            }
            $updatePaymentStmt->bind_param('sissi', $paymentStatus, $adminId, $verifiedAt, $paymentNote, $paymentId);
            if (!$updatePaymentStmt->execute()) {
                $updatePaymentStmt->close();
                throw new RuntimeException('Không thể lưu kết quả từ chối payment.');
            }
            $updatePaymentStmt->close();

            $updateOrderStmt = $conn->prepare('UPDATE order_master SET order_status = ? WHERE order_id = ? LIMIT 1');
            if (!$updateOrderStmt) {
                throw new RuntimeException('Không thể cập nhật trạng thái order bị từ chối.');
            }
            $updateOrderStmt->bind_param('si', $orderStatus, $orderId);
            if (!$updateOrderStmt->execute()) {
                $updateOrderStmt->close();
                throw new RuntimeException('Không thể đánh dấu order thất bại.');
            }
            $updateOrderStmt->close();

            $conn->commit();
            return ['ok' => true, 'message' => 'Đã từ chối thanh toán và chuyển đơn hàng sang trạng thái thất bại.'];
        } catch (Throwable $exception) {
            $conn->rollback();
            return [
                'ok' => false,
                'message' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Không thể xử lý thanh toán này.',
            ];
        }
    }
}
