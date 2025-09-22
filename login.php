<?php
require_once("config.php");
require_once("includes/validators.php");

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
  <meta charset="utf-8">
  <title>Login – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container">
    <h1>Login</h1>

    <?php if ($error_message): ?>
      <div class="err"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
      <div class="note"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form id="loginForm" method="post" action="login.php" novalidate>
      <label for="email">Email (CSUB only)</label>
      <input id="email" type="email" name="email" placeholder="you@csub.edu" required>

      <label for="password">Password</label>
      <input id="password" type="password" name="password" minlength="8" required>

      <button type="submit">Sign in</button>
    </form>

    <p class="note">Don’t have an account? <a href="signup.php">Sign up</a></p>
  </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", function (e) {
      const emailField = document.getElementById("email");
      const passwordField = document.getElementById("password");
      let valid = true;

      if (!emailField.value.endsWith("@csub.edu")) {
        alert("Please use a CSUB email.");
        emailField.classList.add("invalid");
        valid = false;
      } else {
        emailField.classList.remove("invalid");
      }

      if (passwordField.value.length < 8) {
        alert("Password must be at least 8 characters.");
        passwordField.classList.add("invalid");
        valid = false;
      } else {
        passwordField.classList.remove("invalid");
      }

      if (!valid) e.preventDefault();
    });
  </script>
</body>
</html>