<?php
require_once(__DIR__ . '/session_bootstrap.php');
secure_session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => !empty($params['secure']),
        'httponly' => !empty($params['httponly']),
        'samesite' => 'Lax',
    ]);
}

session_destroy();
header('Location: index.php');
exit;
?>
