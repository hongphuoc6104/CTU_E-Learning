<?php
$db_host = getenv("DB_HOST") ?: "localhost";
$db_user = getenv("DB_USER") ?: "root";
$db_password = getenv("DB_PASS") ?: "";
$db_name = getenv("DB_NAME") ?: "lms_db";

mysqli_report(MYSQLI_REPORT_OFF);

// Create Connection
$conn = @new mysqli($db_host, $db_user, $db_password, $db_name);

// Check Connection
if($conn->connect_errno) {
 error_log('Database connection failed: ' . $conn->connect_error);
 if(!headers_sent()) {
  http_response_code(500);
  header('Content-Type: text/html; charset=UTF-8');
 }
 echo '<div style="font-family:Inter,Arial,sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;">'
   . '<h2 style="margin:0 0 10px;color:#0f172a;">Không thể kết nối hệ thống</h2>'
   . '<p style="margin:0;color:#475569;line-height:1.6;">Máy chủ đang bận hoặc gặp sự cố kết nối dữ liệu. Vui lòng thử lại sau.</p>'
   . '</div>';
 exit;
}

if(!$conn->set_charset("utf8mb4")) {
 error_log('Failed to set DB charset utf8mb4: ' . $conn->error);
}
?>
