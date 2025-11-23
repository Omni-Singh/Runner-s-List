<?php
require_once('../includes/config.php');
require_once('../includes/functions.php');

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$post_id = (int)($_GET['post_id'] ?? 0);
$other_user_id = (int)($_GET['other_user_id'] ?? 0);

if ($post_id <= 0 || $other_user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Get post information
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.description, pi.path AS image_path, p.status 
                       FROM posts p 
                       LEFT JOIN post_images pi ON pi.post_id = p.id 
                       WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    // Get other user information
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$other_user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Get all messages in this conversation
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, 
       CASE 
           WHEN m.body LIKE 'From:%' THEN SUBSTRING(m.body, LOCATE('\n', m.body) + 1)
           ELSE m.body 
       END AS body,
       m.created_at, m.is_read,
       u.full_name AS sender_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.post_id = ?
        AND ((m.sender_id = ? AND m.receiver_id = ?)
             OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$post_id, $uid, $other_user_id, $other_user_id, $uid]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read where current user is receiver
    $pdo->prepare("
        UPDATE messages 
        SET is_read = TRUE, read_at = NOW() 
        WHERE post_id = ? 
        AND receiver_id = ? 
        AND sender_id = ?
        AND is_read = FALSE
    ")->execute([$post_id, $uid, $other_user_id]);
    
    echo json_encode([
        'success' => true,
        'post' => $post,
        'other_user' => [
            'id' => $other_user['id'],
            'name' => $other_user['full_name'],
            'email' => $other_user['email']
        ],
        'messages' => $messages
    ]);
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}