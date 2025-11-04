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