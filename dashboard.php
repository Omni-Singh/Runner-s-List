<?php
require_once "config.php"; 

// must be logged in
if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$success_message = $_GET['msg'] ?? '';

$name  = $_SESSION['name']  ?? 'User';
$email = $_SESSION['email'] ?? '';

// small stats for the user
$my_posts = 0;
$my_messages = 0;

try {
  $pdo = get_pdo_connection();

  // how many posts this user created
  $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM posts WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $my_posts = (int)($stmt->fetch()['c'] ?? 0);

  // how many messages they’re involved in (sent or received)
  $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM messages WHERE sender_id = ? OR receiver_id = ?");
  $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
  $my_messages = (int)($stmt->fetch()['c'] ?? 0);
} catch (Throwable $e) {
  
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    .cards { display:grid; gap:12px; grid-template-columns: 1fr 1fr; margin-top:16px; }
    .card  { background:#fff; border:1px solid #eee; border-radius:10px; padding:14px; text-align:center; }
    .actions { display:flex; flex-direction:column; gap:10px; margin-top:18px; }
    .actions a { display:block; text-align:center; padding:10px; border-radius:8px; background:#f1f5f9; }
    .actions a:hover { background:#e9edf3; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Dashboard</h1>

    <?php if ($success_message): ?>
      <div class="note"><?=htmlspecialchars($success_message)?></div>
    <?php endif; ?>

    <p>Welcome, <strong><?=htmlspecialchars($name)?></strong><br>
       <small><?=htmlspecialchars($email)?></small></p>

    <div class="cards">
      <div class="card">
        <div><strong>My Posts</strong></div>
        <div style="font-size:1.6rem; margin-top:6px;"><?= $my_posts ?></div>
      </div>
      <div class="card">
        <div><strong>My Messages</strong></div>
        <div style="font-size:1.6rem; margin-top:6px;"><?= $my_messages ?></div>
      </div>
    </div>

    <div class="actions">
      <!-- Hook these up later as we build pages -->
      <a href="post_create.php">+ Create Lost/Found Post</a>
      <a href="my_posts.php">View My Posts</a>
      <a href="inbox.php">Open Messages</a>
      <a href="account.php">Account Settings</a>
      <a href="logout.php">Log out</a>
    </div>
  </div>
</body>
</html>
