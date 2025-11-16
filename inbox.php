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
                 u.full_name AS sender_name, u.email AS sender_email, u.id AS sender_id
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

// CSRF for reply forms
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf'];

// Page title
$pageTitle = "Inbox";
require_once('includes/header.php');
?>

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
              Regarding Post: 
              <a href="<?= $basePath ?>/post_detail.php?id=<?= (int)$m['post_id'] ?>">
                <?= htmlspecialchars($m['post_title']) ?>
              </a>
            </div>
            <div class="message-body">
              <?= nl2br(htmlspecialchars($m['body'])) ?>
            </div>
          </div>
          
          <div class="message-actions">
            <button class="btn" type="button" onclick="toggleReply(<?= (int)$m['id'] ?>)">Reply</button>
          </div>

          <!-- Hidden reply form -->
          <div id="reply-form-<?= (int)$m['id'] ?>" class="reply-form" style="display:none; margin-top:1rem; border-top:1px solid #ddd; padding-top:1rem;">
            <form method="post" action="<?= $basePath ?>/reply_submit.php">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="hidden" name="post_id" value="<?= (int)$m['post_id'] ?>">
              <input type="hidden" name="to_user_id" value="<?= (int)$m['sender_id'] ?>">

              <label>Your Reply:</label>
              <textarea name="body" rows="3" required placeholder="Type your reply..."></textarea>

              <div style="margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">Send Reply</button>
                <button type="button" class="btn btn-secondary" onclick="toggleReply(<?= (int)$m['id'] ?>)">Cancel</button>
              </div>
            </form>
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

<script>
function toggleReply(id) {
  const form = document.getElementById('reply-form-' + id);
  if (form.style.display === 'none') {
    form.style.display = 'block';
  } else {
    form.style.display = 'none';
  }
}
</script>

<?php require_once('includes/footer.php'); ?>
