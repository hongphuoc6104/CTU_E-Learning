<?php
include('./dbConnection.php');
require_once(__DIR__ . '/session_bootstrap.php');
require_once(__DIR__ . '/commerce_helpers.php');
require_once(__DIR__ . '/csrf.php');
secure_session_start();

if (!isset($_SESSION['stuLogEmail'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    commerce_set_flash('error', 'Phiên thao tác đã hết hạn. Vui lòng thử lại từ đầu.');
    header('Location: Student/myCart.php');
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$studentId = commerce_get_student_id($conn, $stuEmail);
if ($studentId === null) {
    commerce_set_flash('error', 'Không tìm thấy thông tin học viên để tạo đơn hàng.');
    header('Location: Student/myCart.php');
    exit;
}

$checkoutType = isset($_POST['checkout_type']) ? (string) $_POST['checkout_type'] : '';
if ($checkoutType !== 'single' && $checkoutType !== 'cart') {
    commerce_set_flash('error', 'Yêu cầu thanh toán không hợp lệ.');
    header('Location: courses.php');
    exit;
}

$courseRows = [];
$cartCourseIds = [];

if ($checkoutType === 'single') {
    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId) {
        commerce_set_flash('error', 'Khoá học không hợp lệ.');
        header('Location: courses.php');
        exit;
    }

    $courseStmt = $conn->prepare(
        "SELECT course_id, course_name, course_price FROM course WHERE course_id = ? AND is_deleted = 0 AND course_status = 'published' LIMIT 1"
    );
    if (!$courseStmt) {
        commerce_set_flash('error', 'Không thể tạo đơn hàng lúc này.');
        header('Location: coursedetails.php?course_id=' . $courseId);
        exit;
    }

    $courseStmt->bind_param('i', $courseId);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $courseRow = $courseResult ? $courseResult->fetch_assoc() : null;
    $courseStmt->close();

    if (!$courseRow) {
        commerce_set_flash('error', 'Khoá học này không còn khả dụng để thanh toán.');
        header('Location: courses.php');
        exit;
    }

    $courseState = commerce_fetch_course_states($conn, $studentId, [$courseId]);
    $courseState = $courseState[$courseId] ?? ['is_enrolled' => false, 'has_open_order' => false, 'open_order_code' => null];

    if ($courseState['is_enrolled']) {
        commerce_set_flash('success', 'Bạn đã sở hữu khoá học này rồi.');
        header('Location: Student/myCourse.php');
        exit;
    }

    if ($courseState['has_open_order'] && !empty($courseState['open_order_code'])) {
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode((string) $courseState['open_order_code']));
        exit;
    }

    $courseRows[] = $courseRow;
} else {
    commerce_cleanup_cart($conn, $stuEmail);

    $cartStmt = $conn->prepare(
        'SELECT c.cart_id, co.course_id, co.course_name, co.course_price '
        . 'FROM cart c '
        . 'INNER JOIN course co ON co.course_id = c.course_id '
        . 'LEFT JOIN enrollment e ON e.student_id = ? AND e.course_id = co.course_id AND e.enrollment_status = ? '
        . 'WHERE c.stu_email = ? AND c.is_deleted = 0 AND co.is_deleted = 0 AND co.course_status = ? AND e.enrollment_id IS NULL '
        . 'ORDER BY c.cart_id ASC'
    );

    if (!$cartStmt) {
        commerce_set_flash('error', 'Không thể tải giỏ hàng để tạo đơn.');
        header('Location: Student/myCart.php');
        exit;
    }

    $activeStatus = 'active';
    $publishedStatus = 'published';
    $cartStmt->bind_param('isss', $studentId, $activeStatus, $stuEmail, $publishedStatus);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    while ($row = $cartResult->fetch_assoc()) {
        $courseRows[] = $row;
        $cartCourseIds[] = (int) $row['course_id'];
    }
    $cartStmt->close();

    if (empty($courseRows)) {
        commerce_set_flash('error', 'Giỏ hàng hiện không có khóa học hợp lệ để tạo đơn.');
        header('Location: Student/myCart.php');
        exit;
    }

    $courseStates = commerce_fetch_course_states($conn, $studentId, $cartCourseIds);
    foreach ($courseRows as $courseRow) {
        $courseId = (int) $courseRow['course_id'];
        $courseState = $courseStates[$courseId] ?? ['is_enrolled' => false, 'has_open_order' => false, 'open_order_code' => null];
        if ($courseState['is_enrolled']) {
            commerce_set_flash('error', 'Một hoặc nhiều khóa học trong giỏ đã được cấp quyền học trước đó. Giỏ hàng đã được làm sạch, vui lòng thử lại.');
            header('Location: Student/myCart.php');
            exit;
        }
        if ($courseState['has_open_order']) {
            commerce_set_flash('error', 'Một hoặc nhiều khóa học trong giỏ đang có đơn hàng chưa hoàn tất. Hãy tiếp tục từ mục Đơn hàng của tôi.');
            header('Location: Student/myCart.php');
            exit;
        }
    }
}

$orderCode = 'ORDM' . date('YmdHis') . random_int(100, 999);
$orderTotal = 0;
foreach ($courseRows as $courseRow) {
    $orderTotal += (int) ($courseRow['course_price'] ?? 0);
}

$conn->begin_transaction();

try {
    $orderStatus = 'pending';
    $insertOrderStmt = $conn->prepare(
        'INSERT INTO order_master (student_id, order_code, order_total, order_status, is_deleted) VALUES (?, ?, ?, ?, 0)'
    );
    if (!$insertOrderStmt) {
        throw new RuntimeException('Không thể tạo order_master.');
    }

    $insertOrderStmt->bind_param('isis', $studentId, $orderCode, $orderTotal, $orderStatus);
    if (!$insertOrderStmt->execute()) {
        $insertOrderStmt->close();
        throw new RuntimeException('Không thể lưu order_master.');
    }

    $orderId = (int) $conn->insert_id;
    $insertOrderStmt->close();

    $insertItemStmt = $conn->prepare(
        'INSERT INTO order_item (order_id, course_id, unit_price, item_status) VALUES (?, ?, ?, ?)' 
    );
    if (!$insertItemStmt) {
        throw new RuntimeException('Không thể tạo order_item.');
    }

    $itemStatus = 'pending';
    foreach ($courseRows as $courseRow) {
        $courseId = (int) $courseRow['course_id'];
        $unitPrice = (int) $courseRow['course_price'];
        $insertItemStmt->bind_param('iiis', $orderId, $courseId, $unitPrice, $itemStatus);
        if (!$insertItemStmt->execute()) {
            $insertItemStmt->close();
            throw new RuntimeException('Không thể lưu order_item.');
        }
    }
    $insertItemStmt->close();

    $paymentMethod = 'pending';
    $paymentReference = $orderCode;
    $paymentStatus = 'pending';
    $paymentNotes = 'Chờ học viên gửi tham chiếu hoặc minh chứng thanh toán.';
    $insertPaymentStmt = $conn->prepare(
        'INSERT INTO payment (order_id, payment_method, payment_reference, payment_status, notes) VALUES (?, ?, ?, ?, ?)'
    );
    if (!$insertPaymentStmt) {
        throw new RuntimeException('Không thể tạo payment.');
    }

    $insertPaymentStmt->bind_param('issss', $orderId, $paymentMethod, $paymentReference, $paymentStatus, $paymentNotes);
    if (!$insertPaymentStmt->execute()) {
        $insertPaymentStmt->close();
        throw new RuntimeException('Không thể lưu payment.');
    }
    $insertPaymentStmt->close();

    if ($checkoutType === 'cart') {
        $softDeleteCartStmt = $conn->prepare('UPDATE cart SET is_deleted = 1 WHERE stu_email = ? AND course_id = ? AND is_deleted = 0');
        if (!$softDeleteCartStmt) {
            throw new RuntimeException('Không thể cập nhật giỏ hàng.');
        }

        foreach ($cartCourseIds as $courseId) {
            $softDeleteCartStmt->bind_param('si', $stuEmail, $courseId);
            if (!$softDeleteCartStmt->execute()) {
                $softDeleteCartStmt->close();
                throw new RuntimeException('Không thể đồng bộ giỏ hàng sau khi tạo đơn.');
            }
        }

        $softDeleteCartStmt->close();
    }

    $conn->commit();
    commerce_set_flash('success', 'Đơn hàng đã được tạo. Vui lòng gửi minh chứng thanh toán để chờ admin xác minh.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
} catch (Throwable $exception) {
    $conn->rollback();
    commerce_set_flash('error', 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.');
    header('Location: ' . ($checkoutType === 'cart' ? 'Student/myCart.php' : 'coursedetails.php?course_id=' . (int) ($courseRows[0]['course_id'] ?? 0)));
    exit;
}
