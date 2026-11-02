<?php
require 'session_boot.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    // 1. Clear session data
    session_unset();
    $_SESSION = [];

    // 2. Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    // 3. Destroy the session on the server
    session_destroy();
}

header('Location: login.php');
exit;
