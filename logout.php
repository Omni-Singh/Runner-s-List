<?php
// 1. Correctly include the config file (this also starts the session)
require_once('includes/config.php');

// 2. Clear all data from the session array
$_SESSION = [];

// 3. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session data on the server
session_destroy();

// 5. Correctly redirect to the login page using the basePath
$logout_message = urlencode("You’ve been logged out.");
header("Location: " . $basePath . "/login.php?msg=" . $logout_message);
exit;