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

// Search and filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$sortOrder = $_GET['sort'] ?? 'newest';

$posts = [];
$totalPosts = 0;
$totalPages = 0;

try {
    $pdo = get_pdo_connection();
    
    // Build WHERE clause
    $whereConditions = ["p.status = 'ACTIVE'"];
    $params = [];

    // Add search condition
    if ($searchQuery !== '') {
        $searchParam = '%' . $searchQuery . '%';
        $whereConditions[] = "(p.title LIKE :search_title OR p.description LIKE :search_desc OR p.location LIKE :search_loc)";
        $params[':search_title'] = $searchParam;
        $params[':search_desc'] = $searchParam;
        $params[':search_loc'] = $searchParam;
    }
    
    // Add type filter
    if ($typeFilter !== '' && in_array($typeFilter, ['lost', 'found'])) {
        $whereConditions[] = "p.type = :type";
        $params[':type'] = $typeFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Determine sort order
    switch($sortOrder) {
        case 'oldest':
            $orderBy = 'p.created_at ASC';
            break;
        case 'title':
            $orderBy = 'p.title ASC';
            break;
        default:
            $orderBy = 'p.created_at DESC';
    }

    // Get total count of filtered posts
    $countSql = "SELECT COUNT(*) as total FROM posts p WHERE {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalPosts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalPosts / $postsPerPage);
    
    // Get posts for current page
    $sql = "SELECT p.*, u.full_name,
                (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);

    // Bind search parameter if exists
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

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
    <!-- Search and Filter Bar -->
    <div class="search-filter-bar" style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <form method="GET" action="<?= $basePath ?>/dashboard.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <!-- Search Input -->
            <div style="flex: 1; min-width: 250px;">
                <input 
                    type="search" 
                    name="search" 
                    placeholder="Search by title, description, or location..." 
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                >
            </div>
            
            <!-- Type Filter -->
            <select name="type" style="padding: 0.75rem 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; background: white;">
                <option value="">All Types</option>
                <option value="lost" <?= $typeFilter === 'lost' ? 'selected' : '' ?>>Lost Items</option>
                <option value="found" <?= $typeFilter === 'found' ? 'selected' : '' ?>>Found Items</option>
            </select>
            
            <!-- Sort Order -->
            <select name="sort" style="padding: 0.75rem 1rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; background: white;">
                <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                <option value="title" <?= $sortOrder === 'title' ? 'selected' : '' ?>>Title A-Z</option>
            </select>
            
            <!-- Search Button -->
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; white-space: nowrap;">
                Search
            </button>
            
            <!-- Clear Filters -->
            <?php if ($searchQuery !== '' || $typeFilter !== '' || $sortOrder !== 'newest'): ?>
                <a href="<?= $basePath ?>/dashboard.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; text-decoration: none; white-space: nowrap;">
                    Clear
                </a>
            <?php endif; ?>
        </form>
        
        <!-- Search Results Info -->
        <?php if ($searchQuery !== '' || $typeFilter !== ''): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; color: #666;">
                <?php if ($totalPosts > 0): ?>
                    Found <strong><?= $totalPosts ?></strong> result<?= $totalPosts !== 1 ? 's' : '' ?>
                    <?php if ($searchQuery !== ''): ?>
                        for "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                    <?php endif; ?>
                <?php else: ?>
                    <strong>No results found</strong>
                    <?php if ($searchQuery !== ''): ?>
                        for "<?= htmlspecialchars($searchQuery) ?>"
                    <?php endif; ?>
                    . Try different search terms or <a href="<?= $basePath ?>/dashboard.php" style="color: #003366;">clear filters</a>.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <?php $placeholder = ($post['type'] === 'lost') ? '/assets/lost_placeholder.svg' : '/assets/found_placeholder.svg'; ?>
                <div class="post-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : $placeholder) ?>');"></div>                <div class="post-content">
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
            <?php
            // Build query string for pagination
            $paginationParams = [];
            if ($searchQuery !== '') $paginationParams[] = 'search=' . urlencode($searchQuery);
            if ($typeFilter !== '') $paginationParams[] = 'type=' . urlencode($typeFilter);
            if ($sortOrder !== 'newest') $paginationParams[] = 'sort=' . urlencode($sortOrder);
            $queryString = !empty($paginationParams) ? '&' . implode('&', $paginationParams) : '';
            ?>

            <div class="pagination">
                <!-- Previous button -->
                <?php if ($currentPage > 1): ?>
                     <a href="?page=<?= $currentPage - 1 ?><?= $queryString ?>" class="pagination-btn">
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
                        <a href="?page=1<?= $queryString ?>" class="pagination-number">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page numbers -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?><?= $queryString ?>" class="pagination-number <?= $i == $currentPage ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Ellipsis + last page -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?><?= $queryString ?>" class="pagination-number"><?= $totalPages ?></a>
                    <?php endif; ?>
                </div>
                
                <!-- Next button -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= $queryString ?>" class="pagination-btn">
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