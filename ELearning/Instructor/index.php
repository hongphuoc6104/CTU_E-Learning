<?php

require_once(__DIR__ . '/instructorInclude/auth.php');

if (instructor_is_logged_in() && instructor_current_profile($conn)) {
    header('Location: instructorDashboard.php');
    exit;
}

header('Location: instructorLogin.php');
exit;
