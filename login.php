<?php
require 'session_boot.php';
require 'db.php';
start_secure_session();

const MAX_ATTEMPTS  = 5;
const LOCK_MINUTES  = 15;
const GENERIC_ERROR = 'Invalid username or password.';

// true  = show "account locked, try again in X minutes"
// false = always show the generic error (what the activity sheet asks for)
const SHOW_LOCK_MESSAGE = true;

function lock_message(int $minutes): string {
    $minutes = max(1, $minutes);
    return 'Too many failed attempts. Your account is locked. Please try again in '
         . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.';
}

$error    = '';
$username = '';
$flash    = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(403);
        exit('403 Forbidden: invalid token.');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';


    $stmt = $conn->prepare(
        'SELECT id, username, password_hash, role, failed_attempts,
                (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked,
                CEIL(TIMESTAMPDIFF(SECOND, NOW(), locked_until) / 60) AS minutes_left
         FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();


    $dummy = '$2y$10$usesomesillystringforsalt.0000000000000000000000000000';
    $hash  = $user['password_hash'] ?? $dummy;
    $valid = password_verify($password, $hash);

    if ($user && !$user['is_locked'] && $valid) {
        
        session_regenerate_id(true);
        $_SESSION['user_id']     = (int) $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['last_active'] = time();

        // Rehash if PHP's default algorithm/cost has improved
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $new = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $up->bind_param('si', $new, $user['id']);
            $up->execute();
        }

        
        $reset = $conn->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
        $reset->bind_param('i', $user['id']);
        $reset->execute();

        header('Location: dashboard.php');
        exit;
    }

    
    $error = GENERIC_ERROR;

    // Already locked: refuse (even if the password was correct)
    if ($user && $user['is_locked'] && SHOW_LOCK_MESSAGE) {
        $error = lock_message((int) $user['minutes_left']);
    }

    // Wrong password on an unlocked account: count it
    if ($user && !$user['is_locked']) {
        $attempts = (int) $user['failed_attempts'] + 1;
        if ($attempts >= MAX_ATTEMPTS) {
            $lock = $conn->prepare(
                'UPDATE users SET failed_attempts = 0,
                 locked_until = DATE_ADD(NOW(), INTERVAL ' . LOCK_MINUTES . ' MINUTE)
                 WHERE id = ?'
            );
            $lock->bind_param('i', $user['id']);
            $lock->execute();
            if (SHOW_LOCK_MESSAGE) {
                $error = lock_message(LOCK_MINUTES);   // 5th wrong try: lock now
            }
        } else {
            $inc = $conn->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
            $inc->bind_param('ii', $attempts, $user['id']);
            $inc->execute();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="flow">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Login</title>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<noscript><style>.reveal, .w { opacity: 1 !important; transform: none !important; }</style></noscript>
</head>
<body>
  <canvas id="fluid" aria-hidden="true"></canvas>
  <div class="scrim" aria-hidden="true"></div>

  <section class="hero">
    <header class="nav reveal" style="--d:150ms; --y:-0.75rem">
      <a class="brand" href="login.php">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M2.5 9c2.5 0 2.5 4.2 5 4.2S10 9 12 9s2.5 4.2 5 4.2S19.5 9 21.5 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M2.5 15c2.5 0 2.5 4.2 5 4.2S10 15 12 15s2.5 4.2 5 4.2S19.5 15 21.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.5"/>
        </svg>
        Secure Login
      </a>
      <nav class="nav-pill">
        <a href="login.php" class="on">Log in</a>
        <a href="register.php">Register</a>
      </nav>
      <a class="pill" href="register.php">Register</a>
    </header>

    <div class="col">
      <p class="badge reveal" style="--d:320ms">Passwords hashed with bcrypt</p>
      <h1 class="title" data-title>Welcome back</h1>
      <p class="sub" data-sub>Log in to continue to your dashboard.</p>

      <form class="signin reveal<?= $error ? ' shake' : '' ?>" style="--d:1450ms" id="form" method="post" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($flash): ?><p class="note" role="status"><?= e($flash) ?></p><?php endif; ?>
        <input class="field" id="user" name="username" type="text" placeholder="Username" value="<?= e($username) ?>" autocomplete="username" spellcheck="false" autocapitalize="off">
        <input class="field" id="pass" name="password" type="password" placeholder="Password" autocomplete="current-password">
        <button class="pill block" id="go" type="submit">Log In</button>
        <p class="alert" id="status" role="alert"<?= $error ? '' : ' hidden' ?>><?= e($error) ?></p>
        <p class="links">No account yet? <a href="register.php">Register</a></p>
      </form>
    </div>

    <footer class="foot reveal" style="--d:1650ms">© 2026 Aurelio M. Pucot Jr — BSINFOTECH 4A.</footer>
  </section>

  <script src="fluid.js"></script>
  <script src="script.js"></script>
</body>
</html>
