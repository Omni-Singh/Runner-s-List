<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

$success_message = $_GET['msg'] ?? '';

// Pagination settings
$postsPerPage = 10;
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

// Set page title
$pageTitle = "My Posts";

// Include header
require_once('includes/header.php');
?>

<!-- Page content starts here -->
<div class="page-container">
    <div class="content-card" style="max-width: 900px;">
        <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
        <h1>My Posts</h1>

        <?php if ($success_message): ?>
            <div class="ok"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <!-- Active Posts Section -->
        <div class="my-posts-section">
            <h2>Active Posts (<?= $totalActivePosts ?>)</h2>
            <?php if (!empty($active_posts)): ?>
                <?php foreach ($active_posts as $post): ?>
                    <div class="my-post-card">
                        <div class="my-post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                        <div class="my-post-content">
                            <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                            <p class="post-meta">Created on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                            <div class="my-post-actions">
                                <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View</a>
                                <a href="<?= $basePath ?>/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-secondary">Edit</a>
                                <a href="<?= $basePath ?>/delete_post.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Active Posts Pagination -->
                <?php if ($activeTotalPages > 1): ?>
                    <div class="pagination">
                        <!-- Previous button -->
                        <?php if ($activeCurrentPage > 1): ?>
                            <a href="?active_page=<?= $activeCurrentPage - 1 ?><?= isset($_GET['resolved_page']) ? '&resolved_page=' . $_GET['resolved_page'] : '' ?>" class="pagination-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <!-- Page numbers -->
                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $activeCurrentPage - 3);
                            $endPage = min($activeTotalPages, $activeCurrentPage + 3);
                            
                            if ($startPage > 1): ?>
                                <a href="?active_page=1<?= isset($_GET['resolved_page']) ? '&resolved_page=' . $_GET['resolved_page'] : '' ?>" class="pagination-number">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?active_page=<?= $i ?><?= isset($_GET['resolved_page']) ? '&resolved_page=' . $_GET['resolved_page'] : '' ?>" class="pagination-number <?= $i == $activeCurrentPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $activeTotalPages): ?>
                                <?php if ($endPage < $activeTotalPages - 1): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="?active_page=<?= $activeTotalPages ?><?= isset($_GET['resolved_page']) ? '&resolved_page=' . $_GET['resolved_page'] : '' ?>" class="pagination-number"><?= $activeTotalPages ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Next button -->
                        <?php if ($activeCurrentPage < $activeTotalPages): ?>
                            <a href="?active_page=<?= $activeCurrentPage + 1 ?><?= isset($_GET['resolved_page']) ? '&resolved_page=' . $_GET['resolved_page'] : '' ?>" class="pagination-btn">
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Showing <?= $activeOffset + 1 ?> - <?= min($activeOffset + $postsPerPage, $totalActivePosts) ?> of <?= $totalActivePosts ?> active posts
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <p>You have no active posts.</p>
                    <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Your First Post</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resolved Posts Section -->
        <?php if ($totalResolvedPosts > 0): ?>
            <div class="my-posts-section">
                <h2>Resolved Posts (<?= $totalResolvedPosts ?>)</h2>
                <?php foreach ($resolved_posts as $post): ?>
                    <div class="my-post-card resolved">
                        <div class="my-post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                        <div class="my-post-content">
                            <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                            <p class="post-meta">Resolved on <?= date('M j, Y', strtotime($post['updated_at'] ?? $post['created_at'])) ?></p>
                            <div class="my-post-actions">
                                <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View</a>
                                <span class="resolved-badge">✓ RESOLVED</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Resolved Posts Pagination -->
                <?php if ($resolvedTotalPages > 1): ?>
                    <div class="pagination">
                        <!-- Previous button -->
                        <?php if ($resolvedCurrentPage > 1): ?>
                            <a href="?resolved_page=<?= $resolvedCurrentPage - 1 ?><?= isset($_GET['active_page']) ? '&active_page=' . $_GET['active_page'] : '' ?>" class="pagination-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <!-- Page numbers -->
                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $resolvedCurrentPage - 3);
                            $endPage = min($resolvedTotalPages, $resolvedCurrentPage + 3);
                            
                            if ($startPage > 1): ?>
                                <a href="?resolved_page=1<?= isset($_GET['active_page']) ? '&active_page=' . $_GET['active_page'] : '' ?>" class="pagination-number">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?resolved_page=<?= $i ?><?= isset($_GET['active_page']) ? '&active_page=' . $_GET['active_page'] : '' ?>" class="pagination-number <?= $i == $resolvedCurrentPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $resolvedTotalPages): ?>
                                <?php if ($endPage < $resolvedTotalPages - 1): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="?resolved_page=<?= $resolvedTotalPages ?><?= isset($_GET['active_page']) ? '&active_page=' . $_GET['active_page'] : '' ?>" class="pagination-number"><?= $resolvedTotalPages ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Next button -->
                        <?php if ($resolvedCurrentPage < $resolvedTotalPages): ?>
                            <a href="?resolved_page=<?= $resolvedCurrentPage + 1 ?><?= isset($_GET['active_page']) ? '&active_page=' . $_GET['active_page'] : '' ?>" class="pagination-btn">
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Showing <?= $resolvedOffset + 1 ?> - <?= min($resolvedOffset + $postsPerPage, $totalResolvedPosts) ?> of <?= $totalResolvedPosts ?> resolved posts
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>