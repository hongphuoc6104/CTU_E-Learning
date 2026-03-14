<?php
include('dbConnection.php');

$tables = ['course', 'lesson', 'student', 'feedback', 'courseorder', 'cart'];
foreach ($tables as $t) {
    // check if column is_deleted exists
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'is_deleted'");
    if($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$t` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0");
        echo "Added is_deleted to $t\n";
    } else {
        echo "$t already has is_deleted\n";
    }
}
echo "Done.\n";
