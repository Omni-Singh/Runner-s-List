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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $full_name   = trim($_POST['full_name'] ?? '');
    $student_id  = trim($_POST['student_id'] ?? '');
    $password    = (string)($_POST['password'] ?? '');
    $confirm     = (string)($_POST['confirm_password'] ?? '');

    // Validations
    if (!validate_csub_email($email)) {
        $error_message = "Please use a valid CSUB email address (@csub.edu).";
    } elseif (strlen($full_name) < 2) {
        $error_message = "Please enter your full name (at least 2 characters).";
    } elseif ($password !== $confirm) {
        $error_message = "Passwords do not match.";
    } elseif (!validate_password_strength($password)) {
        $error_message = "Password must be at least 8 characters and include uppercase, lowercase, and a number.";
    }

    if ($error_message === '') {
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = "An account with this email already exists. Try logging in instead.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, full_name, student_id, verified) VALUES (?, ?, ?, ?, 1)"
                );
                $ins->execute([$email, $hash, $full_name, $student_id !== "" ? $student_id : null]);
                
                // Redirect to login with success message
                header("Location: " . $basePath . "/login.php?msg=" . urlencode("Account created successfully! Please sign in."));
                exit;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error_message = "Unable to create account right now. Please try again later.";
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
                <a href="<?= $basePath ?>/about.php">About</a>
                <a href="<?= $basePath ?>/signup.php" class="active">Sign Up</a>
                <a href="<?= $basePath ?>/login.php">Login</a>
            </nav>
        </div>
    </header>

    <main class="landing-body">
        <div class="content-card" style="max-width: 500px;">
            <a href="<?= $basePath ?>/index.php" class="back-arrow">&larr; Back to Home</a>
            <h1>Create Your Account</h1>
            <p style="text-align: center; color: #666; margin-bottom: 1.5rem;">Join RunnersList to post and find lost items</p>

            <?php if ($error_message): ?>
                <div class="err"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="ok"><?= $success_message ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="<?= $basePath ?>/signup.php" novalidate>
                    <label for="full_name">Full Name *</label>
                    <input 
                        id="full_name" 
                        type="text" 
                        name="full_name" 
                        placeholder="John Doe"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required
                        autofocus
                    >

                    <label for="email">CSUB Email *</label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        placeholder="you@csub.edu"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                    <small id="emailHint" style="display:none; color:#dc3545; font-size: 0.85rem; margin-top: -0.4rem;">⚠️ Please use a valid CSUB email (@csub.edu)</small>

                    <label for="student_id">Student ID (optional)</label>
                    <input 
                        id="student_id" 
                        type="text" 
                        name="student_id"
                        placeholder="e.g., 012345678"
                        value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>"
                    >

                    <label for="password">Password *</label>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        placeholder="At least 8 characters"
                        required
                    >
                    <small id="passwordHint" style="color:#555; font-size: 0.85rem; display: block; margin-top: -0.4rem;">
                        Must include: uppercase, lowercase, and number
                    </small>

                    <label for="confirm_password">Confirm Password *</label>
                    <input 
                        id="confirm_password" 
                        type="password" 
                        name="confirm_password"
                        placeholder="Re-enter password"
                        required
                    >

                    <button id="submitBtn" type="submit" class="btn btn-primary">Create Account</button>
                </form>
            </div>

            <p class="form-footer-link">
                Already have an account? <a href="<?= $basePath ?>/login.php" style="color: #003366; font-weight: 600;">Sign in here</a>
            </p>
        </div>
    </main>
    
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= PROJECT_NAME ?> - CSUB Lost & Found</p>
    </footer>
    
    <script>
    (function () {
        const emailInput = document.getElementById('email');
        const emailHint = document.getElementById('emailHint');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        // Email validation
        function checkEmail() {
            const email = emailInput.value.trim();
            if (email && !email.endsWith('@csub.edu')) {
                emailHint.style.display = 'block';
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                return false;
            } else if (email) {
                emailHint.style.display = 'none';
                emailInput.classList.add('valid');
                emailInput.classList.remove('invalid');
                return true;
            }
            emailHint.style.display = 'none';
            emailInput.classList.remove('valid', 'invalid');
            return true;
        }
        
        // Password strength validation
        function checkPassword() {
            const password = passwordInput.value;
            if (password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /[0-9]/.test(password)) {
                passwordInput.classList.add('valid');
                passwordInput.classList.remove('invalid');
                return true;
            } else if (password.length > 0) {
                passwordInput.classList.add('invalid');
                passwordInput.classList.remove('valid');
                return false;
            }
            passwordInput.classList.remove('valid', 'invalid');
            return false;
        }
        
        // Password match validation
        function checkConfirm() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            if (confirm && password === confirm) {
                confirmInput.classList.add('valid');
                confirmInput.classList.remove('invalid');
                return true;
            } else if (confirm) {
                confirmInput.classList.add('invalid');
                confirmInput.classList.remove('valid');
                return false;
            }
            confirmInput.classList.remove('valid', 'invalid');
            return false;
        }
        
        // Event listeners
        emailInput.addEventListener('input', checkEmail);
        emailInput.addEventListener('blur', checkEmail);
        passwordInput.addEventListener('input', checkPassword);
        passwordInput.addEventListener('blur', checkPassword);
        confirmInput.addEventListener('input', checkConfirm);
        confirmInput.addEventListener('blur', checkConfirm);
        
        // Check on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkEmail();
            checkPassword();
            checkConfirm();
        });
    })();
    </script>
</body>
</html>