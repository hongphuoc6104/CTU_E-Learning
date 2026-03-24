<?php
require_once(__DIR__ . '/session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/csrf.php');
require_once(__DIR__ . '/commerce_helpers.php');
include('dbConnection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['is_login'], $_SESSION['stuLogEmail'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập để sử dụng giỏ hàng!']);
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

if ($action === 'add') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        echo json_encode(['status' => 'error', 'msg' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
        exit;
    }

    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId) {
        echo json_encode(['status' => 'error', 'msg' => 'Khoá học không hợp lệ.']);
        exit;
    }

    $studentId = commerce_get_student_id($conn, $stuEmail);
    if ($studentId === null) {
        echo json_encode(['status' => 'error', 'msg' => 'Không tìm thấy thông tin học viên.']);
        exit;
    }

    commerce_cleanup_cart($conn, $stuEmail);

    $checkCourseStmt = $conn->prepare("SELECT 1 FROM course WHERE course_id = ? AND is_deleted = 0 AND course_status = 'published' LIMIT 1");
    if (!$checkCourseStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $checkCourseStmt->bind_param('i', $courseId);
    $checkCourseStmt->execute();
    $checkCourseStmt->store_result();
    if ($checkCourseStmt->num_rows === 0) {
        $checkCourseStmt->close();
        echo json_encode(['status' => 'error', 'msg' => 'Khoá học không tồn tại, chưa được phát hành hoặc đã bị ẩn.']);
        exit;
    }
    $checkCourseStmt->close();

    $courseState = commerce_fetch_course_states($conn, $studentId, [$courseId]);
    $courseState = $courseState[$courseId] ?? ['is_enrolled' => false, 'has_open_order' => false, 'open_order_status' => null];

    if ($courseState['is_enrolled']) {
        echo json_encode(['status' => 'info', 'msg' => 'Bạn đã có khoá học này rồi, không thể thêm!']);
        exit;
    }

    if ($courseState['has_open_order']) {
        $msg = $courseState['open_order_status'] === 'awaiting_verification'
            ? 'Khóa học này đang có đơn chờ xác minh. Hãy theo dõi tại mục Đơn hàng của tôi.'
            : 'Khóa học này đang có đơn hàng chưa hoàn tất. Hãy tiếp tục thanh toán ở mục Đơn hàng của tôi.';
        echo json_encode(['status' => 'info', 'msg' => $msg]);
        exit;
    }

    $insertStmt = $conn->prepare(
        'INSERT INTO cart (stu_email, course_id, added_date, is_deleted) VALUES (?, ?, CURRENT_TIMESTAMP, 0) '
        . 'ON DUPLICATE KEY UPDATE is_deleted = 0, added_date = CURRENT_TIMESTAMP'
    );

    if (!$insertStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $insertStmt->bind_param('si', $stuEmail, $courseId);
    $ok = $insertStmt->execute();
    $insertStmt->close();

    if ($ok) {
        echo json_encode(['status' => 'success', 'msg' => 'Đã thêm vào giỏ hàng!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
    }
    exit;
}

if ($action === 'remove') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        echo json_encode(['status' => 'error', 'msg' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
        exit;
    }

    $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
    if (!$cartId) {
        echo json_encode(['status' => 'error', 'msg' => 'Mục giỏ hàng không hợp lệ.']);
        exit;
    }

    $checkCartStmt = $conn->prepare('SELECT stu_email, is_deleted FROM cart WHERE cart_id = ? LIMIT 1');
    if (!$checkCartStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $checkCartStmt->bind_param('i', $cartId);
    $checkCartStmt->execute();
    $cartResult = $checkCartStmt->get_result();
    $cartRow = $cartResult ? $cartResult->fetch_assoc() : null;
    $checkCartStmt->close();

    if (!$cartRow) {
        echo json_encode(['status' => 'info', 'msg' => 'Mục giỏ hàng không tồn tại.']);
        exit;
    }

    if (!hash_equals((string) $cartRow['stu_email'], $stuEmail)) {
        echo json_encode(['status' => 'error', 'msg' => 'Mục giỏ hàng này không thuộc tài khoản của bạn.']);
        exit;
    }

    if ((int) $cartRow['is_deleted'] === 1) {
        echo json_encode(['status' => 'info', 'msg' => 'Mục giỏ hàng này đã được xoá trước đó.']);
        exit;
    }

    $removeStmt = $conn->prepare('UPDATE cart SET is_deleted = 1 WHERE cart_id = ? AND stu_email = ? AND is_deleted = 0');
    if (!$removeStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $removeStmt->bind_param('is', $cartId, $stuEmail);
    $ok = $removeStmt->execute();
    $affectedRows = $removeStmt->affected_rows;
    $removeStmt->close();

    if ($ok && $affectedRows > 0) {
        echo json_encode(['status' => 'success', 'msg' => 'Đã xoá khỏi giỏ!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Không thể xoá mục giỏ hàng. Vui lòng thử lại.']);
    }
    exit;
}

if ($action === 'count') {
    echo json_encode(['status' => 'success', 'count' => commerce_get_cart_count($conn, $stuEmail)]);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Yêu cầu không hợp lệ.']);
?>
