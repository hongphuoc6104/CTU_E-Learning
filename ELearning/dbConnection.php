<?php
$db_host = getenv("DB_HOST") ?: "localhost";
$db_user = getenv("DB_USER") ?: "root";
$db_password = getenv("DB_PASS") ?: "";
$db_name = getenv("DB_NAME") ?: "lms_db";

// Create Connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
$conn->set_charset("utf8mb4");

// Check Connection
if($conn->connect_error) {
 die("Kết nối thất bại");
} 
?>