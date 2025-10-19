<?php
require_once('includes/config.php');
require_once('includes/functions.php');

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// --- Fetch Post Data ---
$post_id = (int)($_GET['id'] ?? 0);
$post = null;

if ($post_id <= 0) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

try {
    $pdo = get_pdo_connection();
    $sql = "SELECT p.*, u.full_name, u.email 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all images for this post
    $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log($e->getMessage());
}

// If post not found, redirect back
if (!$post) {
    header("Location: " . $basePath . "/dashboard.php?msg=notfound");
    exit;
}

$is_owner = ($post['user_id'] == $_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($post['title']) ?> – <?= PROJECT_NAME ?></title>
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
    
    <div class="header-actions">
        <div class="notification-icon">
            <a href="#"> 
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
        <div class="content-card" style="max-width: 700px;">
            <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
            
            <div class="post-detail-header">
                <span class="post-type-badge"><?= strtoupper(htmlspecialchars($post['type'])) ?></span>
                <h1><?= htmlspecialchars($post['title']) ?></h1>
                <p class="post-meta">
                    Posted by <strong><?= htmlspecialchars($post['full_name']) ?></strong> 
                    on <?= date('F j, Y', strtotime($post['created_at'])) ?>
                </p>
            </div>

            <?php if (!empty($images)): ?>
                <div class="post-detail-image">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= $basePath . htmlspecialchars($img['path']) ?>" alt="Post image">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="post-detail-section">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($post['description'])) ?></p>
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
                    <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-primary">Edit Post</a>
                    <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to delete this post?')">Delete Post</a>
                <?php else: ?>
                    <a href="#" class="btn btn-primary">Contact Owner</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>
