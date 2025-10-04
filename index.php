<?php
session_start();

// Detect environment: local vs Artemis
$basePath = (strpos($_SERVER['HTTP_HOST'], 'artemis.cs.csubak.edu') !== false)
  ? '/~runnerslist'
  : '';

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
  <title>Welcome – CSUB Lost & Found</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="landing-body">
  <header class="landing-header">
    <div class="logo-container">
      <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>CSUB Lost &amp; Found</h1>
  </header>

  <main>
    <p class="tagline">Find lost items, report found ones, and reconnect with their owners.</p>
    <div class="actions">
      <a href="<?= $basePath ?>/signup.php" class="btn">Sign Up</a>
      <a href="<?= $basePath ?>/login.php" class="btn">Log In</a>
    </div>
  </main>

  <footer>
    <!-- TODO: Replace with real pages later -->
      <a href="<?= $basePath ?>/about.php">About</a> •
      <a href="<?= $basePath ?>/contact.php">Contact</a> 
  </footer>
</body>
</html>
