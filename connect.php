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

function runQuery($sql, $types = null, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Handle errors in preparation
        error_log("MySQL prepare error: " . $conn->error); // Log error to server's error log
        return false; // Return false to indicate failure
    }

    if ($types && $params) {
        if (!$stmt->bind_param($types, ...$params)) {
            // Handle binding errors
            error_log("MySQL bind_param error: " . $stmt->error);
            return false;
        }
    }

    if (!$stmt->execute()) {
        // Handle execution errors
        error_log("MySQL execute error: " . $stmt->error);
        return false;
    }

    // Fetch results if it's a SELECT query
    if (strpos($sql, 'SELECT') === 0) {
        $result = $stmt->get_result();
        $stmt->close(); // Close the statement after fetching results
        return $result;
    }

    // For INSERT, UPDATE, DELETE
    $stmt->close(); // Close the statement after execution
    return $stmt;
}

function getUserIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    $foundIPs = []; // Store found IPs for logging
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                $foundIPs[$key][] = $ip; // Log all IPs found
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }

    // Log all collected IP data for debugging
    file_put_contents('ip_debug.log', date('Y-m-d H:i:s') . " - IPs checked: " . json_encode($foundIPs) . "\n", FILE_APPEND);
    
    return 'UNKNOWN';
}
