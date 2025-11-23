<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$conversations = [];
$unread_count = 0;

try {
    $pdo = get_pdo_connection();
    
    // Get all conversations grouped by post and other user
    $sql = "SELECT DISTINCT
            m.post_id,
            p.title AS post_title,
            p.description AS post_description,
            pi.path AS image_path,
            p.status AS post_status,
            IF(m.sender_id = ?, m.receiver_id, m.sender_id) AS other_user_id,
            u.full_name AS other_user_name,
            u.email AS other_user_email,
            (SELECT COUNT(*) FROM messages m2 
             WHERE m2.post_id = m.post_id 
             AND ((m2.sender_id = ? AND m2.receiver_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id))
                  OR (m2.receiver_id = ? AND m2.sender_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)))
            ) AS message_count,
            (SELECT COUNT(*) FROM messages m2 
             WHERE m2.post_id = m.post_id 
             AND m2.receiver_id = ?
             AND m2.sender_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
             AND m2.is_read = FALSE
            ) AS unread_count,
            (SELECT MAX(created_at) FROM messages m2
             WHERE m2.post_id = m.post_id
             AND ((m2.sender_id = ? AND m2.receiver_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id))
                  OR (m2.receiver_id = ? AND m2.sender_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)))
            ) AS last_message_time,
            (SELECT body FROM messages m2
             WHERE m2.post_id = m.post_id
             AND ((m2.sender_id = ? AND m2.receiver_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id))
                  OR (m2.receiver_id = ? AND m2.sender_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)))
             ORDER BY m2.created_at DESC
             LIMIT 1
            ) AS last_message
        FROM messages m
        JOIN posts p ON p.id = m.post_id
        LEFT JOIN post_images pi ON pi.post_id = p.id
        JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY m.post_id, other_user_id
        ORDER BY last_message_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid, $uid]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total unread
    foreach ($conversations as $conv) {
        $unread_count += (int)$conv['unread_count'];
    }
    
    } catch (Throwable $e) {
    error_log($e->getMessage());
    }

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf'];

$pageTitle = "Inbox";
require_once('includes/header.php');
?>

<div class="inbox-container" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
    <a href="<?= $basePath ?>/dashboard.php" class="back-arrow" style="display: inline-block; margin-bottom: 1rem; color: #003262; text-decoration: none;">&larr; Back to Dashboard</a>
    
    <div class="inbox-header" style="text-align: center; margin-bottom: 2rem;">
        <h1 style="display: inline-flex; align-items: center; gap: 0.5rem;">
            Inbox 
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </h1>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-success" style="padding: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 1rem;">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="conversations-grid">
        <?php if (!empty($conversations)): ?>
            <?php foreach ($conversations as $conv): ?>
                <div class="conversation-card <?= $conv['unread_count'] > 0 ? 'has-unread' : '' ?>" 
                     onclick="openChat(<?= (int)$conv['post_id'] ?>, <?= (int)$conv['other_user_id'] ?>)">
                    
                    <div class="conversation-left">
                        <?php if (!empty($conv['image_path'])): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($conv['image_path']) ?>" 
                                 alt="Post image" 
                                 class="conversation-thumbnail">
                        <?php else: ?>
                            <div class="conversation-thumbnail-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="conversation-middle">
                        <div class="conversation-post-title">
                            <?= htmlspecialchars($conv['post_title']) ?>
                            <?php if ($conv['post_status'] === 'RESOLVED'): ?>
                                <span class="status-badge resolved">Resolved</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="conversation-other-user">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($conv['other_user_name']) ?>
                        </div>
                        
                        <div class="conversation-preview">
                            <?= htmlspecialchars(substr($conv['last_message'], 0, 80)) ?>
                            <?= strlen($conv['last_message']) > 80 ? '...' : '' ?>
                        </div>
                    </div>

                    <div class="conversation-right">
                        <div class="conversation-time">
                            <?= time_ago($conv['last_message_time']) ?>
                        </div>
                        
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="unread-count-badge"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                        
                        <div class="conversation-message-count">
                            <?= $conv['message_count'] ?> message<?= $conv['message_count'] != 1 ? 's' : '' ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h2>No Messages Yet</h2>
                <p>Your inbox is empty. When someone contacts you about your posts, their messages will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<div id="chatModal" class="chat-modal" style="display: none;">
    <div class="chat-modal-content">
        <div class="chat-modal-header">
            <div class="chat-header-info">
                <h3 id="chatPostTitle">Loading...</h3>
                <p id="chatOtherUser"></p>
            </div>
            <button class="chat-close-btn" onclick="closeChat()">&times;</button>
        </div>
        
        <div class="chat-post-info" id="chatPostInfo" style="display: none;">
            <img id="chatPostImage" src="" alt="Post image" style="max-width: 150px; border-radius: 8px;">
            <div>
                <p id="chatPostDescription"></p>
                <a id="chatPostLink" href="#" target="_blank" class="btn-link">View Full Post</a>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">Loading messages...</div>
        </div>

        <div class="chat-input-area">
            <form id="chatReplyForm" onsubmit="sendMessage(event)">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="post_id" id="chatPostId">
                <input type="hidden" name="to_user_id" id="chatToUserId">
                
                <textarea name="body" 
                          id="chatMessageInput" 
                          placeholder="Type your message..." 
                          rows="3" 
                          onkeydown="handleKeyPress(event)"
                          required></textarea>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let currentPostId = null;
let currentOtherUserId = null;
let messageCheckInterval = null;

function openChat(postId, otherUserId) {
    currentPostId = postId;
    currentOtherUserId = otherUserId;
    
    document.getElementById('chatModal').style.display = 'flex';
    document.getElementById('chatPostId').value = postId;
    document.getElementById('chatToUserId').value = otherUserId;
    
    loadMessages();
    
    // Poll for new messages every 5 seconds
    if (messageCheckInterval) clearInterval(messageCheckInterval);
    messageCheckInterval = setInterval(loadMessages, 5000);
}

function closeChat() {
    document.getElementById('chatModal').style.display = 'none';
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
        messageCheckInterval = null;
    }
    // Reload page to update unread counts
    location.reload();
}

async function loadMessages() {
    try {
        const response = await fetch(`<?= $basePath ?>/api/get_messages.php?post_id=${currentPostId}&other_user_id=${currentOtherUserId}`);
        const data = await response.json();
        
        if (data.success) {
            // Update header info
            document.getElementById('chatPostTitle').textContent = data.post.title;
            document.getElementById('chatOtherUser').textContent = data.other_user.name;
            
            // Update post info
            const postInfo = document.getElementById('chatPostInfo');
            if (data.post.image_path) {
                document.getElementById('chatPostImage').src = '<?= $basePath ?>/' + data.post.image_path;
                postInfo.style.display = 'flex';
            }
            document.getElementById('chatPostDescription').textContent = data.post.description || 'No description';
            document.getElementById('chatPostLink').href = '<?= $basePath ?>/post_detail.php?id=' + currentPostId;
            
            // Display messages
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML = '';
            
            if (data.messages.length === 0) {
                messagesDiv.innerHTML = '<div class="chat-loading">No messages yet. Start the conversation!</div>';
            } else {
                data.messages.forEach(msg => {
                    const isSent = msg.sender_id == <?= $uid ?>;
                    const bubble = document.createElement('div');
                    bubble.className = 'message-bubble ' + (isSent ? 'sent' : 'received');
                    
                    bubble.innerHTML = `
                        <div class="message-content">${escapeHtml(msg.body)}</div>
                        <div class="message-meta">${formatMessageTime(msg.created_at)}</div>
                    `;
                    
                    messagesDiv.appendChild(bubble);
                });
                
                // Scroll to bottom
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

async function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('<?= $basePath ?>/reply_submit.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            form.reset();
            loadMessages();
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML.replace(/\n/g, '<br>');
}

function formatMessageTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('chatModal').style.display === 'flex') {
        closeChat();
    }
});

// Handle Enter key to send message 
// Shift+Enter adds new line

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault(); 
        
        document.getElementById('chatReplyForm').dispatchEvent(new Event('submit', {
            cancelable: true,
            bubbles: true
        }));
    }
}

</script>

<?php require_once('includes/footer.php'); ?>