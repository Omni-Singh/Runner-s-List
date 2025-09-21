<?php
require_once "config.php"; 

// Clear session array
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destroy session
session_destroy();

// Redirect (
header("Location: login.php?msg=" . urlencode("You’ve been logged out."));
exit;
