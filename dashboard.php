<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// Pagination settings
$postsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $postsPerPage;

$posts = [];
$totalPosts = 0;
$totalPages = 0;

try {
    $pdo = get_pdo_connection();
    
    // Get total count of active posts
    $countSql = "SELECT COUNT(*) as total FROM posts WHERE status = 'ACTIVE'";
    $countStmt = $pdo->query($countSql);
    $totalPosts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalPosts / $postsPerPage);
    
    // Get posts for current page
    $sql = "SELECT p.*, u.full_name,
                (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'ACTIVE'
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log($e->getMessage());
}

// Set page title
$pageTitle = "Dashboard";

// Include header
require_once('includes/header.php');
?>

<!-- Page content starts here -->
<div class="feed-container">
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : '/assets/placeholder.png') ?>');"></div>
                <div class="post-content">
                    <h3>[<?= strtoupper(htmlspecialchars($post['type'])) ?>] <?= htmlspecialchars($post['title']) ?></h3>
                    <p class="post-meta">Posted by <?= htmlspecialchars($post['full_name']) ?> on <?= date('M j, Y', strtotime($post['created_at'])) ?></p>
                    <p><?= htmlspecialchars(substr($post['description'], 0, 100)) ?>...</p>
                    <div class="post-actions">
                        <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn">View Details</a>
                        <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>#contact" class="btn btn-secondary">Message</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination (only show if more than one page) -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <!-- Previous button -->
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>" class="pagination-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Previous
                    </a>
                <?php endif; ?>
                
                <!-- Page numbers -->
                <div class="pagination-numbers">
                    <?php
                    // Show max 7 page numbers with current page in middle
                    $startPage = max(1, $currentPage - 3);
                    $endPage = min($totalPages, $currentPage + 3);
                    
                    // First page + ellipsis
                    if ($startPage > 1): ?>
                        <a href="?page=1" class="pagination-number">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page numbers -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>" class="pagination-number <?= $i == $currentPage ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Ellipsis + last page -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?>" class="pagination-number"><?= $totalPages ?></a>
                    <?php endif; ?>
                </div>
                
                <!-- Next button -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>" class="pagination-btn">
                        Next
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Results info -->
            <div class="pagination-info">
                Showing <?= $offset + 1 ?> - <?= min($offset + $postsPerPage, $totalPosts) ?> of <?= $totalPosts ?> posts
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <p>No posts found. Be the first to create one!</p>
            <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Your First Post</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>