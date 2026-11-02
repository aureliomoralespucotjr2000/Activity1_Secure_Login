<?php
require 'session_boot.php';
require 'db.php';
start_secure_session();

$errors   = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(403);
        exit('403 Forbidden: invalid token.');
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Task 2: validate on the server
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters: letters, numbers, underscore only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Is the username or email already taken? (prepared statement)
    if (!$errors) {
        $chk = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $chk->bind_param('ss', $username, $email);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $errors[] = 'Username or email is already registered.';
        }
    }

    // Hash the password, then INSERT with a prepared statement
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';   // the role is NEVER read from the form

        try {
            $ins = $conn->prepare(
                'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $ins->bind_param('ssss', $username, $email, $hash, $role);
            $ins->execute();

            $_SESSION['flash'] = 'Registration successful. Please log in.';
            header('Location: login.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {          // someone registered it a moment ago
                $errors[] = 'Username or email is already registered.';
            } else {
                error_log($ex->getMessage());
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="flow">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Register</title>
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
        <a href="login.php">Log in</a>
        <a href="register.php" class="on">Register</a>
      </nav>
      <a class="pill" href="login.php">Log in</a>
    </header>

    <div class="col">
      <p class="badge reveal" style="--d:320ms">Free account for this lab</p>
      <h1 class="title" data-title>Create account</h1>
      <p class="sub" data-sub>Your password is hashed before it is saved.</p>

      <form class="signin reveal" style="--d:1450ms" method="post" action="register.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input class="field" type="text" name="username" placeholder="Username" value="<?= e($username) ?>" required maxlength="30" spellcheck="false" autocapitalize="off">
        <input class="field" type="email" name="email" placeholder="Email" value="<?= e($email) ?>" required maxlength="100" spellcheck="false" autocapitalize="off">
        <input class="field" type="password" name="password" placeholder="Password (min. 8 characters)" required>
        <input class="field" type="password" name="confirm_password" placeholder="Confirm password" required>
        <button class="pill block" type="submit">Create Account</button>
        <?php foreach ($errors as $err): ?>
          <p class="alert"><?= e($err) ?></p>
        <?php endforeach; ?>
        <p class="links">Already registered? <a href="login.php">Log in</a></p>
      </form>
    </div>

    <footer class="foot reveal" style="--d:1650ms">© 2026 Aurelio M. Pucot Jr — BSINFOTECH 4A.</footer>
  </section>

  <script src="fluid.js"></script>
  <script src="script.js"></script>
</body>
</html>
