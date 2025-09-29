<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$errors = [];
$post = null;
$post_id = (int)($_GET['id'] ?? 0);

// Load the post
try {
  $pdo = get_pdo_connection();
  $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ? LIMIT 1");
  $stmt->execute([$post_id, $_SESSION['user_id']]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$post) {
    header("Location: my_posts.php");
    exit;
  }
  
  // Get existing image
  $stmt = $pdo->prepare("SELECT path FROM post_images WHERE post_id = ? LIMIT 1");
  $stmt->execute([$post_id]);
  $existing_image = $stmt->fetch(PDO::FETCH_ASSOC);
  
} catch (Throwable $e) {
  header("Location: my_posts.php");
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $lost_date = trim($_POST['lost_date'] ?? '');
  $status = trim($_POST['status'] ?? 'ACTIVE');

  // Validation
  if ($title === '') $errors['title'] = "Title is required.";
  if ($description === '') $errors['description'] = "Description is required.";
  if ($location === '') $errors['location'] = "Location is required.";
  if ($type === '' || !in_array($type, ['lost', 'found'])) {
    $errors['type'] = "Please select Lost or Found.";
  }
  if (!in_array($status, ['ACTIVE', 'RESOLVED'])) {
    $status = 'ACTIVE';
  }

  // Keep current status - don't allow manual changes
  $status = $post['status'];

  if (empty($errors)) {
    try {
      // Update post
      $stmt = $pdo->prepare(
        "UPDATE posts 
         SET type = ?, title = ?, description = ?, location = ?, lost_date = ?, status = ?
         WHERE id = ? AND user_id = ?"
      );
      $stmt->execute([
        $type,
        $title,
        $description,
        $location,
        $lost_date ?: null,
        $status,
        $post_id,
        $_SESSION['user_id']
      ]);
      
      // Handle new image upload
      if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($existing_image) {
          $old_path = __DIR__ . $existing_image['path'];
          if (file_exists($old_path)) {
            unlink($old_path);
          }
          $pdo->prepare("DELETE FROM post_images WHERE post_id = ?")->execute([$post_id]);
        }
        
        $upload_dir = __DIR__ . '/uploads/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . $post_id . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
          $stmt = $pdo->prepare("INSERT INTO post_images (post_id, path) VALUES (?, ?)");
          $stmt->execute([$post_id, '/uploads/' . $filename]);
        }
      }
      
      header("Location: my_posts.php?msg=" . urlencode("Post updated successfully!"));
      exit;
      
    } catch (Throwable $e) {
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
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <div class="create-post-container">
    <a href="my_posts.php" class="back-arrow" aria-label="Back to My Posts">&larr;</a>
    <h1>Edit Post</h1>

    <?php if (isset($errors['general'])): ?>
      <div class="err"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form class="create-post-form" method="POST" enctype="multipart/form-data">
      <div>
        <label for="type">Type *</label>
        <select name="type" id="type" required>
          <option value="">Select...</option>
          <option value="lost" <?= ($post['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
          <option value="found" <?= ($post['type'] ?? '') === 'found' ? 'selected' : '' ?>>Found</option>
        </select>
        <?php if (isset($errors['type'])): ?><div class="error"><?= $errors['type'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="title">Title *</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
        <?php if (isset($errors['title'])): ?><div class="error"><?= $errors['title'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="description">Description *</label>
        <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="error"><?= $errors['description'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="location">Location *</label>
        <input type="text" name="location" id="location" value="<?= htmlspecialchars($post['location'] ?? '') ?>" required>
        <?php if (isset($errors['location'])): ?><div class="error"><?= $errors['location'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="lost_date">Date (optional)</label>
        <input type="date" name="lost_date" id="lost_date" value="<?= htmlspecialchars($post['lost_date'] ?? '') ?>">
      </div>

      <!-- Status will be automatically set to RESOLVED when both parties confirm -->

      <?php if ($existing_image): ?>
        <div>
          <label>Current Image</label>
          <img src="<?= htmlspecialchars($existing_image['path']) ?>" alt="Current post image" style="max-width: 200px; border-radius: 8px;">
        </div>
      <?php endif; ?>

      <div>
        <label for="image">Change Image (optional)</label>
        <input type="file" name="image" id="image" accept="image/*">
        <small style="color: #666;">Upload a new image to replace the current one</small>
      </div>

      <button type="submit">Update Post</button>
    </form>
  </div>
</body>
</html>