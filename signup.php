<?php
require_once("config.php");
require_once("includes/validators.php");

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $password   = (string)($_POST['password'] ?? '');
    $confirm    = (string)($_POST['confirm_password'] ?? '');

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

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = "An account already exists for that email.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $ins = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, full_name, student_id, verified)
                     VALUES (?, ?, ?, ?, 1)"
                );
                $ins->execute([$email, $hash, $full_name, $student_id !== "" ? $student_id : null]);

                $success_message = "Account created — you can now <a href='login.php'>sign in</a>.";
            }
        } catch (Throwable $e) {
            $error_message = "Unable to create account right now.";
            // Debug mode: show actual error (REMOVE BEFORE PUSHING)
            $error_message = "DEBUG: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign Up – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/~runnerslist/assets/style.css">
</head>
<body>
  <div class="container-narrow">
    <a href="login.php" class="back-arrow">←</a>
    <<h1 class="title">Create Account</h1>

    <?php if ($error_message): ?><div class="err"><?=htmlspecialchars($error_message)?></div><?php endif; ?>
    <?php if ($success_message): ?><div class="note"><?= $success_message ?></div><?php endif; ?>

    <form method="post" action="signup.php" novalidate>
      <label for="full_name">Full Name</label>
      <input id="full_name" type="text" name="full_name" required>

      <label for="email">CSUB Email</label>
      <input id="email" type="email" name="email" placeholder="you@csub.edu" required>

      <small id="emailHint" class="note" style="display:none;color:#b00020;">
        CSUB emails only. Use your you@csub.edu address.
      </small>

      <label for="student_id">Student ID (optional)</label>
      <input id="student_id" type="text" name="student_id">

      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
      <small id="passwordHint" class="note" style="color:#555;">At least 8 chars, uppercase, lowercase, number.</small>

      <label for="confirm_password">Confirm Password</label>
      <input id="confirm_password" type="password" name="confirm_password" required>

      <button id="submitBtn" type="submit">Create Account</button>
    </form>

    <p class="note">Already have an account? <a href="login.php">Login</a></p>
  </div>
  <script>
    (function () {
      const email = document.getElementById('email');
      const hint  = document.getElementById('emailHint');
      const pass = document.getElementById('password');
      const confirm = document.getElementById('confirm_password');
      const btn   = document.getElementById('submitBtn');
      const hintPass  = document.getElementById('passwordHint');

      function isCSUB(e) { 
        return /@csub\.edu$/i.test((e||'').trim()); 
      }
      function strong(p){
      if (!p) return false;
        return p.length >= 8 && /[a-z]/.test(p) && /[A-Z]/.test(p) && /\d/.test(p);
      }

      function check() {
        const emailOK = isCSUB(email.value);
        const passOK   = strong(pass.value);
        const matchOK  = confirm.value.length > 0 && pass.value === confirm.value;

        // email feedback
        hint.style.display = emailOK ? 'none' : 'block';
        email.classList.toggle('invalid', !emailOK && email.value.length > 0);
        email.classList.toggle('valid', emailOK);

        // password feedback
        if (pass.value.length === 0) {
            hintPass.textContent = "At least 8 chars, uppercase, lowercase, number.";
            hintPass.style.color = "#555";
        } else if (passOK) {
            hintPass.textContent = "Strong password ✔";
            hintPass.style.color = "green";
        } else {
            hintPass.textContent = "Weak password ✘";
            hintPass.style.color = "red";
        }
        pass.classList.toggle('invalid', pass.value.length > 0 && !passOK);
        pass.classList.toggle('valid', passOK);

        // Confirm feedback
        confirm.classList.toggle('invalid', confirm.value.length > 0 && !matchOK);
        confirm.classList.toggle('valid', matchOK);

        // enable submit only if everything is valid
        btn.disabled = !(emailOK && passOK && matchOK);
      }

      email.addEventListener('input', check);
      pass.addEventListener('input', check);
      confirm.addEventListener('input', check);
      document.addEventListener('DOMContentLoaded', check);
    })();
  </script>
</body>
</html>

