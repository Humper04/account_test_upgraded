<?php
require 'connect.php';
require '../vendor/autoload.php';  // Make sure the autoload path is correct
use RobThree\Auth\TwoFactorAuth;

session_start();
//ini_set('display_errors', 0);

// Ensure the user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo "You must be logged in to view this page.";
    exit;
}

// Fetch user details and MFA status
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT username, email, phone, mfa_secret, role FROM user_info WHERE id = ?");
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
    $tfa = new TwoFactorAuth('Login JM');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
</head>
<body>

<div class="profile-container">
    <h1>User Profile</h1>

    <div class="profile-info">
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
    </div>

    <div class="profile-actions">
        <?php if (empty($user['mfa_secret'])): ?>
            <a class="btn primary" href="setup_mfa.php">Setup MFA</a>
        <?php else: ?>
            <form method="post" class="mfa-form">
                <label for="mfa_code">Enter MFA code to remove MFA</label>
                <input type="text" id="mfa_code" name="mfa_code" required>
                <button type="submit" name="remove_mfa" class="btn danger">Remove MFA</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="profile-links">
        <a href="index.php">Home</a>
        <p><a href="modify_information.php">Modify Information</a></p>
        <p><a href='verify_mfa.php'>Verify MFA</a></p>
        <p><a href="logout.php" class="logout">Logout</a></p>
    </div>
</div>

</body>
</html>
