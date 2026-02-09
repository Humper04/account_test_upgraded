<?php
require 'connect.php';
require '../vendor/autoload.php';
use RobThree\Auth\TwoFactorAuth;

session_start();

$message = '';

// Capture time zone from POST data and store it in the session
if (isset($_POST['timezone'])) {
    $_SESSION['timezone'] = $_POST['timezone'];
}

// Rate Limiting Check
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    if (isset($_SESSION['block_time']) && (time() - $_SESSION['block_time']) <= 5) {
        die('You have been locked out of your account for 60 seconds due to multiple failed login attempts.');
    } else {
        unset($_SESSION['login_attempts'], $_SESSION['block_time']);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $mfa_code = $_POST['mfa_code'] ?? ''; // MFA code input by user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hashing the password for logging
    // $ip_address = getUserIP(); // Ensure getUserIP() function is defined

    // Check login credentials
    $stmt = runQuery("SELECT id, username, password, mfa_secret FROM user_info WHERE username = ? OR email = ? OR phone = ?", "sss", $login, $login, $login);
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check for MFA
            if (!empty($user['mfa_secret'])) {
                if (!empty($mfa_code)) {
                    $tfa = new TwoFactorAuth('Login JM');
                    if (!$tfa->verifyCode($user['mfa_secret'], $mfa_code, 2)) {
                        $message = "Incorrect 2FA code.";
                    } else {
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_id'] = $user['id'];
                        session_regenerate_id(true);
                        header("Location: index.php");
                        exit();
                    }
                } else {
                    $message = "2FA code is required.";
                }
            } else {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
		$_SESSION['email'] = $user['email'];
                session_regenerate_id(true);
                header("Location: index.php");
                exit();
            }
        } else {
            $message = "Incorrect login information.";
        }
    } else {
        $message = "Incorrect login information.";
    }
    
    // Increment or set login attempts if failed
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['block_time'] = time(); // Set the block time
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-container">
    <h1>Login</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Username / Email / Phone</label>
            <input type="text" name="login" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>MFA code (if applicable)</label>
            <input type="text" name="mfa_code">
        </div>
        <input type="hidden" id="timezone" name="timezone">
        <button type="submit" name="login">Login</button>
    </form>

    <script>
        // Use JavaScript to capture the user's time zone and insert it into the hidden form field
        document.getElementById('timezone').value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    </script>

    <div class="links">
        <p><a href="password_recovery.php">Forgot your password?</a></p>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

</body>
</html>
