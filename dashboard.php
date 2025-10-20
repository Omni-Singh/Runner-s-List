<?php
require_once('includes/config.php');
require_once('includes/functions.php');

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// --- Fetch All Posts for the Feed ---
$posts = [];
try {
    $pdo = get_pdo_connection();
    $sql = "SELECT p.*, u.full_name,
                (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'ACTIVE'
            ORDER BY p.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log($e->getMessage());
}

// --- Notification Badge Count ---
$unread_count = 0;
try {
    $last_seen = $_SESSION['inbox_last_seen'] ?? '1970-01-01 00:00:00';
    $q = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND created_at > ?");
    $q->execute([ (int)$_SESSION['user_id'], $last_seen ]);
    $unread_count = (int)$q->fetchColumn();
} catch (Throwable $e) { /* ignore */ }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard – <?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
    <style>
      .badge {
        background:#e11d48;
        color:#fff;
        border-radius:999px;
        padding:2px 8px;
        font-size:.75rem;
        margin-left:4px;
      }
    </style>
</head>
<body class="dashboard-body">

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
        <!-- 🔔 Notification icon now links to inbox -->
        <div class="notification-icon">
            <a href="<?= $basePath ?>/inbox.php" aria-label="Go to Inbox">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?= $unread_count > 0 ? '<span class="badge">'.$unread_count.'</span>' : '' ?>
            </a>
        </div>

        <div class="profile-icon">
            <a href="<?= $basePath ?>/account.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Account</span>
            </a>
        </div>

        <div class="logout-icon">
            <a href="<?= $basePath ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<main class="feed-container">
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                <div class="post-content">
                    <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                    <p class="post-meta">Posted by <?= htmlspecialchars($post['full_name']) ?> on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                    <p><?= htmlspecialchars(substr($post['description'], 0, 100)) ?>...</p>
                    <div class="post-actions">
                        <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View Details</a>
                        <a href="#" class="btn btn-secondary">Message</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No posts found. Be the first to create one!</p>
    <?php endif; ?>
</main>

<nav class="mobile-nav">
    <!-- Mobile nav items... -->
</nav>

</body>
</html>
