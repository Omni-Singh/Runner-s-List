<?php
require_once('includes/config.php');
require_once("includes/validators.php");
require_once('includes/functions.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $full_name   = trim($_POST['full_name'] ?? '');
    $student_id  = trim($_POST['student_id'] ?? '');
    $password    = (string)($_POST['password'] ?? '');
    $confirm     = (string)($_POST['confirm_password'] ?? '');

    // Validations
    if (!validate_csub_email($email)) {
        $error_message = "CSUB emails only.";
    } elseif (strlen($full_name) < 2) {
        $error_message = "Please enter your full name.";
    } elseif ($password !== $confirm) {
        $error_message = "Passwords do not match.";
    } elseif (!validate_password_strength($password)) {
        $error_message = "Password must be ≥ 8 chars and include upper, lower, and a number.";
    }

    if ($error_message === '') {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = "An account already exists for that email.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, full_name, student_id, verified) VALUES (?, ?, ?, ?, 1)"
                );
                $ins->execute([$email, $hash, $full_name, $student_id !== "" ? $student_id : null]);
                $success_message = "Account created — you can now <a href='login.php'>sign in</a>.";
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error_message = "Unable to create account right now.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sign Up – <?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
                <a href="<?= $basePath ?>/index.php">Home</a>
                <a href="<?= $basePath ?>/view_posts.php">Browse Items</a>
                <a href="<?= $basePath ?>/post_create.php">Report Item</a>
                <a href="<?= $basePath ?>/login.php" class="active">Account</a>
            </nav>
        </div>
    </header>

    <main class="page-container">
        <div class="content-card">
            <a href="<?= $basePath ?>/index.php" class="back-arrow">← Back to Home</a>
            <h1>Create Account</h1>

            <?php if ($error_message): ?><div class="err"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="ok"><?= $success_message ?></div><?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="signup.php" novalidate>
                    <label for="full_name">Full Name</label>
                    <input id="full_name" type="text" name="full_name" required>

                    <label for="email">CSUB Email</label>
                    <input id="email" type="email" name="email" placeholder="you@csub.edu" required>
                    <small id="emailHint" class="note" style="display:none;color:#b00020;">CSUB emails only.</small>

                    <label for="student_id">Student ID (optional)</label>
                    <input id="student_id" type="text" name="student_id">

                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                    <small id="passwordHint" class="note" style="color:#555;">At least 8 characters, uppercase, lowercase, number.</small>

                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" type="password" name="confirm_password" required>

                    <button id="submitBtn" type="submit" class="btn">Create Account</button>
                </form>
            </div>

            <p class="form-footer-link">
                Already have an account? <a href="<?= $basePath ?>/login.php">Login</a>
            </p>
        </div>
    </main>
    
    <script>
    // Your existing JavaScript validation code goes here
    (function () {
      const email = document.getElementById('email');
      const hint  = document.getElementById('emailHint');
      // ... the rest of your JS code ...
      document.addEventListener('DOMContentLoaded', check);
    })();
    </script>
</body>
</html>
