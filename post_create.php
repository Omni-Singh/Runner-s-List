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
  $category = trim($_POST['category'] ?? '');
  $tags = trim($_POST['tags'] ?? '');

  // Basic validation
  if ($title === '') $errors['title'] = "Title is required.";
  if ($description === '') $errors['description'] = "Description is required.";
  if ($location === '') $errors['location'] = "Location is required.";

  if (empty($errors)) {
    // TODO: insert into database here
    $success = "Post created successfully (mock).";
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
        <label for="image">Image (optional)</label>
        <input type="file" name="image" id="image">
      </div>

      <div>
        <label for="category">Category (optional)</label>
        <select name="category" id="category">
          <option value="">Select...</option>
          <option value="Lost" <?= ($_POST['category'] ?? '') === 'Lost' ? 'selected' : '' ?>>Lost</option>
          <option value="Found" <?= ($_POST['category'] ?? '') === 'Found' ? 'selected' : '' ?>>Found</option>
        </select>
      </div>

      <div>
        <label for="tags">Tags (optional)</label>
        <input type="text" name="tags" id="tags" placeholder="e.g. keys, backpack" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
      </div>

      <button type="submit">Create Post</button>
    </form>
  </div>
</body>
</html>
