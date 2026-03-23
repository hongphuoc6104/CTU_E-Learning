<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();

if (!isset($_SESSION['is_login'])) {
    header('Location: ../index.php');
    exit;
}

header('Location: studentProfile.php#tab-password');
exit;
