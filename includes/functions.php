<?php

/**
 * Creates and returns a PDO database connection object.
 * It uses the database credentials that are defined in your config.php file.
 */
function get_pdo_connection() {
    // Build the connection string (DSN) from the config constants
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    // Set options for how PDO will behave
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as an associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];

    try {
        // Attempt to create and return the new database connection object
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If the connection fails, log the real error and show a generic message
        error_log("Database Connection Error: " . $e->getMessage());
        die("Error: A database connection could not be established.");
    }
}

// Get unread message count for a user
function get_unread_message_count($user_id) {
    try {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE receiver_id = ? 
            AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return 0;
    }
}

// Format time ago /
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    
    return date('M j', $time);
}
