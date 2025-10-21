<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php');

// --- Auth check ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// --- Minimal CSRF helper ---
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_token() { return $_SESSION['csrf'] ?? ''; }

$post_id = (int)($_GET['id'] ?? 0);
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

    $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $post = null;
}

if (!$post) {
    header("Location: " . $basePath . "/dashboard.php?msg=notfound");
    exit;
}

$is_owner      = ($post['user_id'] == $_SESSION['user_id']);
$prefill_name  = $_SESSION['name']  ?? '';
$prefill_email = $_SESSION['email'] ?? '';

// Notification badge count (based on last seen)
$unread_count = 0;
try {
    $last_seen = $_SESSION['inbox_last_seen'] ?? '1970-01-01 00:00:00';
    $q = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND created_at > ?");
    $q->execute([(int)$_SESSION['user_id'], $last_seen]);
    $unread_count = (int)$q->fetchColumn();
} catch (Throwable $e) {}

$ok  = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
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
    <div class="search-container">
        <input type="search" placeholder="Search...">
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
        <div class="post-detail-grid">
            <!-- Left Column: Post Details -->
            <div class="post-details-column">
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
                        <img src="<?= $basePath . htmlspecialchars($images[0]['path']) ?>" alt="Post image">
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
            </div>

            <!-- Right Column: Contact Form or Owner Actions -->
            <div id ="contact" class="contact-form-column">
                <?php if ($is_owner): ?>
                    <div class="owner-actions-card">
                        <h2>Your Post</h2>
                        <p>You are the owner of this post. You can edit or delete it.</p>
                        <div class="post-detail-actions">
                            <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-primary">Edit Post</a>
                            <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete Post</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-container contact-card">
                        <h2>Contact the Owner</h2>
                        
                        <?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
                        <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
                        
                        <p class="note">Describe unique details about the item to help the owner verify your claim.</p>
                        
                        <form method="post" action="<?= $basePath ?>/contact_submit.php">
                             <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                             <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                             <input type="hidden" name="to_user_id" value="<?= (int)$post['user_id'] ?>">

                            <div>
                                <label for="c_name">Your Name</label>
                                <input id="c_name" name="name" type="text" value="<?= htmlspecialchars($prefill_name) ?>" required>
                            </div>

                            <div>
                                <label for="c_email">Your Email</label>
                                <input id="c_email" name="email" type="email" value="<?= htmlspecialchars($prefill_email) ?>" required>
                            </div>

                            <div>
                                <label for="c_body">Message</label>
                                <textarea id="c_body" name="body" rows="5" required></textarea>
                            </div>

    <button type="submit" class="btn">Send Message</button>
</form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>

