<?php
require_once('includes/config.php');
require_once('includes/functions.php');

// Check if AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
}

if (empty($_SESSION['user_id'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    header("Location: " . $basePath . "/login.php");
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    http_response_code(400);
    die("Invalid CSRF token");
}

$from_user = (int)$_SESSION['user_id'];
$to_user   = (int)($_POST['to_user_id'] ?? 0);
$post_id   = (int)($_POST['post_id'] ?? 0);
$body      = trim($_POST['body'] ?? '');

if ($to_user <= 0 || $post_id <= 0 || $body === '') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Invalid message data']);
        exit;
    }
    header("Location: " . $basePath . "/inbox.php?err=Invalid reply data");
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Verify post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }
        header("Location: " . $basePath . "/inbox.php?err=Post not found");
        exit;
    }
    
    // Insert message
    $insert = $pdo->prepare("INSERT INTO messages (post_id, sender_id, receiver_id, body, is_read) VALUES (?, ?, ?, ?, FALSE)");
    $insert->execute([$post_id, $from_user, $to_user, $body]);
    
    $message_id = $pdo->lastInsertId();
    
    if ($is_ajax) {
        echo json_encode([
            'success' => true,
            'message_id' => $message_id,
            'message' => 'Reply sent successfully'
        ]);
        exit;
    }
    
    header("Location: " . $basePath . "/inbox.php?msg=" . urlencode("Reply sent successfully!"));
    exit;
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Could not send reply']);
        exit;
    }
    
    header("Location: " . $basePath . "/inbox.php?err=" . urlencode("Could not send reply."));
    exit;
}