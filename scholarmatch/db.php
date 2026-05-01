<?php
/**
 * Database Connection
 * Uses DB credentials from config.php
 */
require_once __DIR__ . '/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character set to UTF-8
mysqli_set_charset($conn, "utf8mb4");
?>

