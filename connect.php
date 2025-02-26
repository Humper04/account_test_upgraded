<?php

require_once '/var/www/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable('/var/www/');
$dotenv->load();

$host = 'localhost';  // Server IP address or hostname
$db = 'PPP4';         // Updated database name
$user = 'root';
$pass = $_ENV['DB_PASSWORD_ROOT'];
$charset = 'utf8mb4';

// Debug output - Uncomment if needed for debugging
/*
var_dump($pass);

if (!$pass) {
    echo "Error: Environment variable 'DB_PASSWORD_ROOT' not set or empty.";
    exit;
}

echo "Database password loaded successfully: " . htmlspecialchars($pass, ENT_QUOTES, 'UTF-8');
*/

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Success message - Uncomment if needed for debugging
// echo "Connected successfully!";

$conn->set_charset($charset);

function runQuery($sql, $types = null, ...$params) {
    global $conn;

    // Check if at least one parameter is provided
    if (empty($params)) {
        error_log("Error: At least one parameter is required.");
        return false;
    }

    // Check if $types and $params match in length
    if ($types && strlen($types) !== count($params)) {
        error_log("Parameter mismatch: Expected " . strlen($types) . " parameters, got " . count($params));
        return false;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("MySQL prepare error: " . $conn->error); // Log error to server's error log
        return false;
    }

    if ($types && $params) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("MySQL bind_param error: " . $stmt->error);
            return false;
        }
    }

    if (!$stmt->execute()) {
        error_log("MySQL execute error: " . $stmt->error);
        return false;
    }

    return $stmt;
}
