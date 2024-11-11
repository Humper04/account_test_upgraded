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
    return preg_match('/^(\\+(\\d{1,3})\\s?)?(\\d{10,12})$|^0(\\d{9,11})$/', $phone);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['new_username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    if (!isValidEmail($email) && !empty($email)) {
        $message = "Invalid email format. Please enter a valid email address.";
    } else if (!isValidPhone($phone) && !empty($phone)) {
        $message = "Invalid phone number format. Please enter a valid phone number.";
    } else {
        $current_username = $_SESSION['username'];
        $update_successful = updateUserInfo($current_username, $new_username, $email, $phone, $password);
        if ($update_successful) {
            $_SESSION['username'] = $new_username; // Update session username
            $message = 'Information updated successfully.';
        } else {
            $message = 'Failed to update information or no changes were made.';
        }
    }
}

function updateUserInfo($current_username, $new_username, $email, $phone, $password)
{
    global $conn;

    try {
        // Build the update SQL query dynamically based on which fields are not empty
        $fields = [];
        $params = [];
        $types = '';

        if (!empty($new_username)) {
            $fields[] = "username = ?";
            $params[] = $new_username;
            $types .= 's';
        }

        if (!empty($email)) {
            $fields[] = "email = ?";
            $params[] = $email;
            $types .= 's';
        }

        if (!empty($phone)) {
            $fields[] = "phone = ?";
            $params[] = $phone;
            $types .= 's';
        }

        if (!empty($password)) {
            $fields[] = "password = ?";
            $params[] = $password;
            $types .= 's';
        }

        // If no fields to update, return true indicating no changes
        if (empty($fields)) {
            return true; // No updates to perform
        }

        // Construct the SQL query
        $sql = "UPDATE user_info SET " . implode(", ", $fields) . " WHERE username = ?";
        $params[] = $current_username; // Add current username to parameters
        $types .= 's';

        $stmt = runQuery($sql, $types, $params);
        return $stmt && $stmt->affected_rows > 0;
    } catch (Exception $e) {
        // Handle specific foreign key constraint error
        if (strpos($e->getMessage(), 'Cannot delete or update a parent row: a foreign key constraint fails') !== false) {
            echo "<script>alert('Error: The email is still associated with a password recovery process.');</script>";
        } else {
            // Log any other error message and display a generic error to the user
            error_log("Update user info error: " . $e->getMessage());
            echo "<script>alert('An error occurred while updating user information. Please try again later.');</script>";
        }
        return false; // Return false to indicate failure
    }
}

?>
<form method="post">
    New Username: <input type="text" name="new_username" value="<?= htmlspecialchars($_SESSION['username']) ?>"><br>
    Email: <input type="text" name="email"><br>
    Phone: <input type="text" name="phone"><br>
    Password: <input type="password" name="password"><br>
    <input type="submit" value="Update Information">
</form>
<div><?= htmlspecialchars($message) ?></div>
