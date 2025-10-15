<?php
// Force error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Universal includes
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php'); // Assuming you have validation functions here

// --- Protected Page Logic ---
if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

// --- Initial Setup ---
$errors = [];
$post = null;
$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: " . $basePath . "/my_posts.php");
    exit;
}

// --- Load Existing Post Data ---
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        header("Location: " . $basePath . "/my_posts.php?msg=notfound");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, path FROM post_images WHERE post_id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $existing_image = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Throwable $e) {
    error_log($e->getMessage()); // Log error instead of exiting silently
    header("Location: " . $basePath . "/my_posts.php?msg=dberror");
    exit;
}

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $lost_date = trim($_POST['lost_date'] ?? '');
    
    // Validation
    if ($title === '') $errors['title'] = "Title is required.";
    if ($description === '') $errors['description'] = "Description is required.";
    if ($location === '') $errors['location'] = "Location is required.";
    if (!in_array($type, ['lost', 'found'])) $errors['type'] = "Please select a valid type.";

    if (empty($errors)) {
        try {
            // Update post in the database
            $stmt = $pdo->prepare(
                "UPDATE posts SET type = ?, title = ?, description = ?, location = ?, lost_date = ?
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$type, $title, $description, $location, $lost_date ?: null, $post_id, $_SESSION['user_id']]);
            
            // Handle new image upload
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image file and database record if one exists
                if ($existing_image) {
                    $old_filepath = ROOT_PATH . $existing_image['path']; // Use universal ROOT_PATH
                    if (file_exists($old_filepath)) {
                        unlink($old_filepath);
                    }
                    $pdo->prepare("DELETE FROM post_images WHERE id = ?")->execute([$existing_image['id']]);
                }
                
                // Process and save the new image
                $upload_dir = ROOT_PATH . '/uploads/'; // Use universal ROOT_PATH
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
            
            // Redirect on success
            header("Location: " . $basePath . "/my_posts.php?msg=" . urlencode("Post updated successfully!"));
            exit;
            
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors['general'] = "Unable to update post. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Post – Runnerslist</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="landing-body">
  <div class="content-card">
    <main>
      <a href="<?= $basePath ?>/my_posts.php" class="back-arrow">&larr; Back to My Posts</a>
      <h1>Edit Post</h1>

      <?php if (!empty($errors['general'])): ?><div class="err"><?= htmlspecialchars($errors['general']) ?></div><?php endif; ?>

      <div class="form-container">
        <form method="POST" enctype="multipart/form-data" action="<?= $basePath ?>/edit_post.php?id=<?= $post_id ?>">
            <div>
                <label for="type">Type *</label>
                <select name="type" id="type" required>
                    <option value="">Select...</option>
                    <option value="lost" <?= ($post['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="found" <?= ($post['type'] ?? '') === 'found' ? 'selected' : '' ?>>Found</option>
                </select>
                <?php if (!empty($errors['type'])): ?><div class="err-small"><?= $errors['type'] ?></div><?php endif; ?>
            </div>

            <div>
                <label for="title">Title *</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
                <?php if (!empty($errors['title'])): ?><div class="err-small"><?= $errors['title'] ?></div><?php endif; ?>
            </div>

            <div>
                <label for="description">Description *</label>
                <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
                <?php if (!empty($errors['description'])): ?><div class="err-small"><?= $errors['description'] ?></div><?php endif; ?>
            </div>

            <div>
                <label for="location">Location *</label>
                <input type="text" name="location" id="location" value="<?= htmlspecialchars($post['location'] ?? '') ?>" required>
                <?php if (!empty($errors['location'])): ?><div class="err-small"><?= $errors['location'] ?></div><?php endif; ?>
            </div>

            <div>
                <label for="lost_date">Date (optional)</label>
                <input type="date" name="lost_date" id="lost_date" value="<?= htmlspecialchars($post['lost_date'] ?? '') ?>">
            </div>

            <?php if ($existing_image): ?>
            <div>
                <label>Current Image</label>
                <img src="<?= $basePath . htmlspecialchars($existing_image['path']) ?>" alt="Current post image" style="max-width: 200px; border-radius: 8px;">
            </div>
            <?php endif; ?>

            <div>
                <label for="image">Change Image (optional)</label>
                <input type="file" name="image" id="image" accept="image/*">
                <small>Upload a new image to replace the current one.</small>
            </div>

            <button type="submit" class="btn">Update Post</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>