<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php');

//  Auth check ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// --- Minimal CSRF helper ---
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_token() { return $_SESSION['csrf'] ?? ''; }

$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

try {
    $pdo = get_pdo_connection();

    $sql = "SELECT p.*, u.full_name, u.email 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $post = null;
}

if (!$post) {
    header("Location: " . $basePath . "/dashboard.php?msg=" . urlencode("Post not found."));
    exit;
}

$is_owner      = ($post['user_id'] == $_SESSION['user_id']);
$prefill_name  = $_SESSION['name']  ?? '';
$prefill_email = $_SESSION['email'] ?? '';

$ok  = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Set page title
$pageTitle = htmlspecialchars($post['title']);

// Include header
require_once('includes/header.php');
?>

<!-- Page content starts here -->
<div class="page-container">
    <div class="post-detail-grid">
        <!-- Left Column: Post Details -->
        <div class="post-details-column">
            <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
            
            <div class="post-detail-header">
                <span class="post-type-badge"><?= strtoupper(htmlspecialchars($post['type'])) ?></span>
                <h1><?= htmlspecialchars($post['title']) ?></h1>
                <p class="post-meta">
                    Posted by <strong><?= htmlspecialchars($post['full_name']) ?></strong> 
                    on <?= date('F j, Y', strtotime($post['created_at'])) ?>
                </p>
            </div>

            <?php if (!empty($images)): ?>
                <div class="post-detail-image">
                    <img src="<?= $basePath . htmlspecialchars($images[0]['path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                </div>
            <?php else: ?>
                <?php 
                    $placeholder = ($post['type'] === 'lost') 
                        ? '/assets/lost_placeholder.svg' 
                        : '/assets/found_placeholder.svg'; 
                ?>
                <div class="post-detail-image">
                    <img src="<?= $basePath . $placeholder ?>" alt="..." style="opacity: 0.6;">
                </div>
            <?php endif; ?>

            <div class="post-detail-section">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($post['description'])) ?></p>
            </div>
            
            <div class="post-detail-section">
                <h3>Details</h3>
                <p><strong>Location:</strong> <?= htmlspecialchars($post['location']) ?></p>
                <?php if ($post['lost_date']): ?>
                    <p><strong>Date:</strong> <?= date('F j, Y', strtotime($post['lost_date'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Contact Form or Owner Actions -->
        <div id="contact" class="contact-form-column">
            <?php if ($is_owner): ?>
                <div class="owner-actions-card">
                    <h2>Your Post</h2>
                    <p>You are the owner of this post. You can edit or delete it below.</p>
                    <div class="post-detail-actions">
                        <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn">Edit Post</a>
                        <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">Delete Post</a>

                         <!-- Resolve button -->
                    <form method="post"
                          action="<?= $basePath ?>/resolve_post.php"
                          class="form-container"
                          style="margin-top: 1rem;"
                          onsubmit="return confirm('Are you sure you want to mark this post as resolved?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <button type="submit" class="btn btn-secondary">Resolve</button>
                    </form>

                        
                    </div>
                </div>
            <?php else: ?>
                <div class="contact-card">
                    <h2>Contact the Owner</h2>
                    
                    <?php if ($ok): ?>
                        <div class="ok"><?= htmlspecialchars($ok) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($err): ?>
                        <div class="err"><?= htmlspecialchars($err) ?></div>
                    <?php endif; ?>
                    
                    <p class="note">Describe unique details about the item to help the owner verify your claim.</p>
                    
                    <!-- Contact form -->
                    <form method="post" action="<?= $basePath ?>/contact_submit.php" class="form-container">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <input type="hidden" name="to_user_id" value="<?= (int)$post['user_id'] ?>">

                        <div>
                            <label for="c_name">Your Name</label>
                            <input id="c_name" name="name" type="text" value="<?= htmlspecialchars($prefill_name) ?>" required>
                        </div>

                        <div>
                            <label for="c_email">Your Email</label>
                            <input id="c_email" name="email" type="email" value="<?= htmlspecialchars($prefill_email) ?>" required>
                        </div>

                        <div>
                            <label for="c_body">Message</label>
                            <textarea id="c_body" name="body" rows="5" placeholder="Describe details about the item to verify your claim..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>

                    <!-- Resolve button -->
                    <form method="post"
                          action="<?= $basePath ?>/resolve_post.php"
                          class="form-container"
                          style="margin-top: 1rem;"
                          onsubmit="return confirm('Are you sure you want to mark this post as resolved?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <button type="submit" class="btn btn-secondary">Resolve</button>
                    </form>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
