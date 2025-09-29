<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name = $_SESSION['name'] ?? 'User';
$success_message = $_GET['msg'] ?? '';
$active_posts = [];
$resolved_posts = [];

try {
  $pdo = get_pdo_connection();
  
  // Get active posts
  $stmt = $pdo->prepare(
    "SELECT p.*, 
     (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
     FROM posts p
     WHERE p.user_id = ? AND p.status = 'ACTIVE'
     ORDER BY p.created_at DESC"
  );
  $stmt->execute([$_SESSION['user_id']]);
  $active_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get resolved posts
  $stmt = $pdo->prepare(
    "SELECT p.*, 
     (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
     FROM posts p
     WHERE p.user_id = ? AND p.status = 'RESOLVED'
     ORDER BY p.created_at DESC"
  );
  $stmt->execute([$_SESSION['user_id']]);
  $resolved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  // Silent fail
}
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

    <?php if ($success_message): ?>
      <div class="note" role="alert"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Active Posts Section -->
    <div class="section-header">
      <h2>Active Posts <span class="count">(<?= count($active_posts) ?>)</span></h2>
    </div>

    <?php if (!empty($active_posts)): ?>
      <?php foreach ($active_posts as $post): ?>
        <div class="post-card">
          <?php if (!empty($post['image_path'])): ?>
            <img src="/~runnerslist<?= htmlspecialchars($post['image_path']) ?>" alt="Post image">
          <?php endif; ?>
          <div class="post-details">
            <div class="post-title">
              [<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?>
            </div>
            <div class="post-meta">
              <?= date('M j, Y', strtotime($post['created_at'])) ?>
              <?php if ($post['location']): ?>
                • <?= htmlspecialchars($post['location']) ?>
              <?php endif; ?>
            </div>
            <div class="post-description">
              <?= htmlspecialchars(substr($post['description'], 0, 150)) ?>
              <?= strlen($post['description']) > 150 ? '...' : '' ?>
            </div>
            <div class="post-actions">
              <a href="post_detail.php?id=<?= $post['id'] ?>">View</a>
              <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
              <a href="delete_post.php?id=<?= $post['id'] ?>" 
                 onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">You have no active posts.</p>
      <div style="text-align: center; margin-top: 1rem;">
        <a href="post_create.php" style="display: inline-block; padding: 10px 20px; background: var(--brand); color: white; border-radius: 8px; text-decoration: none;">Create Your First Post</a>
      </div>
    <?php endif; ?>

    <!-- Resolved Posts Section -->
    <?php if (!empty($resolved_posts)): ?>
      <div class="section-header">
        <h2>Resolved Posts <span class="count">(<?= count($resolved_posts) ?>)</span></h2>
      </div>

      <?php foreach ($resolved_posts as $post): ?>
        <div class="post-card" style="opacity: 0.85;">
          <?php if (!empty($post['image_path'])): ?>
            <img src="uploads/<?= htmlspecialchars($post['image_path']) ?>" alt="Post image">
          <?php endif; ?>
          <div class="post-details">
            <div class="post-title">
              [<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?>
              <span class="resolved-badge">RESOLVED</span>
            </div>
            <div class="post-meta">
              <?= date('M j, Y', strtotime($post['created_at'])) ?>
              <?php if ($post['location']): ?>
                • <?= htmlspecialchars($post['location']) ?>
              <?php endif; ?>
            </div>
            <div class="post-description">
              <?= htmlspecialchars(substr($post['description'], 0, 150)) ?>
              <?= strlen($post['description']) > 150 ? '...' : '' ?>
            </div>
            <div class="post-actions">
              <a href="post_detail.php?id=<?= $post['id'] ?>">View</a>
              <a href="edit_post.php?id=<?= $post['id'] ?>">Reactivate</a>
              <a class="disabled" title="Cannot delete resolved posts">Delete</a>
            </div>
            <small style="color: #666; font-style: italic; margin-top: 8px; display: block;">
              This post was successfully resolved and is archived here.
            </small>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</body>
</html>
