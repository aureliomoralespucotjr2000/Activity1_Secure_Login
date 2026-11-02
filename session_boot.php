<?php
// session_boot.php - secure session start + CSRF helpers

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Harden the cookie BEFORE session_start()
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,      // HTTPS only (auto-off on http://localhost)
        'httponly' => true,        // JavaScript cannot read the cookie
        'samesite' => 'Lax',       // limits CSRF
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid(): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
}

function e(string $s): string   // escape output (XSS protection)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
