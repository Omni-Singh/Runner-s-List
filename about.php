<?php
require_once('includes/config.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>About – Runnerslist</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="landing-body">
  <header>
    <div class="logo-container">
      <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo" class="logo">
    </div>
    <h1>About Runnerslist</h1>
  </header>

  <div class="content-card">
    <main>
      <h2>Our Mission</h2>
      <p>
        The <strong>Runnerslist</strong> platform helps students and staff easily report lost or found
        items on campus. Our goal is to simplify reconnecting owners with their belongings in a quick,
        reliable, and secure way.
      </p>

      <hr>

      <h2>How It Works</h2>
      <ol>
        <li><strong>Post an Item:</strong> Quickly create a post for a lost or found item with details and a location.</li>
        <li><strong>Get Notified:</strong> Our system alerts you if a matching item is posted.</li>
        <li><strong>Connect Securely:</strong> Use our platform to arrange a safe and easy return.</li>
      </ol>
    </main>

    <footer class="card-footer">
      <a href="<?= $basePath ?>/index.php" class="btn">Back to Home</a>
    </footer>
  </div>
</body>
</html>