<?php
// Correctly include required files
require_once('includes/config.php');
require_once('includes/functions.php');

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php"); // Use basePath for redirect
    exit;
}

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: " . $basePath . "/my_posts.php"); // Use basePath for redirect
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id, status FROM posts WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Post not found or you don't have permission."));
        exit;
    }
    
    // Block deletion if post is RESOLVED
    if ($post['status'] === 'RESOLVED') {
        header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Cannot delete resolved posts."));
        exit;
    }
    
    // Get images to delete files from the server
    $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // --- Universal File Deletion Logic ---
    foreach ($images as $img) {
        $filepath = ROOT_PATH . $img['path']; // Use ROOT_PATH constant
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    // Delete the post from the database (image records will be deleted by CASCADE)
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    
    header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Post deleted successfully."));
    exit;
    
} catch (Throwable $e) {
    // For debugging, you can log the error: error_log($e->getMessage());
    header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Unable to delete post."));
    exit;
}