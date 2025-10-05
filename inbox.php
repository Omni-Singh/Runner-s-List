<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name = $_SESSION['name'] ?? 'User';

// No mock data — backend will populate $messages later
$messages = [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Inbox – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="container">
    <!-- Back button -->
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>Inbox</h1>

    <?php if (!empty($messages)): ?>
      <?php foreach ($messages as $msg): ?>
        <div class="message-card">
          <div class="message-title"><?= htmlspecialchars($msg['post_title']) ?></div>
          <div class="message-text"><?= htmlspecialchars($msg['action']) ?></div>
          <div class="message-meta">
            <?= htmlspecialchars($msg['timestamp']) ?>
          </div>
          <div class="message-actions">
            <a href="view_post.php?id=<?= urlencode($msg['post_id']) ?>">View Post</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">No messages yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
