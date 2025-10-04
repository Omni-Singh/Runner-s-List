<?php
// Detect environment: local vs Artemis
$basePath = (strpos($_SERVER['HTTP_HOST'], 'artemis.cs.csubak.edu') !== false)
  ? '/~runnerslist'
  : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Contact – CSUB Lost & Found</title>
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="standard-page">
  <header class="page-header">
    <div class="logo-container">
      <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>Contact Us</h1>
  </header>

  <main class="contact-container">
    <p>If you have questions, need help claiming an item, or want to report a technical issue, contact us at:</p>
    <p><a href="mailto:runnerslist@csub.edu">runnerslist@csub.edu</a></p>
    <p>We’ll get back to you as soon as possible.</p>
  </main>

  <footer>
    <a href="<?= $basePath ?>/index.php">Back to Home</a>
  </footer>
</body>
</html>
