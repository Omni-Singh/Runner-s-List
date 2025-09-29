<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name = $_SESSION['name'] ?? 'User';

// No posts yet – backend will populate $posts later
$posts = [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Community Feed – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <div class="community-feed-container">
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>Community Feed</h1>

    <!-- Search Bar -->
    <div class="search-bar">
      <input type="text" placeholder="Search posts...">
    </div>

    <!-- Filters -->
    <div class="filters">
      <select>
        <option>All</option>
        <option>Lost</option>
        <option>Found</option>
      </select>
      <select>
        <option>Sort: Newest</option>
        <option>Oldest</option>
        <option>Most Viewed</option>
      </select>
    </div>

    <!-- Posts -->
    <?php if (!empty($posts)): ?>
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
              <a href="#">View</a>
              <a href="#">Contact</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">No posts yet. Be the first to create one!</p>
    <?php endif; ?>
  </div>
</body>
</html>
