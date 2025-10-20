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
  <div class="container">
    <a href="dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
    <h1>Inbox</h1>

    <?php if ($messages): ?>
      <?php foreach ($messages as $m): ?>
        <div class="message-card">
          <div class="message-title">
            Post: <a href="<?= $basePath ?>/post_detail.php?id=<?= (int)$m['post_id'] ?>"><?= htmlspecialchars($m['post_title']) ?></a>
          </div>
          <div class="message-meta">
            From: <?= htmlspecialchars($m['sender_name']) ?> (<?= htmlspecialchars($m['sender_email']) ?>) • 
            <?= htmlspecialchars(date('M j, Y g:i a', strtotime($m['created_at']))) ?>
          </div>
          <div class="message-body"><?= nl2br(htmlspecialchars($m['body'])) ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="empty-state">No messages yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
