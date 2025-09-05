<?php
require 'connect.php';  // Your database connection
require '/var/www/vendor/autoload.php';  // Composer autoloader
use RobThree\Auth\TwoFactorAuth;

function verifyMFAToken($userId, $token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT mfa_secret FROM user_info WHERE id=?");
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();

    $tfa = new TwoFactorAuth('Login JM');
    return $tfa->verifyCode($secret, $token, 2);  // Allow a 2-window for codes to account for possible time drift
}

// // Example usage
// $userId = 1; // Example user ID
// $userToken = '123456'; // Token from user's authenticator app

if (verifyMFAToken($userId, $userToken)) {
    echo "MFA verification successful.";
} else {
    echo "MFA verification failed.";
}
?>
