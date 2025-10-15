<?php
require_once('includes/config.php');

// If user already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Welcome – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="landing-body">
  <header>
  <div class="logo-container">
    <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
  </div>
  <h1>Runnerslist</h1>
</header>

  <main>
    <p class="tagline">Find lost items, report found ones, and reconnect with their owners.</p>
    <div class="actions">
      <a href="signup.php">Sign Up</a>
      <a href="login.php">Log In</a>
    </div>
  </main>

  <footer>
    <a href="<?= $basePath ?>/about.php">About</a> •
    <a href="<?= $basePath ?>/contact.php">Contact</a> 
  </footer>
</body>
</html>
