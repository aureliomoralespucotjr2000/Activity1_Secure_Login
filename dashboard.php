<?php
// dashboard.php - simple protected page (proves the login worked)
require 'session_boot.php';
start_secure_session();

const IDLE_LIMIT = 1800;   // 30 minutes

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;                                   // stop the rest of the page
}

// Idle timeout
if (time() - ($_SESSION['last_active'] ?? 0) > IDLE_LIMIT) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_active'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="panel-page">
  <main class="term" id="term">
    <section class="body">
      <p class="eyebrow">secure session</p>
      <h1 class="glitch">Login successful</h1>

      <p class="info"><span>Username</span> <?= e($_SESSION['username'] ?? '') ?></p>
      <p class="info"><span>User ID</span> <?= (int) $_SESSION['user_id'] ?></p>
      <p class="info"><span>Role</span> <?= e($_SESSION['role']) ?></p>
      <p class="info"><span>Session</span> secure</p>

      <form class="plain" method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="go" type="submit">Log out</button>
      </form>
    </section>
  </main>
</body>
</html>
