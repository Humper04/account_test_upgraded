<?php
require 'connect.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $registerWithMfa = isset($_POST['register_with_mfa']); // Check if MFA registration was requested

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (empty($username)) {
        $message = "Username is required.";
    } elseif (empty($email) && empty($phone_number)) {
        $message = "At least one contact method (email or phone) must be provided.";
    } else {
        $stmt = runQuery("SELECT * FROM user_info WHERE username = ? OR email = ? OR phone = ?", "sss", $username, $email, $phone_number);
        if ($stmt->get_result()->num_rows > 0) {
            $message = "Username, email and / or phone already in use.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = runQuery("INSERT INTO user_info (username, password, email, phone) VALUES (?, ?, ?, ?)", "ssss", $username, $hashed_password, $email, $phone_number);
            if ($stmt->affected_rows > 0) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $stmt->insert_id; // Assumes AUTO_INCREMENT on the ID field
                if ($registerWithMfa) {
                    header("Location: setup_mfa.php");
                } else {
                    header("Location: login.php");
                }
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>
    <div><?= htmlspecialchars($message) ?></div>
    <form method="post">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        Confirm Password: <input type="password" name="confirm_password" required><br>
        Email: <input type="text" name="email"><br>
        Phone Number: <input type="text" name="phone_number"><br>
        <input type="submit" value="Register">
        <input type="submit" name="register_with_mfa" value="Register with MFA">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
