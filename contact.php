<?php
require_once('includes/config.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Contact – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="landing-body">
  <header>
    <div class="logo-container">
      <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>Contact Us</h1>
  </header>

  <div class="content-card">
    <main>
      <p>
        If you have questions, need help claiming an item, or want to report a technical issue,
        please send an email to:
      </p>
      <p class="contact-email">
        <a href="mailto:runnerslist@csub.edu">runnerslist@csub.edu</a>
      </p>
      <p>
        We’ll get back to you as soon as possible.
      </p>
    </main>

    <footer class="card-footer">
      <a href="<?= $basePath ?>/index.php" class="btn">Back to Home</a>
    </footer>
  </div>
</body>
</html>