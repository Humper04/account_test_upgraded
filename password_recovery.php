<?php
require 'connect.php';  // Assuming database connection settings are in connect.php
function sendPasswordReset($email) {
    global $pdo;  // Assuming $pdo is your PDO database connection instance

    $token = bin2hex(random_bytes(32));  // Secure token generation
    $expires = new DateTime('now');
    $expires->add(new DateInterval('PT30M'));  // Token expires in 30 minutes

    // Store token in database
    $stmt = $pdo->prepare("INSERT INTO password_recovery (email, token, expires) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expires->format('Y-m-d H:i:s')]);

    // Send recovery email
    $to = $email;
    $subject = 'Password Recovery';
    $message = "To reset your password, please click the following link: http://192.168.2.114:56/reset_password.php?token=$token";
    mail($to, $subject, $message);
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    sendPasswordReset($_POST['email']);
}
