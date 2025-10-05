<?php
session_start();


$basePath = '/~!runnerslist';

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
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="landing-body">
  <header class="landing-header">
    <div class="logo-container">
      <img src="assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>RunnersList Lost &amp; Found</h1>
  </header>

  <main>
    <p class="tagline">Find lost items, report found ones, and reconnect with their owners.</p>
    <div class="actions">
      <a href="/~runnerslist/signup.php" class="btn">Sign Up</a>
      <a href="/~runnerslist/login.php" class="btn">Log In</a>
    </div>
  </main>

  <footer>
      <a href="/~runnerslist/about.php">About</a> •
      <a href="/~runnerslist/contact.php">Contact</a>
  </footer>
</body>
</html>
