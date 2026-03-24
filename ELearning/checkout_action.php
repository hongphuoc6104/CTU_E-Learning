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
    header('Location: Student/myOrders.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    commerce_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.');
    header('Location: Student/myOrders.php');
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$studentId = commerce_get_student_id($conn, $stuEmail);
$orderCode = trim((string) ($_POST['order_code'] ?? ''));

if ($studentId === null || $orderCode === '') {
    commerce_set_flash('error', 'Thiếu thông tin đơn hàng để gửi minh chứng thanh toán.');
    header('Location: Student/myOrders.php');
    exit;
}

$orderStmt = $conn->prepare(
    'SELECT om.order_id, om.order_code, om.order_status, p.payment_id, p.payment_status, p.payment_method, p.payment_reference, p.payment_proof_url, p.notes '
    . 'FROM order_master om '
    . 'LEFT JOIN payment p ON p.order_id = om.order_id '
    . 'WHERE om.order_code = ? AND om.student_id = ? AND om.is_deleted = 0 LIMIT 1'
);

if (!$orderStmt) {
    commerce_set_flash('error', 'Không thể tải đơn hàng để cập nhật thanh toán.');
    header('Location: Student/myOrders.php');
    exit;
}

$orderStmt->bind_param('si', $orderCode, $studentId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult ? $orderResult->fetch_assoc() : null;
$orderStmt->close();

if (!$order) {
    commerce_set_flash('error', 'Không tìm thấy đơn hàng cần cập nhật thanh toán.');
    header('Location: Student/myOrders.php');
    exit;
}

$currentOrderStatus = (string) ($order['order_status'] ?? 'pending');
$currentPaymentStatus = (string) ($order['payment_status'] ?? 'pending');
if (!commerce_can_submit_payment($currentOrderStatus, $currentPaymentStatus)) {
    commerce_set_flash('error', 'Đơn hàng này hiện không ở trạng thái cho phép gửi lại thanh toán.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
}

$paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
$paymentReference = trim((string) ($_POST['payment_reference'] ?? ''));
$allowedMethods = ['bank_transfer', 'qr_transfer', 'momo'];

if (!in_array($paymentMethod, $allowedMethods, true)) {
    commerce_set_flash('error', 'Vui lòng chọn phương thức thanh toán hợp lệ.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
}

$hasProofUpload = isset($_FILES['payment_proof'])
    && (int) ($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if ($paymentReference === '' && !$hasProofUpload) {
    commerce_set_flash('error', 'Bạn cần nhập mã tham chiếu hoặc tải lên minh chứng thanh toán. Đơn hàng sẽ được giữ ở trạng thái chờ.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
}

if ($paymentReference !== '' && (!preg_match('/^[A-Za-z0-9._\-]{4,120}$/', $paymentReference))) {
    commerce_set_flash('error', 'Mã tham chiếu chỉ được chứa chữ, số, dấu chấm, gạch nối và có độ dài từ 4 đến 120 ký tự.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
}

$newProofDbPath = null;
$newProofDiskPath = null;
$existingProofPath = (string) ($order['payment_proof_url'] ?? '');

if ($hasProofUpload) {
    $proofError = (int) ($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE);
    $proofName = (string) ($_FILES['payment_proof']['name'] ?? '');
    $proofTmpName = (string) ($_FILES['payment_proof']['tmp_name'] ?? '');
    $proofSize = (int) ($_FILES['payment_proof']['size'] ?? 0);
    $proofExt = strtolower(pathinfo($proofName, PATHINFO_EXTENSION));
    $allowedProofTypes = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if ($proofError !== UPLOAD_ERR_OK) {
        commerce_set_flash('error', 'Không thể tải minh chứng thanh toán lên máy chủ.');
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
        exit;
    }

    if (!in_array($proofExt, $allowedProofTypes, true)) {
        commerce_set_flash('error', 'Minh chứng thanh toán chỉ hỗ trợ JPG, JPEG, PNG, WebP hoặc PDF.');
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
        exit;
    }

    if ($proofSize <= 0 || $proofSize > 3 * 1024 * 1024) {
        commerce_set_flash('error', 'Minh chứng thanh toán phải có dung lượng từ 1 byte đến tối đa 3MB.');
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
        exit;
    }

    $safeFileName = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $proofExt;
    $uploadDir = __DIR__ . '/image/paymentproof/';
    $newProofDiskPath = $uploadDir . $safeFileName;
    $newProofDbPath = 'image/paymentproof/' . $safeFileName;

    // Pre-flight: ensure upload directory exists and is writable by web server
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    if (!is_writable($uploadDir)) {
        commerce_set_flash('error', 'Thư mục lưu minh chứng chưa được cấp quyền ghi. Vui lòng liên hệ quản trị viên.');
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
        exit;
    }

    if (!move_uploaded_file($proofTmpName, $newProofDiskPath)) {
        commerce_set_flash('error', 'Không thể lưu minh chứng thanh toán. Vui lòng thử lại.');
        header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
        exit;
    }
}

$finalReference = $paymentReference !== ''
    ? $paymentReference
    : ((string) ($order['payment_reference'] ?? '') !== '' ? (string) $order['payment_reference'] : $orderCode);
$finalProofPath = $newProofDbPath ?? ($existingProofPath !== '' ? $existingProofPath : null);
$paymentStatus = 'submitted';
$orderStatus = 'awaiting_verification';
$notePrefix = $currentPaymentStatus === 'rejected' ? 'Học viên đã gửi lại minh chứng thanh toán.' : 'Học viên đã gửi minh chứng thanh toán.';
$previousNote = trim((string) ($order['notes'] ?? ''));
$paymentNote = $notePrefix;
if ($currentPaymentStatus === 'rejected' && $previousNote !== '') {
    $paymentNote .= ' Ghi chú lần trước: ' . $previousNote;
}

$conn->begin_transaction();

try {
    $updatePaymentStmt = $conn->prepare(
        'UPDATE payment SET payment_method = ?, payment_reference = ?, payment_proof_url = ?, payment_status = ?, notes = ?, verified_by_admin_id = NULL, verified_at = NULL WHERE payment_id = ? LIMIT 1'
    );
    if (!$updatePaymentStmt) {
        throw new RuntimeException('Không thể cập nhật payment.');
    }

    $paymentId = (int) ($order['payment_id'] ?? 0);
    if ($paymentId <= 0) {
        $updatePaymentStmt->close();
        throw new RuntimeException('Đơn hàng chưa có bản ghi payment hợp lệ.');
    }
    $updatePaymentStmt->bind_param('sssssi', $paymentMethod, $finalReference, $finalProofPath, $paymentStatus, $paymentNote, $paymentId);
    if (!$updatePaymentStmt->execute()) {
        $updatePaymentStmt->close();
        throw new RuntimeException('Không thể lưu thông tin thanh toán.');
    }
    $updatePaymentStmt->close();

    $updateOrderStmt = $conn->prepare('UPDATE order_master SET order_status = ? WHERE order_id = ? LIMIT 1');
    if (!$updateOrderStmt) {
        throw new RuntimeException('Không thể cập nhật order_master.');
    }

    $orderId = (int) $order['order_id'];
    $updateOrderStmt->bind_param('si', $orderStatus, $orderId);
    if (!$updateOrderStmt->execute()) {
        $updateOrderStmt->close();
        throw new RuntimeException('Không thể đổi trạng thái đơn hàng.');
    }
    $updateOrderStmt->close();

    $conn->commit();

    if ($newProofDiskPath !== null && $existingProofPath !== '' && str_starts_with($existingProofPath, 'image/paymentproof/')) {
        $oldDiskPath = __DIR__ . '/' . $existingProofPath;
        if (is_file($oldDiskPath) && realpath($oldDiskPath) !== realpath($newProofDiskPath)) {
            @unlink($oldDiskPath);
        }
    }

    commerce_set_flash('success', 'Đã gửi thông tin thanh toán thành công. Đơn hàng hiện đang chờ admin xác minh.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
} catch (Throwable $exception) {
    $conn->rollback();
    if ($newProofDiskPath !== null && is_file($newProofDiskPath)) {
        @unlink($newProofDiskPath);
    }

    commerce_set_flash('error', 'Không thể gửi thông tin thanh toán lúc này. Vui lòng thử lại.');
    header('Location: Student/orderDetails.php?order_code=' . rawurlencode($orderCode));
    exit;
}
