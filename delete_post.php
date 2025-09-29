<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
  header("Location: my_posts.php");
  exit;
}

try {
  $pdo = get_pdo_connection();
  
  // Verify ownership
  $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ? LIMIT 1");
  $stmt->execute([$post_id, $_SESSION['user_id']]);
  $post = $stmt->fetch();
  
  if (!$post) {
    header("Location: my_posts.php?msg=" . urlencode("Post not found or you don't have permission."));
    exit;
  }
  
  // Block deletion if post is RESOLVED
  if ($post['status'] === 'RESOLVED') {
    header("Location: my_posts.php?msg=" . urlencode("Cannot delete resolved posts."));
    exit;
  }
  
  // Get images to delete files
  $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
  $stmt->execute([$post_id]);
  $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Delete the post 
  $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
  $stmt->execute([$post_id, $_SESSION['user_id']]);
  
  // Delete image files 
  foreach ($images as $img) {
  $filepath = '/home/stu/runnerslist/public_html' . $img['path'];
  if (file_exists($filepath)) {
    unlink($filepath);
  }
}
  
  header("Location: my_posts.php?msg=" . urlencode("Post deleted successfully."));
  exit;
  
} catch (Throwable $e) {
  header("Location: my_posts.php?msg=" . urlencode("Unable to delete post."));
  exit;
}