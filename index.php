<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

echo "Welcome to the Home Page! <br>";
echo "Go to your <a href='profile.php'>Profile</a>";
?>
