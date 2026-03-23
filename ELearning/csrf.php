<?php

require_once(__DIR__ . '/session_bootstrap.php');

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        secure_session_start();

        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token): bool
    {
        secure_session_start();
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            return false;
        }

        if ($token === null) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
