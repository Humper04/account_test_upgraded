<?php
require 'connect.php';
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); // Redirect if not logged in
    exit();
}

$message = '';

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPhone($phone) {
    return preg_match('/^(\+(\d{1,3})\s?)?(\d{10,12})$|^0(\d{9,11})$/', $phone);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['new_username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check that at least one field is non-empty
    if (empty($new_username) && empty($email) && empty($phone) && empty($password)) {
        $message = "Please fill in at least one field to update.";
    } else {
        if (!isValidEmail($email) && !empty($email)) {
            $message = "Invalid email format. Please enter a valid email address.";
        } else if (!isValidPhone($phone) && !empty($phone)) {
            $message = "Invalid phone number format. Please enter a valid phone number.";
        } else {
            // Hash password if provided
            if (!empty($password)) {
                $password = password_hash($password, PASSWORD_DEFAULT);
            }

            $current_username = $_SESSION['username'];
            $update_successful = updateUserInfo($current_username, $new_username, $email, $phone, $password);
            if ($update_successful) {
                $_SESSION['username'] = $new_username ?: $_SESSION['username']; // Update session username if changed
                $message = 'Information updated successfully.';
            } else {
                $message = 'No changes were made.';
            }
        }
    }
}

function updateUserInfo($current_username, $new_username, $email, $phone, $password) {
    global $conn;

    // Build SQL query and parameters dynamically based on non-empty fields
    $sql = "UPDATE user_info SET ";
    $types = '';
    $params = [];

    if (!empty($new_username)) {
        $sql .= "username = ?, ";
        $types .= 's';
        $params[] = $new_username;
    }
    if (!empty($email)) {
        $sql .= "email = ?, ";
        $types .= 's';
        $params[] = $email;
    }
    if (!empty($phone)) {
        $sql .= "phone = ?, ";
        $types .= 's';
        $params[] = $phone;
    }
    if (!empty($password)) {
        $sql .= "password = ?, ";
        $types .= 's';
        $params[] = $password;
    }

    // Remove trailing comma and add WHERE clause
    $sql = rtrim($sql, ', ') . " WHERE username = ?";
    $types .= 's';
    $params[] = $current_username;

    // Run query
    $stmt = runQuery($sql, $types, ...$params);
    return $stmt && $stmt->affected_rows > 0;
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Modify Information</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="modify_information.css">
</head>
<body>

<div class="form-container">
    <h1>Modify Information</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="new_username">New Username</label>
            <input
                type="text"
                id="new_username"
                name="new_username"
                value="<?= htmlspecialchars($_SESSION['username']) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" id="email" name="email">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone">
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password">
        </div>

        <button type="submit" class="btn primary">Update Information</button>
    </form>

    <div class="links">
        <a href="profile.php">← Back to Profile</a>
    </div>
</div>

</body>
</html>

