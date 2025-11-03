<?php
// This file assumes $basePath is already defined from config.php
// Include this file AFTER your PHP logic and AFTER setting $pageTitle variable
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= isset($pageTitle) ? $pageTitle . ' – ' : '' ?><?= PROJECT_NAME ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
</head>
<body class="dashboard-body">

<header class="dashboard-header">
    <!-- Logo and RunnersList Title -->
    <a href="<?= $basePath ?>/dashboard.php" class="logo">
        <img src="<?= $basePath ?>/assets/csub_logo.png" alt="CSUB Logo">
        <span>RunnersList</span>
    </a>
    
    <!-- Main Call-to-Action Buttons -->
    <div class="header-main-actions">
        <a href="<?= $basePath ?>/post_create.php" class="btn btn-primary nav-btn">+ Create Post</a>
        <a href="<?= $basePath ?>/my_posts.php" class="btn btn-secondary nav-btn">My Posts</a>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="GET" action="<?= $basePath ?>/dashboard.php">
        <input 
            type="search" 
            name="search" 
            placeholder="Search posts..." 
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
        >
    </form>
    </div>
    
    <!-- User Actions (Inbox, Account, Logout) -->
    <div class="header-actions">
        <!-- Inbox Icon -->
        <div class="notification-icon">
            <a href="<?= $basePath ?>/inbox.php" title="Inbox"> 
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </a>
        </div>

        <!-- Account Icon -->
        <div class="profile-icon">
            <a href="<?= $basePath ?>/account.php" title="Account">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Account</span>
            </a>
        </div>

        <!-- Logout Icon -->
        <div class="logout-icon">
            <a href="<?= $basePath ?>/logout.php" title="Logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<!-- Mobile Navigation (hidden on desktop) -->
<nav class="mobile-nav">
    <a href="<?= $basePath ?>/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
        <span>Home</span>
    </a>
    <a href="<?= $basePath ?>/post_create.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'post_create.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
        <span>Create</span>
    </a>
    <a href="<?= $basePath ?>/my_posts.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'my_posts.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
        <span>My Posts</span>
    </a>
    <a href="<?= $basePath ?>/account.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Account</span>
    </a>
</nav>

<main>