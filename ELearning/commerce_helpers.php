<?php

function commerce_set_flash(string $type, string $text): void
{
    $_SESSION['commerce_flash'] = [
        'type' => $type,
        'text' => $text,
    ];
}

function commerce_pull_flash(): ?array
{
    if (!isset($_SESSION['commerce_flash']) || !is_array($_SESSION['commerce_flash'])) {
        return null;
    }

    $flash = $_SESSION['commerce_flash'];
    unset($_SESSION['commerce_flash']);

    return $flash;
}

function commerce_get_student_id(mysqli $conn, string $stuEmail): ?int
{
    $stmt = $conn->prepare('SELECT stu_id FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $stuEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? (int) $row['stu_id'] : null;
}

function commerce_fetch_course_states(mysqli $conn, ?int $studentId, array $courseIds): array
{
    $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), static function ($value) {
        return $value > 0;
    })));

    $states = [];
    foreach ($courseIds as $courseId) {
        $states[$courseId] = [
            'is_enrolled' => false,
            'has_open_order' => false,
            'open_order_code' => null,
            'open_order_status' => null,
            'payment_status' => null,
        ];
    }

    if ($studentId === null || $studentId <= 0 || empty($courseIds)) {
        return $states;
    }

    $enrollmentStmt = $conn->prepare(
        "SELECT 1 FROM enrollment WHERE student_id = ? AND course_id = ? AND enrollment_status = 'active' LIMIT 1"
    );
    $orderStmt = $conn->prepare(
        "SELECT om.order_code, om.order_status, p.payment_status "
        . 'FROM order_master om '
        . 'INNER JOIN order_item oi ON oi.order_id = om.order_id '
        . 'LEFT JOIN payment p ON p.order_id = om.order_id '
        . 'WHERE om.student_id = ? AND oi.course_id = ? AND om.is_deleted = 0 '
        . "AND om.order_status IN ('pending', 'awaiting_verification', 'failed') "
        . 'ORDER BY om.created_at DESC, om.order_id DESC LIMIT 1'
    );

    foreach ($courseIds as $courseId) {
        if ($enrollmentStmt) {
            $enrollmentStmt->bind_param('ii', $studentId, $courseId);
            $enrollmentStmt->execute();
            $enrollmentStmt->store_result();
            $states[$courseId]['is_enrolled'] = $enrollmentStmt->num_rows > 0;
            $enrollmentStmt->free_result();
        }

        if ($states[$courseId]['is_enrolled']) {
            continue;
        }

        if ($orderStmt) {
            $orderStmt->bind_param('ii', $studentId, $courseId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            $orderRow = $orderResult ? $orderResult->fetch_assoc() : null;
            if ($orderRow) {
                $states[$courseId]['has_open_order'] = true;
                $states[$courseId]['open_order_code'] = (string) ($orderRow['order_code'] ?? '');
                $states[$courseId]['open_order_status'] = (string) ($orderRow['order_status'] ?? '');
                $states[$courseId]['payment_status'] = (string) ($orderRow['payment_status'] ?? '');
            }
        }
    }

    if ($enrollmentStmt) {
        $enrollmentStmt->close();
    }
    if ($orderStmt) {
        $orderStmt->close();
    }

    return $states;
}

function commerce_cleanup_cart(mysqli $conn, string $stuEmail): void
{
    $stmt = $conn->prepare(
        'UPDATE cart c '
        . 'LEFT JOIN course co ON co.course_id = c.course_id '
        . 'LEFT JOIN student s ON s.stu_email = c.stu_email AND s.is_deleted = 0 '
        . "LEFT JOIN enrollment e ON e.student_id = s.stu_id AND e.course_id = c.course_id AND e.enrollment_status = 'active' "
        . 'SET c.is_deleted = 1 '
        . 'WHERE c.stu_email = ? AND c.is_deleted = 0 AND ('
        . 'co.course_id IS NULL OR co.is_deleted = 1 OR co.course_status <> ? OR e.enrollment_id IS NOT NULL)'
    );

    if (!$stmt) {
        return;
    }

    $publishedStatus = 'published';
    $stmt->bind_param('ss', $stuEmail, $publishedStatus);
    $stmt->execute();
    $stmt->close();
}

function commerce_get_cart_count(mysqli $conn, string $stuEmail): int
{
    commerce_cleanup_cart($conn, $stuEmail);

    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS cnt '
        . 'FROM cart c '
        . 'INNER JOIN course co ON co.course_id = c.course_id '
        . 'LEFT JOIN student s ON s.stu_email = c.stu_email AND s.is_deleted = 0 '
        . "LEFT JOIN enrollment e ON e.student_id = s.stu_id AND e.course_id = c.course_id AND e.enrollment_status = 'active' "
        . 'WHERE c.stu_email = ? AND c.is_deleted = 0 AND co.is_deleted = 0 AND co.course_status = ? AND e.enrollment_id IS NULL'
    );
    if (!$stmt) {
        return 0;
    }

    $publishedStatus = 'published';
    $stmt->bind_param('ss', $stuEmail, $publishedStatus);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['cnt'] ?? 0);
}

function commerce_get_order_status_meta(string $status): array
{
    $map = [
        'pending' => ['label' => 'Chờ nộp thanh toán', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
        'awaiting_verification' => ['label' => 'Chờ xác minh', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'failed' => ['label' => 'Thanh toán thất bại', 'class' => 'bg-red-50 text-red-700 border-red-200'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-500 border-slate-200'],
        'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
    ];

    return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
}

function commerce_get_payment_status_meta(string $status): array
{
    $map = [
        'pending' => ['label' => 'Chưa gửi minh chứng', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
        'submitted' => ['label' => 'Đã gửi minh chứng', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
        'verified' => ['label' => 'Đã xác minh', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'rejected' => ['label' => 'Bị từ chối', 'class' => 'bg-red-50 text-red-700 border-red-200'],
    ];

    return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
}

function commerce_can_submit_payment(string $orderStatus, string $paymentStatus): bool
{
    if ($orderStatus === 'pending') {
        return true;
    }

    return $orderStatus === 'failed' && $paymentStatus === 'rejected';
}
