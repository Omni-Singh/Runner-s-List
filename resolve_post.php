<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

// CSRF check
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(400);
    die("Invalid CSRF token");
}

$pdo        = get_pdo_connection();
$current_id = (int)$_SESSION['user_id'];
$post_id    = (int)($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

try {
    // load post
    $stmt = $pdo->prepare("SELECT id, user_id, status, title FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: " . $basePath . "/dashboard.php?err=" . urlencode("Post not found."));
        exit;
    }

    // If already not ACTIVE, nothing more to do
    if ($post['status'] !== 'ACTIVE') {
        header("Location: " . $basePath . "/post_detail.php?id={$post_id}&msg=" . urlencode("This post is already resolved or inactive."));
        exit;
    }

    $owner_id = (int)$post['user_id'];

    if ($current_id === $owner_id) {
        // owner presses Resolve button (mark post as RESOLVED)
        $upd = $pdo->prepare("UPDATE posts SET status = 'RESOLVED' WHERE id = ?");
        $upd->execute([$post_id]);

        header("Location: " . $basePath . "/post_detail.php?id={$post_id}&msg=" . urlencode("Post marked as resolved. It will no longer appear in the active feed."));
        exit;
    } else {
        //  someone else presses Resolve (send message to owner)
        $stmt2 = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmt2->execute([$current_id]);
        $sender = $stmt2->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'A user', 'email' => ''];

        $sender_name  = $sender['full_name'];
        $sender_email = $sender['email'];

        $body = "Resolution Notification:\n\n"
              . "{$sender_name} <{$sender_email}> indicated that this item/post appears resolved.\n"
              . "Post title: \"{$post['title']}\" (ID: {$post_id}).\n\n"
              . "If this is correct, please open the post and press Resolve to mark it as resolved.";

        $ins = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, body) VALUES (?, ?, ?, ?)");
        $ins->execute([$post_id, $current_id, $owner_id, $body]);

        header("Location: " . $basePath . "/post_detail.php?id={$post_id}&msg=" . urlencode("The owner has been notified that this post may be resolved."));
        exit;
    }

} catch (Throwable $e) {
    error_log($e->getMessage());
    header("Location: " . $basePath . "/post_detail.php?id={$post_id}&err=" . urlencode("Unable to resolve this post right now."));
    exit;
}
