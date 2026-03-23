<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
include('../dbConnection.php');

header('Content-Type: application/json');

if (isset($_SESSION['is_admin_login'])) {
    echo json_encode(1);
    exit;
}

if (!isset($_POST['checkLogemail'], $_POST['adminLogEmail'], $_POST['adminLogPass'])) {
    echo json_encode(0);
    exit;
}

$adminLogEmail = trim((string) $_POST['adminLogEmail']);
$adminLogPass = (string) $_POST['adminLogPass'];

$stmt = $conn->prepare('SELECT admin_email, admin_pass FROM admin WHERE admin_email = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(0);
    exit;
}

$stmt->bind_param('s', $adminLogEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($adminLogPass, $row['admin_pass'])) {
        session_regenerate_id(true);
        $_SESSION['is_admin_login'] = true;
        $_SESSION['adminLogEmail'] = $row['admin_email'];
        $stmt->close();
        echo json_encode(1);
        exit;
    }
}

$stmt->close();
echo json_encode(0);
