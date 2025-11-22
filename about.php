<?php
require_once('includes/config.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>About – <?= PROJECT_NAME ?></title>
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
                <a href="<?= $basePath ?>/login.php">Browse Items</a>
                <a href="<?= $basePath ?>/login.php">Report Item</a>
                <a href="<?= $basePath ?>/login.php">Login</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="about-section">
            <div class="content-wrapper">
                <a href="<?= $basePath ?>/index.php" class="back-link">&larr; Back to Home</a>
                
                <h1>Our Mission</h1>
                <p class="mission-text">
                    The <strong>RunnersList</strong> platform helps students and staff easily report lost or found
                    items on campus. Our goal is to simplify reconnecting owners with their belongings in a quick,
                    reliable, and secure way.
                </p>

                <hr class="section-divider">

                <h2>How It Works</h2>
                <div class="how-it-works-list">
                    <div class="work-step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h3>Post an Item</h3>
                            <p>Quickly create a post for a lost or found item with details and a location.</p>
                        </div>
                    </div>
                    
                    <div class="work-step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h3>Get Notified</h3>
                            <p>Our system alerts you if a matching item is posted.</p>
                        </div>
                    </div>
                    
                    <div class="work-step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h3>Connect Securely</h3>
                            <p>Use our platform to arrange a safe and easy return.</p>
                        </div>
                    </div>
                </div>

                <div class="cta-section">
                    <h3>Ready to Get Started?</h3>
                    <div class="cta-buttons">
                        <a href="<?= $basePath ?>/signup.php" class="btn btn-primary">Create Account</a>
                        <a href="<?= $basePath ?>/login.php" class="btn btn-secondary">Sign In</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> RunnersList - CSUB Lost & Found</p>
        </div>
    </footer>

</body>
</html>