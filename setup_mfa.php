<?php
require 'connect.php';  // Ensure the path to connect.php is correct
require '/var/www/vendor/autoload.php';  // Ensure this path is correct

use RobThree\Auth\TwoFactorAuth;

session_start();

// Display errors for debugging; should be turned off in a production environment
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo "You must be logged in to set up MFA.";
    exit;
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    echo "Invalid User ID.";
    exit;
}

$tfa = new TwoFactorAuth('Login JM');
$secret = $_SESSION['mfa_secret'] ?? '';

// Generate a new secret and display QR if not already done
if (!$secret) {
    $secret = $tfa->createSecret();

    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri('Login JM', $secret);
}

// Check if the verification code is submitted
$verificationCode = $_POST['verificationCode'] ?? '';
$postedSecret = $_POST['secret'] ?? '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($verificationCode) && !empty($postedSecret)) {
    if ($tfa->verifyCode($postedSecret, $verificationCode, 2)) {  // 2 = window for code verification
        // Verification success
        // Save the secret to the database and session now
        $stmt = $conn->prepare("UPDATE user_info SET mfa_secret=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('si', $postedSecret, $userId);
            $stmt->execute();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
        $_SESSION['mfa_secret'] = $postedSecret;  // Store in session
        header("Location: profile.php"); // Redirect to profile page on successful verification
        exit;
    } else {
        // Verification failed
        echo "<p>Verification failed. Please try again.</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup MFA</title>
</head>
<body>
    <h1>Setup Multi-Factor Authentication</h1>
    <?php if (!empty($qrCodeUrl)): ?>
        <p>Scan this QR code with your MFA app to set up authentication:</p>
        <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="MFA QR Code">
        <p>If the QR-code does not work, input the following code into your 2FA app: <?= htmlspecialchars($secret) ?></p>
        <form method="post">
            <input type="hidden" name="secret" value="<?= htmlspecialchars($secret) ?>">
            <label for="verificationCode">Enter the code from the app:</label>
            <input type="text" id="verificationCode" name="verificationCode" required>
            <button type="submit">Verify</button>
        </form>
    <?php endif; ?>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>
