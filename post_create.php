<?php
session_start();
require_once "config.php";

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $lost_date = trim($_POST['lost_date'] ?? '');

  // Basic validation
  if ($title === '') $errors['title'] = "Title is required.";
  if ($description === '') $errors['description'] = "Description is required.";
  if ($location === '') $errors['location'] = "Location is required.";
  if ($type === '' || !in_array($type, ['lost', 'found'])) {
    $errors['type'] = "Please select Lost or Found.";
  }

  if (empty($errors)) {
    try {
      $pdo = get_pdo_connection();
      
      // Insert post
      $stmt = $pdo->prepare(
        "INSERT INTO posts (user_id, type, title, description, location, lost_date, status) 
         VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')"
      );

      $stmt->execute([
        $_SESSION['user_id'],
        $type,
        $title,
        $description,
        $location,
        $lost_date ?: null
      ]);

      $post_id = $pdo->lastInsertId();

      if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "/uploads/";
        if (!is_dir($upload_dir)) {
          mkdir($upload_dir, 0755, true);
        }
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_post_' . $post_id . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
          $stmt = $pdo->prepare("INSERT INTO post_images (post_id, path) VALUES (?, ?)");
          $stmt->execute([$post_id, $filename]);
        }
      }

      header("Location: dashboard.php?msg=" . urlencode("Post created successfully!"));
      exit;

      } catch (Throwable $e) {
      $errors['general'] = "Unable to create post. Please try again.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Post – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <div class="create-post-container">
    <a href="dashboard.php" class="back-arrow" aria-label="Back to Dashboard">&larr;</a>
    <h1>Create Lost/Found Post</h1>

    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form class="create-post-form" method="POST" enctype="multipart/form-data">
      <div>
        <label for="type">Type *</label>
        <select name="type" id="type" required>
          <option value="">Select...</option>
          <option value="lost" <?= ($_POST['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
          <option value="found" <?= ($_POST['type'] ?? '') === 'found' ? 'selected' : '' ?>>Found</option>
        </select>
        <?php if (isset($errors['type'])): ?><div class="error"><?= $errors['type'] ?></div><?php endif; ?>
      </div>
    
      <div>
        <label for="title">Title *</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required autofocus>
        <?php if (isset($errors['title'])): ?><div class="error"><?= $errors['title'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="description">Description *</label>
        <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="error"><?= $errors['description'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="location">Location *</label>
        <input type="text" name="location" id="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
        <?php if (isset($errors['location'])): ?><div class="error"><?= $errors['location'] ?></div><?php endif; ?>
      </div>

      <div>
        <label for="lost_date">Date (optional)</label>
        <input type="date" name="lost_date" id="lost_date" value="<?= htmlspecialchars($_POST['lost_date'] ?? '') ?>">
      </div>

      <div>
        <label for="image">Image (optional)</label>
        <input type="file" name="image" id="image">
      </div>

      <button type="submit">Create Post</button>
    </form>
  </div>
</body>
</html>
