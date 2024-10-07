<?php
require '/var/www/vendor/autoload.php';  // Ensure Composer's autoloader is included
require 'connect.php';  // Your database connection settings

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = \Dotenv\Dotenv::createImmutable('/var/www/');
$dotenv->load();

// Check if the request is a POST request and contains a timezone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timezone'])) {
    $timezone = $_POST['timezone'];
    
    // Log or handle the received time zone (for debugging or future reference)
    // file_put_contents('/tmp/user_timezone.log', "Received Time Zone: $timezone\n", FILE_APPEND);

    // Create a DateTimeZone object with the user's time zone
    try {
        $userTimeZone = new DateTimeZone($timezone);
        $expires = new DateTime('now', $userTimeZone);
        $expires->add(new DateInterval('PT30M'));  // Token expires in 30 minutes

        // You can use this expiration time and perform additional operations here

        echo "Success: Expiration set to " . $expires->format('Y-m-d H:i:s T');
    } catch (Exception $e) {
        echo "Error: Invalid time zone provided.";
    }
} else {
    echo "Error: No time zone provided.";
}
?>
