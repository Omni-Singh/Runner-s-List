<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name  = $_SESSION['name']  ?? 'User';
$email = $_SESSION['email'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Account Settings – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="container">
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>Account Settings</h1>

    <p class="placeholder-text">
      Account settings will go here.  
      Currently logged in as <strong><?= htmlspecialchars($name) ?></strong> (<?= htmlspecialchars($email) ?>).
    </p>
  </div>
</body>
</html>
