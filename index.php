<?php
require_once('includes/config.php');
require_once('includes/functions.php');

// If user is already logged in, redirect them to the dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

// --- Fetch recent items (currently disabled) ---
// This section is when there are posts in the database.
/*
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare(
        "SELECT p.title, p.location, p.lost_date, 
         (SELECT path FROM post_images WHERE post_id = p.id LIMIT 1) as image_path
         FROM posts p WHERE p.type = 'found' ORDER BY p.created_at DESC LIMIT 5"
    );
    $stmt->execute();
    $recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recent_items = []; // On error, default to an empty array
}
*/
$recent_items = []; // Keep this line to ensure the page loads without database items.
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome – <?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    
    <!-- Google Fonts Import for the new design -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body>

    <header class="landing-header">
        <div class="header-content">
            <a href="<?= $basePath ?>/index.php" class="logo">
                <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo">
                <span>RunnersList</span>
            </a>
            <nav class="main-nav">
                <a href="<?= $basePath ?>/index.php" class="active">Home</a>
                <a href="<?= $basePath ?>/view_posts.php">Browse Items</a>
                <a href="<?= $basePath ?>/post_create.php">Report Item</a>
                <a href="<?= $basePath ?>/login.php">Account</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Lost Something? <br> Found Something?</h1>
                    <p>The CSUB Lost & Found. Search for items or report what you've found to help fellow Roadrunners.</p>
                    <div class="hero-actions">
                        <a href="<?= $basePath ?>/post_create.php?type=lost" class="btn btn-primary">Report a Lost Item</a>
                        <a href="<?= $basePath ?>/post_create.php?type=found" class="btn btn-secondary">Report a Found Item</a>
                    </div>
                </div>
                <div class="hero-graphic">
                    <!-- This div uses a background image from CSS -->
                </div>
            </div>
        </section>

        <section class="how-it-works">
            <h2>How It Works</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </div>
                    <h3>1. Search & Browse</h3>
                </div>
                <div class="step">
                    <div class="step-icon">
                         <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16"><path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/><path d="M4.5 3.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/></svg>
                    </div>
                    <h3>2. Report Your Item</h3>
                </div>
                <div class="step">
    <div class="step-icon">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="48" height="48" fill="currentColor">
      <path d="M300.9 149.2L184.3 278.8C179.7 283.9 179.9 291.8 184.8 296.7C215.3 327.2 264.8 327.2 295.3 296.7L327.1 264.9C331.3 260.7 336.6 258.4 342 258C348.8 257.4 355.8 259.7 361 264.9L537.6 440L608 384L608 96L496 160L472.2 144.1C456.4 133.6 437.9 128 418.9 128L348.5 128C347.4 128 346.2 128 345.1 128.1C328.2 129 312.3 136.6 300.9 149.2zM148.6 246.7L255.4 128L215.8 128C190.3 128 165.9 138.1 147.9 156.1L144 160L32 96L32 384L188.4 514.3C211.4 533.5 240.4 544 270.3 544L286 544L279 537C269.6 527.6 269.6 512.4 279 503.1C288.4 493.8 303.6 493.7 312.9 503.1L353.9 544.1L362.9 544.1C382 544.1 400.7 539.8 417.7 531.8L391 505C381.6 495.6 381.6 480.4 391 471.1C400.4 461.8 415.6 461.7 424.9 471.1L456.9 503.1L474.4 485.6C483.3 476.7 485.9 463.8 482 452.5L344.1 315.7L329.2 330.6C279.9 379.9 200.1 379.9 150.8 330.6C127.8 307.6 126.9 270.7 148.6 246.6z"/>
        </svg>
    </div>
    <h3>3. Reunite!</h3>
</div>
            </div>
        </section>

        <section class="recent-items">
            <div class="recent-items-header">
                <h2>Recently Found Items</h2>
                <a href="<?= $basePath ?>/view_posts.php?type=found">View All &rarr;</a>
            </div>
            <div class="items-container">
                <?php if (!empty($recent_items)): ?>
                    <?php foreach ($recent_items as $item): ?>
                        <div class="item-card">
                            <div class="item-image" style="background-image: url('<?= $basePath . htmlspecialchars($item['image_path'] ?? '/assets/placeholder.png') ?>');"></div>
                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p><?= htmlspecialchars($item['location']) ?> &bull; <?= htmlspecialchars(date('m/d', strtotime($item['lost_date'] ?? $item['created_at']))) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No recently found items to display. Check the "Browse Items" page for all posts!</p>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= PROJECT_NAME ?> - CSUB Lost & Found. </p>
    </footer>

</body>
</html>

