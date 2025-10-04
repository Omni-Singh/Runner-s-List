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
  <title>About – CSUB Lost & Found</title>
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="standard-page">
  <header class="page-header">
    <div class="logo-container">
      <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>About CSUB Lost &amp; Found</h1>
  </header>

  <main class="about-container">
    <p>
      The <strong>CSUB Lost &amp; Found</strong> platform helps students and staff easily report lost or found
      items on campus. Our goal is to simplify reconnecting owners with their belongings in a quick,
      reliable, and secure way.
    </p>
  </main>

  <footer>
    <a href="<?= $basePath ?>/index.php">Back to Home</a>
  </footer>
</body>
</html>
