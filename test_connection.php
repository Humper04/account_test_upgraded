<?php
require 'connect.php';

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connection to the database was successful!";
}

// Optionally, you might want to run a simple query to further confirm
$result = $conn->query("SELECT 1");  // A simple query to test connectivity
if ($result) {
    echo "Query successfully executed. Database is fully operational.";
} else {
    echo "Failed to execute query: " . $conn->error;
}

// Close the database connection
$conn->close();
?>
