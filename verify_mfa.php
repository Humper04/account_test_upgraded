<?php
require 'connect.php';
require '/var/www/vendor/autoload.php';
use RobThree\Auth\TwoFactorAuth;

session_start(); // ✅ You forgot this before

// Ensure the user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo "You must be logged in to verify MFA.";
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

// Fetch user details including MFA secret
$stmt = $conn->prepare("SELECT username, email, mfa_secret FROM user_info WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit;
}

$tfa = new TwoFactorAuth('Login JM');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mfa_code'])) {
    $code = trim($_POST['mfa_code']);

    if (empty($user['mfa_secret'])) {
        echo "<p>MFA is not enabled for this user.</p>";
    } elseif ($tfa->verifyCode($user['mfa_secret'], $code, 2)) {
        echo "<p>MFA verification successful.</p>";
        // Optional: you can set a session flag here for MFA completion
        $_SESSION['mfa_verified'] = true;
    } else {
        echo "<p>Invalid MFA code.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify MFA</title>
</head>
<body>
    <h1>Verify MFA</h1>
    <p>Welcome, <?= htmlspecialchars($user['username']) ?></p>

    <?php if (!empty($user['mfa_secret'])): ?>
        <form method="post">
            <label for="mfa_code">Enter your MFA code:</label>
            <input type="text" id="mfa_code" name="mfa_code" required>
            <button type="submit">Verify</button>
        </form>
    <?php else: ?>
        <p>You do not have MFA set up. <a href="setup_mfa.php">Set it up here</a>.</p>
    <?php endif; ?>

    <p><a href="profile.php">Back to Profile</a></p>
</body>
</html>
