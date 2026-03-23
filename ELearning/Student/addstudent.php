<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
include_once('../dbConnection.php');

header('Content-Type: application/json');

function json_response($payload): void
{
    echo json_encode($payload);
    exit;
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

if (isset($_POST['stuemail'], $_POST['checkemail'])) {
    $stuemail = trim((string) $_POST['stuemail']);
    if ($stuemail === '' || !filter_var($stuemail, FILTER_VALIDATE_EMAIL) || text_length($stuemail) > 191) {
        json_response(0);
    }

    $stmt = $conn->prepare('SELECT 1 FROM student WHERE stu_email = ? LIMIT 1');
    if (!$stmt) {
        json_response(0);
    }

    $stmt->bind_param('s', $stuemail);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0 ? 1 : 0;
    $stmt->close();

    json_response($exists);
}

if (isset($_POST['stusignup'], $_POST['stuname'], $_POST['stuemail'], $_POST['stupass'])) {
    $stuname = trim((string) $_POST['stuname']);
    $stuemail = trim((string) $_POST['stuemail']);
    $stupass = (string) $_POST['stupass'];

    if ($stuname === '' || $stuemail === '' || $stupass === '') {
        json_response('Failed');
    }

    $nameLength = text_length($stuname);
    $emailLength = text_length($stuemail);
    $passwordLength = strlen($stupass);

    if (!filter_var($stuemail, FILTER_VALIDATE_EMAIL)
        || $nameLength < 2
        || $nameLength > 100
        || $emailLength > 191
        || $passwordLength < 6
        || $passwordLength > 72) {
        json_response('Failed');
    }

    $dupStmt = $conn->prepare('SELECT 1 FROM student WHERE stu_email = ? LIMIT 1');
    if (!$dupStmt) {
        json_response('Failed');
    }

    $dupStmt->bind_param('s', $stuemail);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        $dupStmt->close();
        json_response('Failed');
    }
    $dupStmt->close();

    $hashedPass = password_hash($stupass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO student (stu_name, stu_email, stu_pass, stu_occ, stu_img) VALUES (?, ?, ?, \'\', \'\')');

    if (!$stmt) {
        json_response('Failed');
    }

    $stmt->bind_param('sss', $stuname, $stuemail, $hashedPass);
    $ok = $stmt->execute();
    $stmt->close();

    json_response($ok ? 'OK' : 'Failed');
}

if (!isset($_SESSION['is_login']) && isset($_POST['checkLogemail'], $_POST['stuLogEmail'], $_POST['stuLogPass'])) {
    $stuLogEmail = trim((string) $_POST['stuLogEmail']);
    $stuLogPass = (string) $_POST['stuLogPass'];

    if ($stuLogEmail === ''
        || $stuLogPass === ''
        || !filter_var($stuLogEmail, FILTER_VALIDATE_EMAIL)
        || text_length($stuLogEmail) > 191) {
        json_response(0);
    }

    $stmt = $conn->prepare('SELECT stu_email, stu_pass FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
    if (!$stmt) {
        json_response(0);
    }

    $stmt->bind_param('s', $stuLogEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($stuLogPass, $row['stu_pass'])) {
            session_regenerate_id(true);
            $_SESSION['is_login'] = true;
            $_SESSION['stuLogEmail'] = $row['stu_email'];
            $stmt->close();
            json_response(1);
        }
    }

    $stmt->close();
    json_response(0);
}

json_response(0);
