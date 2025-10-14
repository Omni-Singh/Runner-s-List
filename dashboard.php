<?php
session_start();
require_once('includes/config.php');
require_once('includes/functions.php');

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
} catch (Throwable $e) {}
?>
<!doctype html>
<html lang="en">
<head>
   <meta charset="utf-8">
  <title>Dashboard – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body>
<main> 
  <header> 
    <h1>Dashboard</h1>

    <?php if ($success_message): ?>
      <div class="note" role="alert"><?=htmlspecialchars($success_message)?></div> 
    <?php endif; ?>

    <p>Welcome, <strong><?=htmlspecialchars($name)?></strong><br>
       <small><?=htmlspecialchars($email)?></small></p>
  </header> 

  <section class="cards" aria-label="User Stats"> 
    <div class="card">
      <div><strong>My Posts</strong></div>
      <div class="stat-value"><?= $my_posts ?></div>
    </div>
    <div class="card">
      <div><strong>My Messages</strong></div>
      <div class="stat-value"><?= $my_messages ?></div>
    </div>
  </section> 

  <nav class="actions" aria-label="Dashboard Navigation"> 
    <a href="post_create.php">+ Create Lost/Found Post</a>
    <a href="my_posts.php">View My Posts</a>
    <a href="view_posts.php">Community Feed</a> 
    <a href="inbox.php">Open Messages</a>
    <a href="account.php">Account Settings</a>
    <a href="logout.php">Log out</a>
  </nav> 
</main> 
</body>
</html>
