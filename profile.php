<?php
require 'connect.php';
require '/var/www/vendor/autoload.php';  // Make sure the autoload path is correct
use RobThree\Auth\TwoFactorAuth;

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo "You must be logged in to view this page.";
    exit;
}

// Fetch user details and MFA status
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT username, email, mfa_secret FROM user_info WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit;
}

// Handle MFA removal request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_mfa']) && !empty($_POST['mfa_code'])) {
    $tfa = new TwoFactorAuth('PPP4');
    if ($tfa->verifyCode($user['mfa_secret'], $_POST['mfa_code'], 2)) {  // 2 = window for code verification
        // Code is correct, remove MFA
        $stmt = $conn->prepare("UPDATE user_info SET mfa_secret = NULL WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user['mfa_secret'] = NULL;  // Update current session data
        echo "<p>MFA has been successfully removed.</p>";
    } else {
        echo "<p>Incorrect MFA code.</p>";
    }
}

// Handle password recovery entries removal request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_password_recovery'])) {
    $email = $user['email']; // Use the user's email to delete entries
    $removeSql = "DELETE FROM password_recovery WHERE email = ?";
    $removeStmt = $conn->prepare($removeSql);
    $removeStmt->bind_param('s', $email);
    
    if ($removeStmt->execute()) {
        echo "<p>All password recovery entries for this email have been successfully removed.</p>";
    } else {
        echo "<p>Failed to remove password recovery entries: " . htmlspecialchars($conn->error) . "</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
</head>
<body>
    <h1>User Profile</h1>
    <p>Welcome, <?= htmlspecialchars($user['username']) ?></p>
    <p>Email: <?= htmlspecialchars($user['email']) ?></p>

    <?php if (empty($user['mfa_secret'])): ?>
        <!-- No MFA setup, provide setup button -->
        <p><a href="setup_mfa.php">Setup Multi-factor Authentication</a></p>
    <?php else: ?>
        <!-- MFA is set up, provide removal form -->
        <form method="post">
            <label for="mfa_code">Enter your MFA code to remove MFA:</label>
            <input type="text" id="mfa_code" name="mfa_code" required>
            <button type="submit" name="remove_mfa">Remove MFA</button>
        </form>
    <?php endif; ?>
    
    <!-- Button to remove all password recovery entries -->
    <form method="post">
        <button type="submit" name="remove_password_recovery" onclick="return confirm('Are you sure you want to remove all password recovery entries for this email?');">Remove All Password Recovery Entries</button>
    </form>

    <p><a href='modify_information.php'>Modify Information</a></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
