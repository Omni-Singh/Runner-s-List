<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php');

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

$errors = [];

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $lost_date = trim($_POST['lost_date'] ?? '');

    // Validation
    if ($title === '') $errors['title'] = "Title is required.";
    if ($description === '') $errors['description'] = "Description is required.";
    if (!in_array($type, ['lost', 'found'])) $errors['type'] = "Please select a type.";

    if (empty($errors)) {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare(
                "INSERT INTO posts (user_id, type, title, description, location, lost_date) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$_SESSION['user_id'], $type, $title, $description, $location, $lost_date ?: null]);
            $post_id = $pdo->lastInsertId();

            // Handle image upload
            if ($post_id && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = ROOT_PATH . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $filename = 'post_' . $post_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                $db_path = '/uploads/' . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    $stmt = $pdo->prepare("INSERT INTO post_images (post_id, path) VALUES (?, ?)");
                    $stmt->execute([$post_id, $db_path]);
                }
            }
            header("Location: " . $basePath . "/dashboard.php?msg=" . urlencode("Post created successfully!"));
            exit;
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors['general'] = "An error occurred. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Post – <?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body>

<header class="dashboard-header">
    <a href="<?= $basePath ?>/index.php" class="logo">
        <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo">
        <span>RunnersList</span>
    </a>
    <div class="header-main-actions">
        <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary">+ Create Post</a>
        <a href="<?= $basePath ?>/my_posts.php" class="btn btn-secondary">My Posts</a>
    </div>
    <div class="search-container">
        <input type="search" placeholder="Search...">
    </div>

    <div class="header-user-actions">
        <a href="#" class="notification-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </a>
        <a href="<?= $basePath ?>/account.php" class="profile-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Account</span>
        </a>
        <a href="<?= $basePath ?>/logout.php" class="logout-icon">
             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>
</header>

    <main class="page-container">
        <div class="content-card" style="max-width: 600px;">
            <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
            <h1>Create Lost/Found Post</h1>

            <?php if (!empty($errors['general'])): ?><div class="err"><?= htmlspecialchars($errors['general']) ?></div><?php endif; ?>

            <div class="form-container">
                <form method="POST" action="post_create.php" enctype="multipart/form-data">
                    
                    <div>
                        <label for="type">Type *</label>
                        <div class="type-selector">
                            <div class="type-option" data-value="lost">Lost</div>
                            <div class="type-option" data-value="found">Found</div>
                        </div>
                        <input type="hidden" name="type" id="type-input" required>
                        <?php if (!empty($errors['type'])): ?><div class="err-small"><?= $errors['type'] ?></div><?php endif; ?>
                    </div>

                    <div>
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" required>
                         <?php if (!empty($errors['title'])): ?><div class="err-small"><?= $errors['title'] ?></div><?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="description">Description *</label>
                        <textarea name="description" id="description" rows="5" required></textarea>
                    </div>

                    <div>
                        <label for="location">Location *</label>
                        <input type="text" name="location" id="location" required>
                    </div>

                    <div>
                        <label for="lost_date">Date (optional)</label>
                        <input type="date" name="lost_date" id="lost_date">
                    </div>

                    <div>
                        <label for="image">Image (optional)</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary">Create Post</button>

                </form>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelector = document.querySelector('.type-selector');
        const hiddenInput = document.getElementById('type-input');
        const options = document.querySelectorAll('.type-option');

        typeSelector.addEventListener('click', function(e) {
            if (e.target && e.target.matches('.type-option')) {
                const selectedValue = e.target.dataset.value;
                hiddenInput.value = selectedValue;

                // Update active class on buttons
                options.forEach(option => option.classList.remove('active'));
                e.target.classList.add('active');
            }
        });
    });
    </script>

</body>
</html>

