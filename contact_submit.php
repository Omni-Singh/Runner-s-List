<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php');
require_once('includes/text_validator.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  http_response_code(400);
  die("Invalid CSRF token");
}

$post_id   = (int)($_POST['post_id'] ?? 0);
$to_user   = (int)($_POST['to_user_id'] ?? 0);
$from_user = (int)$_SESSION['user_id'];

$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$body  = trim($_POST['body']  ?? '');

if ($post_id <= 0 || $to_user <= 0 || $from_user <= 0) {
  header("Location: " . $basePath . "/dashboard.php");
  exit;
}

$errors = [];
if ($name === '')  { $errors[] = "Name is required."; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Valid email is required."; }
if ($body === '')  { $errors[] = "Description is required."; }

// Text moderation for message body
if ($body !== '') {
  $body_check = validate_text_content($body, 'message');
  if ($body_check !== true) {
    $errors[] = $body_check;
  }
}

if (!empty($errors)) {
  $msg = urlencode(implode(' ', $errors));
  header("Location: " . $basePath . "/post_detail.php?id={$post_id}&err={$msg}");
  exit;
}

$composed = "From: {$name} <{$email}>\n\n{$body}";

try {
  $pdo = get_pdo_connection();

  $chk = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? LIMIT 1");
  $chk->execute([$post_id]);
  $row = $chk->fetch(PDO::FETCH_ASSOC);
  if (!$row || (int)$row['user_id'] !== $to_user) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
  }

  $ins = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, body) VALUES (?, ?, ?, ?)");
  $ins->execute([$post_id, $from_user, $to_user, $composed]);

  header("Location: " . $basePath . "/post_detail.php?id={$post_id}&msg=" . urlencode("Message sent to the owner."));
  exit;

} catch (Throwable $e) {
  error_log($e->getMessage());
  header("Location: " . $basePath . "/post_detail.php?id={$post_id}&err=" . urlencode("Could not send message."));
  exit;
}
