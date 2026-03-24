<?php

require_once(__DIR__ . '/instructorInclude/auth.php');

header('Content-Type: application/json');

if (instructor_is_logged_in()) {
    echo json_encode(1);
    exit;
}

if (!isset($_POST['checkLogemail'], $_POST['insLogEmail'], $_POST['insLogPass'])) {
    echo json_encode(0);
    exit;
}

$insLogEmail = trim((string) $_POST['insLogEmail']);
$insLogPass = (string) $_POST['insLogPass'];

if ($insLogEmail === '' || $insLogPass === '' || !filter_var($insLogEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(0);
    exit;
}

$stmt = $conn->prepare(
    'SELECT ins_id, ins_email, ins_pass, ins_status, is_deleted '
    . 'FROM instructor WHERE ins_email = ? LIMIT 1'
);
if (!$stmt) {
    echo json_encode(0);
    exit;
}

$stmt->bind_param('s', $insLogEmail);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(0);
    exit;
}

if ((int) ($row['is_deleted'] ?? 0) === 1 || (string) ($row['ins_status'] ?? '') !== 'active') {
    echo json_encode(0);
    exit;
}

if (!password_verify($insLogPass, (string) ($row['ins_pass'] ?? ''))) {
    echo json_encode(0);
    exit;
}

session_regenerate_id(true);
$_SESSION['is_instructor_login'] = true;
$_SESSION['instructor_id'] = (int) $row['ins_id'];
$_SESSION['instructorLogEmail'] = (string) $row['ins_email'];

echo json_encode(1);
