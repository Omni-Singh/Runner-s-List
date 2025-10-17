<?php
require_once('includes/config.php');
require_once("includes/validators.php");
require_once('includes/functions.php');

$error_message = '';
$success_message = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if (!validate_csub_email($email)) {
        $error_message = "Invalid or non-CSUB email.";
    } elseif ($pass === '') {
        $error_message = "Password required.";
    }

    if ($error_message === '') {
        try {
            $pdo = get_pdo_connection();

            $stmt = $pdo->prepare("SELECT id, email, password_hash, full_name, verified FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($pass, $user['password_hash'])) {
                $error_message = "Invalid credentials.";
            } else {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['name']    = $user['full_name'];

                header("Location: dashboard.php");
                exit;
            }
        } catch (Throwable $e) {
            $error_message = "Unable to sign in right now.";
        }
    }
}
?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-g">
        <title>Login – <?= PROJECT_NAME ?></title>
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
                <h1>Login</h1>
                
                <?php if ($error_message): ?><div class="err"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

                <div class="form-container">
                    <form method="post" action="login.php">
                        <label for="email">Email (CSUB only)</label>
                        <input id="email" type="email" name="email" placeholder="you@csub.edu" required>

                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required>

                        <button type="submit" class="btn">Sign in</button>
                    </form>
                </div>

                <p class="form-footer-link">
                    Don't have an account? <a href="<?= $basePath ?>/signup.php">Sign up</a>
                </p>
            </div>
        </main>

    </body>
    </html>