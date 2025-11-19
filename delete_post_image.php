<?php
require_once('includes/config.php');
require_once('includes/functions.php');

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// CSRF check for security
// Prevents malicious activity
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $basePath . "/my_posts.php");
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(400);
    die("Invalid CSRF token");
}

$post_id = (int)($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    header("Location: " . $basePath . "/my_posts.php");
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Post not found or you don't have permission."));
        exit;
    }
    
    // Get image to delete from server
    $stmt = $pdo->prepare("SELECT id, path FROM post_images WHERE post_id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Delete physical file 
        $filepath = __DIR__ . $image['path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM post_images WHERE id = ?");
        $stmt->execute([$image['id']]);
        
        header("Location: " . $basePath . "/edit_post.php?id={$post_id}&msg=" . urlencode("Image deleted successfully."));
    } else {
        header("Location: " . $basePath . "/edit_post.php?id={$post_id}&msg=" . urlencode("No image to delete."));
    }
    exit;
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    header("Location: " . $basePath . "/edit_post.php?id={$post_id}&msg=" . urlencode("Unable to delete image."));
    exit;
}