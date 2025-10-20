<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

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
    <title>My Posts – <?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body>

    <header class="dashboard-header">
    <a href="<?= $basePath ?>/index.php" class="logo">
        <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo">
    </a>
    <div class="header-main-actions">
    <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Post</a>
    <a href="<?= $basePath ?>/my_posts.php" class="btn btn-secondary">My Posts</a>
</div>
    <div class="search-container">
        <input type="search" placeholder="Search...">
    </div>
    
    <div class="header-actions">
        <div class="notification-icon">
            <a href="<?= $basePath ?>/inbox.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </a>
        </div>
        <div class="profile-icon">
            <a href="<?= $basePath ?>/account.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Account</span>
            </a>
        </div>
        <div class="logout-icon">
            <a href="<?= $basePath ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

    <main class="page-container">
        <div class="content-card" style="max-width: 800px;">
            <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
            <h1>My Posts</h1>

            <?php if ($success_message): ?>
                <div class="ok"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <!-- Active Posts Section -->
            <div class="my-posts-section">
                <h2>Active Posts (<?= count($active_posts) ?>)</h2>
                <?php if (!empty($active_posts)): ?>
                    <?php foreach ($active_posts as $post): ?>
                        <div class="my-post-card">
                            <div class="my-post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                            <div class="my-post-content">
                                <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                                <p class="post-meta">Created on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                                <div class="my-post-actions">
                                    <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View</a>
                                    <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-secondary">Edit</a>
                                    <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You have no active posts.</p>
                        <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Your First Post</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Resolved Posts Section -->
            <?php if (!empty($resolved_posts)): ?>
                <div class="my-posts-section">
                    <h2>Resolved Posts (<?= count($resolved_posts) ?>)</h2>
                    <?php foreach ($resolved_posts as $post): ?>
                         <div class="my-post-card resolved">
                            <div class="my-post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                            <div class="my-post-content">
                                <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                                <p class="post-meta">Resolved on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                                <div class="my-post-actions">
                                    <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View</a>
                                    <span class="resolved-badge">RESOLVED</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
