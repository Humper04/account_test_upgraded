<?php
require '../vendor/autoload.php';  // Ensure Composer's autoloader is included
require 'connect.php';  // Your database connection settings

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = \Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

function sendPasswordReset($email) {
    global $conn;

    // Use the time zone stored in the session, or default to UTC if not set
    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : 'UTC';

    try {
        // Create a DateTime object in the user's time zone
        $userTimeZone = new DateTimeZone($timezone);
        $expires = new DateTime('now', $userTimeZone);  // User's local time
        $expires->add(new DateInterval('PT150M'));  // Add 30 minutes
        
        // Double-check the timezone and date to confirm it's applied correctly
        echo "Expiration time (user's time zone): " . $expires->format('Y-m-d H:i:s T') . "<br>";

        // Token generation
        $token = bin2hex(random_bytes(32));

        // Store token in the database with the correct expiration time
        $stmt = runQuery(
            "INSERT INTO password_recovery (email, token, expires) VALUES (?, ?, ?)",
            "sss",
            $email,
            $token,
            $expires->format('Y-m-d H:i:s')
        );


        if ($stmt) {
            // Send recovery email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  // Gmail's SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'julianmensink25@gmail.com';  // Your Gmail address
                $mail->Password = $_ENV['GOOGLE_APP_PASSWORD'];  // Your app password generated in Gmail
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('julianmensink25@gmail.com', 'Julian Mensink');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Recovery';
                $mail->Body    = "To reset your password, please click the following link: <a href='http://192.168.2.114:56/reset_password.php?token=$token'>Reset Password</a>";
                $mail->AltBody = "To reset your password, please click the following link: http://192.168.2.114:56/reset_password.php?token=$token";

                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Failed to send email to $email. Error: {$mail->ErrorInfo}");
                return false;
            }
        } else {
            error_log("Failed to store password reset token for $email");
            return false;
        }
    } catch (Exception $e) {
        echo "Error: Invalid time zone or failed to process request.";
        return false;
    }
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    if (sendPasswordReset($_POST['email'])) {
        echo "A password recovery link has been sent to your email address.";
    } else {
        echo "Failed to send password recovery email. Please try again.";
    }
} else {
    echo "Please provide an email address.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Recovery</title>
</head>
<body>
    <h1>Password Recovery</h1>
    <form method="post">
        Email: <input type="email" name="email" required><br>
        <input type="submit" value="Send Recovery Email">
    </form>
</body>
</html>
