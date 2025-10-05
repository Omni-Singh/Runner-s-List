<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name = $_SESSION['name'] ?? 'User';

// require_once "includes/load_my_posts.php";
// $posts = get_user_posts($_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Posts – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="my-posts-container">
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>My Posts</h1>

    <!-- Placeholder for posts -->
    <?php if (!empty($posts ?? [])): ?>
      <?php foreach ($posts as $post): ?>
        <div class="post-card">
          <?php if (!empty($post['image'])): ?>
            <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image">
          <?php endif; ?>
          <div class="post-details">
            <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
            <div class="post-meta">
              <?= htmlspecialchars($post['author']) ?> • <?= htmlspecialchars($post['timestamp']) ?>
            </div>
            <div class="post-description"><?= htmlspecialchars($post['description']) ?></div>
            <div class="post-actions">
              <a href="#">Edit</a>
              <a href="#">Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>You have no posts yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
