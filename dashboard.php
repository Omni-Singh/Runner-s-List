<?php
require_once('includes/config.php');
require_once('includes/functions.php');

if (empty($_SESSION['user_id'])) {
  header("Location: " . $basePath . "/login.php");
  exit;
}

// Pagination settings
$postsPerPage = 12; 
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $postsPerPage;

$filter_type = $_GET['type'] ?? 'all'; 
$search_query = $_GET['search'] ?? '';

$posts = [];
$totalPosts = 0;
$totalPages = 0;

try {
  $pdo = get_pdo_connection();
  
  // Build WHERE clause based on filters
  $where_conditions = ["status = 'ACTIVE'"];
  $params = [];
  
  if ($filter_type !== 'all') {
    $where_conditions[] = "type = ?";
    $params[] = $filter_type;
  }
  
  if (!empty($search_query)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
  }
  
  $where_clause = implode(' AND ', $where_conditions);
  
  // Build ORDER BY clause
  $order_by = [];
  
  // Sort by date only
  $date_order = ($_GET['sort_date'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
  $order_by[] = "p.created_at {$date_order}";
  
  $order_clause = implode(', ', $order_by);
  
  // Get total count
  $countSql = "SELECT COUNT(*) as total FROM posts WHERE {$where_clause}";
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute($params);
  $totalPosts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
  $totalPages = ceil($totalPosts / $postsPerPage);
  
  // Get posts for current page
  $sql = "SELECT p.*, 
          (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
          FROM posts p
          WHERE {$where_clause}
          ORDER BY {$order_clause}
          LIMIT ? OFFSET ?";
  
  $stmt = $pdo->prepare($sql);
  $params[] = $postsPerPage;
  $params[] = $offset;
  $stmt->execute($params);
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  error_log($e->getMessage());
}

$pageTitle = "Dashboard";

require_once('includes/header.php');
?>

<div class="page-container">
    <div class="dashboard-container">
        <h1>Lost & Found Posts</h1>
        
        <div class="dashboard-search-section">
            <form method="get" action="<?= $basePath ?>/dashboard.php" class="search-filter-form">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search for lost or found items..." 
                        value="<?= htmlspecialchars($search_query) ?>"
                        class="dashboard-search-input"
                    >
                    <button type="submit" class="dashboard-search-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>

                <div class="filters-row">
                    <select name="type" class="filter-select">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="lost" <?= $filter_type === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $filter_type === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>

                    <select name="sort_date" class="filter-select">
                        <option value="desc" <?= ($_GET['sort_date'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Newest First</option>
                        <option value="asc" <?= ($_GET['sort_date'] ?? '') === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                    </select>

                    <button type="submit" class="apply-filters-btn">Search</button>

                    <?php if (!empty($search_query) || $filter_type !== 'all' || ($_GET['sort_date'] ?? 'desc') !== 'desc'): ?>
                        <a href="<?= $basePath ?>/dashboard.php" class="clear-filters-btn">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (!empty($search_query) || $filter_type !== 'all'): ?>
            <div class="results-count">
                Showing <?= $totalPosts ?> result<?= $totalPosts !== 1 ? 's' : '' ?>
                <?php if (!empty($search_query)): ?>
                    for "<?= htmlspecialchars($search_query) ?>"
                <?php endif; ?>
                <?php if ($filter_type !== 'all'): ?>
                    in <?= ucfirst($filter_type) ?> Items
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($posts)): ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post-grid-card">
                        <?php $placeholder = ($post['type'] === 'lost') ? '/assets/lost_placeholder.svg' : '/assets/found_placeholder.svg'; ?>
                        <div class="post-grid-image" style="background-image: url('<?= $basePath . ($post['image_path'] ? htmlspecialchars($post['image_path']) : $placeholder) ?>');"></div>
                        
                        <div class="post-grid-content">
                            <span class="post-type-label <?= htmlspecialchars($post['type']) ?>">
                                <?= strtoupper(htmlspecialchars($post['type'])) ?>
                            </span>
                            <h3><?= htmlspecialchars($post['title']) ?></h3>
                            
                            <?php if (!empty($post['description'])): ?>
                                <p class="post-description"><?= htmlspecialchars(substr($post['description'], 0, 100)) ?><?= strlen($post['description']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="post-meta-info">
                                <?php if (!empty($post['location'])): ?>
                                    <span class="post-location">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <?= htmlspecialchars($post['location']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="post-date"><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                            </div>
                            
                            <div class="post-grid-actions">
                                <a href="<?= $basePath ?>/post_detail.php?id=<?= $post['id'] ?>" class="btn btn-small btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <?php 
                $query_params = [];
                if (!empty($search_query)) $query_params[] = 'search=' . urlencode($search_query);
                if ($filter_type !== 'all') $query_params[] = 'type=' . $filter_type;
                if (!empty($_GET['sort_date'])) $query_params[] = 'sort_date=' . $_GET['sort_date'];
                $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                ?>
                
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?><?= $query_string ?>" class="pagination-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?= $i ?><?= $query_string ?>" class="pagination-number <?= $i == $currentPage ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= $query_string ?>" class="pagination-btn">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Showing <?= $offset + 1 ?> - <?= min($offset + $postsPerPage, $totalPosts) ?> of <?= $totalPosts ?> posts
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3; margin-bottom: 1rem;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <p>No posts found<?= !empty($search_query) ? ' matching your search' : '' ?>.</p>
                <?php if (!empty($search_query) || $filter_type !== 'all'): ?>
                    <a href="<?= $basePath ?>/dashboard.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>