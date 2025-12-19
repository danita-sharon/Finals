<?php
// --- Configuration (MUST BE UPDATED) ---
$host = 'localhost';
$db   = 'webtech_2025A_bridgetta_akoto';      // UPDATE this to your actual database name
$user = 'bridgetta.akoto';              // UPDATE this to your actual database user
$pass = 'Bri&moo000';          // UPDATE this to your actual database password
$charset = 'utf8mb4';
$lash_tech_email = 'sedemdoku12@example.com'; // UPDATE this email address for notifications
// ----------------------------------------

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Global PDO connection instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If the connection fails, log the error and prevent the application from running
    error_log("Database Connection Error: " . $e->getMessage());
    // Optionally, show a generic error to the user if this is a non-API page
    if (!defined('IS_API_CALL')) {
        die("<h1>Database connection failed. Please check configuration.</h1>");
    }
}
?>
