<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/validators.php');
require_once('includes/image_validator.php');
require_once('includes/text_validator.php');

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

    // Text moderation using validator
    if ($title !== '') {
        $title_check = validate_text_content($title, 'title');
        if ($title_check !== true) {
            $errors['title'] = $title_check;
        }
    }

    if ($description !== '') {
        $description_check = validate_text_content($description, 'description');
        if ($description_check !== true) {
            $errors['description'] = $description_check;
        }
    }

    if ($location !== '') {
        $location_check = validate_text_content($location, 'location');
        if ($location_check !== true) {
            $errors['location'] = $location_check;
        }
    }
    
    // Image validation using validator
    if (!empty($_FILES['image']['name'])) {
        $validation_result = validate_and_moderate_image($_FILES['image']);
        if ($validation_result !== true) {
            $errors['image'] = $validation_result;
        }
    }

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
                $unique_id = uniqid('', true);
                $filename = 'post_' . $post_id . '_' . $unique_id . '.' . $ext;
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

// Set page title
$pageTitle = "Create Post";

// Include header
require_once('includes/header.php');
?>

<!-- Page content starts here -->
<div class="page-container">
    <div class="content-card" style="max-width: 600px;">
        <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
        <h1>Create Lost/Found Post</h1>

        <?php if (!empty($errors['general'])): ?>
            <div class="err"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="post_create.php" enctype="multipart/form-data">
                
                <div>
                    <label for="type">Type *</label>
                    <div class="type-selector">
                        <div class="type-option" data-value="lost">Lost</div>
                        <div class="type-option" data-value="found">Found</div>
                    </div>
                    <input type="hidden" name="type" id="type-input" required>
                    <?php if (!empty($errors['type'])): ?>
                        <div class="err-small"><?= $errors['type'] ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="title">Title *</label>
                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    <?php if (!empty($errors['title'])): ?>
                        <div class="err-small"><?= $errors['title'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <div class="err-small"><?= $errors['description'] ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="location">Location *</label>
                    <input type="text" name="location" id="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                    <?php if (!empty($errors['location'])): ?>
                        <div class="err-small"><?= $errors['location'] ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="lost_date">Date (optional)</label>
                    <input type="date" name="lost_date" id="lost_date" value="<?= htmlspecialchars($_POST['lost_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                </div>

                <div>
                    <label for="image">Image (optional)</label>
                    <input 
                        type="file" 
                        name="image" 
                        id="image" 
                        accept=".jpg,.jpeg,.png,.webp"
                        onchange="validateImage(this)"
                    >
                    <small style="color: #d32f2f; font-weight: 500;">⚠️ Only JPG, PNG, and WEBP allowed. Max 5MB. NO GIFs.</small>
                    <?php if (!empty($errors['image'])): ?>
                        <div class="err-small"><?= $errors['image'] ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Create Post</button>

            </form>
        </div>
    </div>
</div>

<script>
function validateImage(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    // Get file extension
    const fileName = file.name.toLowerCase();
    const extension = fileName.split('.').pop();
    
    // Check extension
    if (!allowedExtensions.includes(extension)) {
        alert('🚫 INVALID FILE TYPE!\n\n' +
              'File: ' + file.name + '\n' +
              'Type: .' + extension.toUpperCase() + '\n\n' +
              '✅ ALLOWED: JPG, PNG, WEBP only\n' +
              '❌ NOT ALLOWED: GIF, PDF, TXT, etc.');
        input.value = '';
        return false;
    }
    
    // Check file size
    if (file.size > maxSize) {
        alert('🚫 FILE TOO LARGE!\n\n' +
              'File: ' + file.name + '\n' +
              'Size: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB\n\n' +
              'Maximum allowed: 5 MB');
        input.value = '';
        return false;
    }
    
    // File is valid, show confirmation
    console.log('✅ Valid file selected: ' + file.name);
    return true;
}

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

<?php require_once('includes/footer.php'); ?>