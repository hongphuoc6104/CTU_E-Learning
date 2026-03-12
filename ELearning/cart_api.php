<?php
session_start();
include('dbConnection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['is_login'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập để sử dụng giỏ hàng!']);
    exit;
}

$stuEmail = $_SESSION['stuLogEmail'];
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($action == 'add') {
    $course_id = $_POST['course_id'];
    
    // Check if already bought
    $check_bought = "SELECT * FROM courseorder WHERE stu_email = '$stuEmail' AND course_id = '$course_id' AND status = 'TXN_SUCCESS'";
    $res_bought = $conn->query($check_bought);
    if ($res_bought->num_rows > 0) {
        echo json_encode(['status' => 'info', 'msg' => 'Bạn đã mua khoá học này rồi!']);
        exit;
    }

    // Check if already in cart
    $check_cart = "SELECT * FROM cart WHERE stu_email = '$stuEmail' AND course_id = '$course_id'";
    $res_cart = $conn->query($check_cart);
    if ($res_cart->num_rows > 0) {
        echo json_encode(['status' => 'info', 'msg' => 'Khoá học đã có trong giỏ hàng!']);
        exit;
    }

    // Add to cart
    $sql = "INSERT INTO cart (stu_email, course_id) VALUES ('$stuEmail', '$course_id')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'msg' => 'Đã thêm vào giỏ hàng!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
    }
} 
elseif ($action == 'remove') {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE cart_id = '$cart_id' AND stu_email = '$stuEmail'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'msg' => 'Đã xoá khỏi giỏ!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
    }
} 
elseif ($action == 'count') {
    $sql = "SELECT COUNT(*) as count FROM cart WHERE stu_email = '$stuEmail'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'count' => $row['count']]);
}
?>
