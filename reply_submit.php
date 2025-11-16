<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  http_response_code(400);
  die("Invalid CSRF token");
}

$from_user = (int)$_SESSION['user_id'];
$to_user   = (int)($_POST['to_user_id'] ?? 0);
$post_id   = (int)($_POST['post_id'] ?? 0);
$body      = trim($_POST['body'] ?? '');

if ($to_user <= 0 || $post_id <= 0 || $body === '') {
  header("Location: " . $basePath . "/inbox.php?err=Invalid reply data");
  exit;
}

try {
  $pdo = get_pdo_connection();

  // verify post exists (for safety)
  $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
  $stmt->execute([$post_id]);
  if (!$stmt->fetch()) {
    header("Location: " . $basePath . "/inbox.php?err=Post not found");
    exit;
  }

  // insert reply as new message (reverse direction)
  $insert = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, body) VALUES (?, ?, ?, ?)");
  $insert->execute([$post_id, $from_user, $to_user, $body]);

  header("Location: " . $basePath . "/inbox.php?msg=" . urlencode("Reply sent successfully!"));
  exit;
} catch (Throwable $e) {
  error_log($e->getMessage());
  header("Location: " . $basePath . "/inbox.php?err=" . urlencode("Could not send reply."));
  exit;
}
