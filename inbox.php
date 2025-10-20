<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

$messages = [];
try {
  $pdo = get_pdo_connection();
  $uid = (int)$_SESSION['user_id'];

  $sql = "SELECT m.id, m.post_id, m.body, m.created_at, 
                 p.title AS post_title, 
                 u.full_name AS sender_name, u.email AS sender_email
          FROM messages m
          JOIN posts p ON p.id = m.post_id
          JOIN users u ON u.id = m.sender_id
          WHERE m.receiver_id = ?
          ORDER BY m.created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$uid]);
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $_SESSION['inbox_last_seen'] = date('Y-m-d H:i:s');
} catch (Throwable $e) {
  error_log($e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Inbox – <?= PROJECT_NAME ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
  <style>
    .message-card { border:1px solid #ddd; border-radius:8px; padding:12px; margin-bottom:12px; }
    .message-title { font-weight:600; }
    .message-meta  { color:#666; font-size:.9rem; margin-top:4px; }
    .message-body  { white-space:pre-wrap; margin-top:8px; }
    .back-arrow { text-decoration:none; display:inline-block; margin-bottom:8px; }
  </style>
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
            <h1>Inbox</h1>

            <div class="message-list">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="message-card <?= !$m['is_read'] ? 'unread' : '' ?>">
                            <div class="message-sender">
                                <strong>From:</strong> <?= htmlspecialchars($m['sender_name']) ?>
                            </div>
                            <div class="message-content">
                                <p class="message-post-link">
                                    <strong>Regarding Post:</strong> 
                                    <a href="<?= $basePath ?>/post_detail.php?id=<?= (int)$m['post_id'] ?>"><?= htmlspecialchars($m['post_title']) ?></a>
                                </p>
                                <p class="message-body">
                                    <?= nl2br(htmlspecialchars(substr($m['body'], 0, 200))) ?>
                                    <?= strlen($m['body']) > 200 ? '...' : '' ?>
                                </p>
                            </div>
                            <div class="message-meta">
                                <span><?= date('M j, Y g:i a', strtotime($m['created_at'])) ?></span>
                                <a href="#" class="btn btn-secondary">Reply</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You have no messages in your inbox.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>