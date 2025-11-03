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

// Set page title
$pageTitle = "Inbox";

// Include header (this opens <html>, <head>, <body>, <header>, and <main>)
require_once('includes/header.php');
?>

<!-- Your page content starts here (already inside <main> tag) -->
<div class="feed-container" style="max-width: 900px; margin-top: 5rem;">
    <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
    <h1 style="text-align: center; margin-bottom: 2rem;">Inbox</h1>

    <div class="message-list">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $m): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div class="message-sender">
                            <strong><?= htmlspecialchars($m['sender_name']) ?></strong>
                            &lt;<?= htmlspecialchars($m['sender_email']) ?>&gt;
                        </div>
                        <div class="message-date">
                            <?= date('M j, Y g:i a', strtotime($m['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <div class="message-post-link">
                            Regarding Post: <a href="<?= $basePath ?>/post_detail.php?id=<?= (int)$m['post_id'] ?>"><?= htmlspecialchars($m['post_title']) ?></a>
                        </div>
                        <div class="message-body">
                            From: <?= htmlspecialchars($m['sender_name']) ?><br>
                            &lt;<?= htmlspecialchars($m['sender_email']) ?>&gt;<br><br>
                            <?= nl2br(htmlspecialchars($m['body'])) ?>
                        </div>
                    </div>
                    
                    <div class="message-actions">
                        <a href="<?= $basePath ?>/post_detail.php?id=<?= (int)$m['post_id'] ?>#contact" class="btn">Reply</a>
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

<?php require_once('includes/footer.php'); ?>