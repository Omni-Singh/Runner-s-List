<?php
require_once('includes/config.php');
require_once("includes/validators.php");
require_once('includes/functions.php');

// If user is already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/dashboard.php");
    exit;
}

$error_message = '';
$success_message = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    
    if (!validate_csub_email($email)) {
        $error_message = "Please use a valid CSUB email address (@csub.edu).";
    } elseif ($pass === '') {
        $error_message = "Password is required.";
    }
    
    if ($error_message === '') {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("SELECT id, email, password_hash, full_name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($pass, $user['password_hash'])) {
                $error_message = "Invalid email or password.";
            } else {
                // Set session variables
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['name']    = $user['full_name'];
                
                // Redirect to dashboard
                header("Location: " . $basePath . "/dashboard.php");
                exit;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error_message = "Unable to sign in right now. Please try again later.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
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
                <a href="<?= $basePath ?>/about.php">About</a>
                <a href="<?= $basePath ?>/signup.php">Sign Up</a>
                <a href="<?= $basePath ?>/login.php" class="active">Login</a>
            </nav>
        </div>
    </header>
    
    <main class="landing-body">
        <div class="content-card">
            <h1>Welcome Back</h1>
            <p style="text-align: center; color: #666; margin-bottom: 1.5rem;">Sign in to access your account</p>
            
            <?php if ($success_message): ?>
                <div class="ok"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="err"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="<?= $basePath ?>/login.php">
                    <label for="email">Email (CSUB only)</label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        placeholder="you@csub.edu" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required 
                        autofocus
                    >
                    
                    <label for="password">Password</label>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </form>
            </div>
            
            <p class="form-footer-link">
                Don't have an account? <a href="<?= $basePath ?>/signup.php" style="color: #003366; font-weight: 600;">Sign up here</a>
            </p>
        </div>
    </main>
    
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= PROJECT_NAME ?> - CSUB Lost & Found</p>
    </footer>
</body>
</html>