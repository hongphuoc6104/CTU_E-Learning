<?php
require_once(__DIR__ . '/session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/csrf.php');
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

    $checkCourseStmt = $conn->prepare('SELECT 1 FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1');
    if (!$checkCourseStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $checkCourseStmt->bind_param('i', $courseId);
    $checkCourseStmt->execute();
    $checkCourseStmt->store_result();
    if ($checkCourseStmt->num_rows === 0) {
        $checkCourseStmt->close();
        echo json_encode(['status' => 'error', 'msg' => 'Khoá học không tồn tại hoặc đã bị ẩn.']);
        exit;
    }
    $checkCourseStmt->close();

    $checkBoughtStmt = $conn->prepare('SELECT 1 FROM courseorder WHERE stu_email = ? AND course_id = ? AND status = ? AND is_deleted = 0 LIMIT 1');
    if (!$checkBoughtStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $successStatus = 'TXN_SUCCESS';
    $checkBoughtStmt->bind_param('sis', $stuEmail, $courseId, $successStatus);
    $checkBoughtStmt->execute();
    $checkBoughtStmt->store_result();
    if ($checkBoughtStmt->num_rows > 0) {
        $checkBoughtStmt->close();
        echo json_encode(['status' => 'info', 'msg' => 'Bạn đã có khoá học này rồi, không thể thêm!']);
        exit;
    }
    $checkBoughtStmt->close();

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
    $countStmt = $conn->prepare(
        'SELECT COUNT(*) as count '
        . 'FROM cart c '
        . 'INNER JOIN course co ON co.course_id = c.course_id '
        . 'WHERE c.stu_email = ? AND c.is_deleted = 0 AND co.is_deleted = 0'
    );
    if (!$countStmt) {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
        exit;
    }

    $countStmt->bind_param('s', $stuEmail);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $row = $result ? $result->fetch_assoc() : ['count' => 0];
    $countStmt->close();

    echo json_encode(['status' => 'success', 'count' => (int) ($row['count'] ?? 0)]);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Yêu cầu không hợp lệ.']);
?>
