<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

$success_message = $_GET['msg'] ?? '';

// Pagination settings
$postsPerPage = 12; 
$activeCurrentPage = isset($_GET['active_page']) ? max(1, (int)$_GET['active_page']) : 1;
$resolvedCurrentPage = isset($_GET['resolved_page']) ? max(1, (int)$_GET['resolved_page']) : 1;
$activeOffset = ($activeCurrentPage - 1) * $postsPerPage;
$resolvedOffset = ($resolvedCurrentPage - 1) * $postsPerPage;

$active_posts = [];
$resolved_posts = [];
$totalActivePosts = 0;
$totalResolvedPosts = 0;
$activeTotalPages = 0;
$resolvedTotalPages = 0;

try {
  $pdo = get_pdo_connection();
  $userId = $_SESSION['user_id'];
  
  // Get total count of active posts
  $countSql = "SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status = 'ACTIVE'";
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute([$userId]);
  $totalActivePosts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
  $activeTotalPages = ceil($totalActivePosts / $postsPerPage);
  
  // Get active posts for current page
  $stmt = $pdo->prepare(
    "SELECT p.*, 
     (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
     FROM posts p
     WHERE p.user_id = :user_id AND p.status = 'ACTIVE'
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset"
  );
  $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $activeOffset, PDO::PARAM_INT);
  $stmt->execute();
  $active_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get total count of resolved posts
  $countSql = "SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status = 'RESOLVED'";
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute([$userId]);
  $totalResolvedPosts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
  $resolvedTotalPages = ceil($totalResolvedPosts / $postsPerPage);

  // Get resolved posts for current page
  $stmt = $pdo->prepare(
    "SELECT p.*, 
     (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
     FROM posts p
     WHERE p.user_id = :user_id AND p.status = 'RESOLVED'
     ORDER BY p.updated_at DESC
     LIMIT :limit OFFSET :offset"
  );
  $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $resolvedOffset, PDO::PARAM_INT);
  $stmt->execute();
  $resolved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  error_log($e->getMessage());
}

$pageTitle = "My Posts";

require_once('includes/header.php');
?>

<div class="page-container">
    <div class="my-posts-container">
        <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
        <h1>My Posts</h1>

        <?php if ($success_message): ?>
            <div class="ok"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <div class="posts-tabs">
            <button class="tab-btn active" data-tab="active">
                Active Posts (<?= $totalActivePosts ?>)
            </button>
            <button class="tab-btn" data-tab="resolved">
                Resolved Posts (<?= $totalResolvedPosts ?>)
            </button>
        </div>

        <div class="tab-content active" id="active-tab">
            <?php if (!empty($active_posts)): ?>
                <div class="posts-grid">
                    <?php foreach ($active_posts as $post): ?>
                        <div class="post-grid-card">
                            <?php $placeholder = ($post['type'] === 'lost') ? '/assets/lost_placeholder.svg' : '/assets/found_placeholder.svg'; ?>
                            <div class="post-grid-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : $placeholder) ?>');"></div>
                            
                            <div class="post-grid-content">
                                <span class="post-type-label <?= htmlspecialchars($post['type']) ?>">
                                    <?= strtoupper(htmlspecialchars($post['type'])) ?>
                                </span>
                                <h3><?= htmlspecialchars($post['title']) ?></h3>
                                <p class="post-date">Created on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                                
                                <div class="post-grid-actions">
                                    <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn btn-small">View</a>
                                    <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-small btn-edit">Edit</a>
                                    <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($activeTotalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($activeCurrentPage > 1): ?>
                            <a href="?active_page=<?= $activeCurrentPage - 1 ?>" class="pagination-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $activeCurrentPage - 2);
                            $endPage = min($activeTotalPages, $activeCurrentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?active_page=<?= $i ?>" class="pagination-number <?= $i == $activeCurrentPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($activeCurrentPage < $activeTotalPages): ?>
                            <a href="?active_page=<?= $activeCurrentPage + 1 ?>" class="pagination-btn">
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Showing <?= $activeOffset + 1 ?> - <?= min($activeOffset + $postsPerPage, $totalActivePosts) ?> of <?= $totalActivePosts ?> posts
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <p>You have no active posts.</p>
                    <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Your First Post</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="resolved-tab">
            <?php if (!empty($resolved_posts)): ?>
                <div class="posts-grid">
                    <?php foreach ($resolved_posts as $post): ?>
                        <div class="post-grid-card resolved">
                            <?php $placeholder = ($post['type'] === 'lost') ? '/assets/lost_placeholder.svg' : '/assets/found_placeholder.svg'; ?>
                            <div class="post-grid-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : $placeholder) ?>');"></div>
                            
                            <div class="post-grid-content">
                                <span class="post-type-label <?= htmlspecialchars($post['type']) ?>">
                                    <?= strtoupper(htmlspecialchars($post['type'])) ?>
                                </span>
                                <span class="resolved-checkmark">✓ RESOLVED</span>
                                <h3><?= htmlspecialchars($post['title']) ?></h3>
                                <p class="post-date">Resolved on <?= date('M j, Y', strtotime($post['updated_at'] ?? $post['created_at'])) ?></p>
                                
                                <div class="post-grid-actions">
                                    <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn btn-small">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($resolvedTotalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($resolvedCurrentPage > 1): ?>
                            <a href="?resolved_page=<?= $resolvedCurrentPage - 1 ?>" class="pagination-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $resolvedCurrentPage - 2);
                            $endPage = min($resolvedTotalPages, $resolvedCurrentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?resolved_page=<?= $i ?>" class="pagination-number <?= $i == $resolvedCurrentPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($resolvedCurrentPage < $resolvedTotalPages): ?>
                            <a href="?resolved_page=<?= $resolvedCurrentPage + 1 ?>" class="pagination-btn">
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Showing <?= $resolvedOffset + 1 ?> - <?= min($resolvedOffset + $postsPerPage, $totalResolvedPosts) ?> of <?= $totalResolvedPosts ?> posts
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <p>You have no resolved posts yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
});
</script>

<?php require_once('includes/footer.php'); ?>