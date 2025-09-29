<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$post_id = (int)($_GET['id'] ?? 0);
$post = null;
$images = [];

try {
  $pdo = get_pdo_connection();
  
  // Get post details with user info
  $stmt = $pdo->prepare(
    "SELECT p.*, u.full_name, u.email 
     FROM posts p
     JOIN users u ON p.user_id = u.id
     WHERE p.id = ? AND p.status = 'ACTIVE'
     LIMIT 1"
  );
  $stmt->execute([$post_id]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$post) {
    header("Location: view_posts.php");
    exit;
  }
  
  // Get all images for this post
  $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
  $stmt->execute([$post_id]);
  $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  header("Location: view_posts.php");
  exit;
}

$is_owner = ($post['user_id'] == $_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($post['title']) ?> – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="post-detail-container">
    <a href="view_posts.php" class="back-arrow" aria-label="Back to Feed">&larr;</a>
    
    <div class="post-detail-header">
      <div class="post-type-badge">
        <?= strtoupper(htmlspecialchars($post['type'])) ?>
      </div>
      <h1 class="post-detail-title"><?= htmlspecialchars($post['title']) ?></h1>
      <div class="post-detail-meta">
        Posted by <strong><?= htmlspecialchars($post['full_name']) ?></strong> 
        on <?= date('F j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
      </div>
    </div>

    <?php if (!empty($images)): ?>
      <div class="post-detail-images">
        <?php foreach ($images as $img): ?>
          <img src="/~runnerslist<?= htmlspecialchars($post['image_path']) ?>" alt="Post image">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="post-detail-section">
      <h3>Description</h3>
      <p style="white-space: pre-wrap; line-height: 1.6;"><?= htmlspecialchars($post['description']) ?></p>
    </div>

    <div class="post-detail-section">
      <h3>Details</h3>
      <p><strong>Location:</strong> <?= htmlspecialchars($post['location']) ?></p>
      <?php if ($post['lost_date']): ?>
        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($post['lost_date'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="post-detail-actions">
      <?php if ($is_owner): ?>
        <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn-primary">Edit Post</a>
        <a href="delete_post.php?id=<?= $post['id'] ?>" 
           class="btn-secondary"
           onclick="return confirm('Are you sure you want to delete this post?')">Delete Post</a>
      <?php else: ?>
        <a href="contact_post.php?id=<?= $post['id'] ?>" class="btn-primary">Contact Owner</a>
      <?php endif; ?>
      <a href="view_posts.php" class="btn-secondary">Back to Feed</a>
    </div>
  </div>
</body>
</html>