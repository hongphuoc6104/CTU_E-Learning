<?php

require_once(__DIR__ . '/instructorInclude/auth.php');

instructor_force_logout();
session_regenerate_id(true);

header('Location: instructorLogin.php');
exit;
