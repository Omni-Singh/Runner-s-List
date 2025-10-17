<?php
require_once('includes/config.php');
require_once('includes/functions.php');
require_once("includes/validators.php");

if (empty($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login.php");
    exit;
}

$pdo = get_pdo_connection();
$uid = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_token() { return $_SESSION['csrf'] ?? ''; }
function verify_csrf_or_die($token) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $token ?? '')) {
        die("Invalid CSRF token");
    }
}
function flash($key, $msg = null) {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
    $m = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $m;
}

$stmt = $pdo->prepare("SELECT id, email, full_name, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header("Location: " . $basePath . "/logout.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    verify_csrf_or_die($_POST['csrf'] ?? '');

    try {
        if ($action === 'update_profile') {
            $newName  = trim($_POST['full_name'] ?? '');
            $newEmail = trim($_POST['email'] ?? '');

            if ($newName === '') {
                flash('error', 'Name is required.'); 
            } elseif (!validate_csub_email($newEmail)) {
                flash('error', 'Please use a valid CSUB email (@csub.edu).');
            } else {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                $check->execute([$newEmail, $uid]);
                if ($check->fetch()) {
                    flash('error', 'That email is already in use.');
                } else {
                    $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $upd->execute([$newName, $newEmail, $uid]);
                    $_SESSION['name']  = $newName;
                    $_SESSION['email'] = $newEmail;
                    flash('success', 'Profile updated successfully.');
                }
            }
            header("Location: " . $basePath . "/account.php"); exit;
        }

        if ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new     = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if (!password_verify($current, $user['password_hash'])) {
                flash('error', 'Your current password is incorrect.');
            } elseif ($new !== $confirm) {
                flash('error', 'New password and confirmation do not match.');
            } elseif (!validate_password_strength($new)) {
                flash('error', 'New password must be ≥ 8 chars and include upper, lower, and a digit.');
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $upd  = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $upd->execute([$hash, $uid]);
                flash('success', 'Password changed successfully.');
            }
            header("Location: " . $basePath . "/account.php"); exit;
        }

        if ($action === 'delete_account') {
            $confirmText = trim($_POST['confirm_text'] ?? '');
            if ($confirmText !== 'DELETE') {
                flash('error', 'Please type DELETE to confirm account removal.');
                header("Location: " . $basePath . "/account.php"); exit;
            }

            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$uid]);

            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            header("Location: " . $basePath . "/login.php?msg=" . urlencode("Your account has been deleted."));
            exit;
        }

    } catch (Throwable $e) {
        error_log($e->getMessage()); 
        flash('error', 'Something went wrong. Please try again.');
        header("Location: " . $basePath . "/account.php"); exit;
    }
}

$successMsg = flash('success');
$errorMsg   = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Account Settings – <?= PROJECT_NAME ?></title>
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
                <a href="<?= $basePath ?>/view_posts.php">Browse Items</a>
                <a href="<?= $basePath ?>/post_create.php">Report Item</a>
                <a href="<?= $basePath ?>/dashboard.php" class="active">Account</a>
            </nav>
        </div>
    </header>

    <main class="page-container">
        <div class="account-container">
            <a href="<?= $basePath ?>/dashboard.php" class="back-arrow">&larr; Back to Dashboard</a>
            <h1>Account Settings</h1>

            <?php if ($errorMsg): ?><div class="err"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
            <?php if ($successMsg): ?><div class="ok"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>

            <!-- Update Profile Card -->
            <div class="settings-card">
                <h2>Profile</h2>
                <form method="post" action="account.php" class="form-container">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_profile">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    <label for="email">Email (CSUB)</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    <button type="submit" class="btn">Save Changes</button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="settings-card">
                <h2>Change Password</h2>
                <form method="post" action="account.php" class="form-container">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_password">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required>
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" minlength="8" required>
                    <label for="confirm_password">Confirm New Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
                    <button type="submit" class="btn">Update Password</button>
                </form>
            </div>

            <!-- Delete Account Card -->
            <div class="settings-card danger-zone">
                <h2>Delete Your Account</h2>
                <p>This action is permanent and will remove all your data.</p>
                <form method="post" action="account.php" class="form-container">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_account">
                    <label for="confirm_text">To confirm, type <strong>DELETE</strong></label>
                    <input id="confirm_text" name="confirm_text" type="text" pattern="DELETE" required>
                    <button type="submit" class="btn btn-danger">Delete My Account</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
