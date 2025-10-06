<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$name = $_SESSION['name'] ?? 'User';
$posts = [];

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$type_filter = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

try {
  $pdo = get_pdo_connection();
  
  // Build query
  $sql = "SELECT p.*, u.full_name, u.email,
          (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.status = 'ACTIVE'";
  
  $params = [];
  
  // Apply search filter
  if ($search !== '') {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
  }
  
  // Apply type filter
  if ($type_filter !== '' && in_array($type_filter, ['lost', 'found'])) {
    $sql .= " AND p.type = ?";
    $params[] = $type_filter;
  }
  
  // Apply sorting
  switch ($sort) {
    case 'oldest':
      $sql .= " ORDER BY p.created_at ASC";
      break;
    case 'newest':
    default:
      $sql .= " ORDER BY p.created_at DESC";
      break;
  }
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  // Silent fail for now
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Community Feed – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="community-feed-container">
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>Community Feed</h1>

    <!-- Search Bar  & Filter -->
    <form method="GET" action="view_posts.php">
      <div class="search-bar">
        <input type="text" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="filters">
        <select name="type">
          <option value="">All</option>
          <option value="lost" <?= $type_filter === 'lost' ? 'selected' : '' ?>>Lost</option>
          <option value="found" <?= $type_filter === 'found' ? 'selected' : '' ?>>Found</option>
        </select>
        <select name ="sort">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort: Newest</option>
          <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        </select>
        <button type="submit" style="padding: 6px 12px;">Apply</button>
      </div>
    </form>

    <!-- Posts -->
    <?php if (!empty($posts)): ?>
      <?php foreach ($posts as $post): ?>
        <div class="post-card">
          <?php if (!empty($post['image_path'])): ?>
            <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post image">
          <?php endif; ?>
          <div class="post-details">
            <div class="post-title">
              [<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?>
            </div>
            <div class="post-meta">
              <?= htmlspecialchars($post['full_name']) ?> • 
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
              <a href="post_detail.php?id=<?= $post['id'] ?>">View Details</a>
              <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
                <a href="contact_post.php?id=<?= $post['id'] ?>">Contact</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">
        <?= $search !== '' || $type_filter !== '' ? 'No posts match your filters.' : 'No posts yet. Be the first to create one!' ?>
      </p>
    <?php endif; ?>
  </div>
</body>
</html>
