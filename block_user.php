<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ChatApp";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"]; // Get logged-in user's ID
$block_id = $_POST["block_id"]; // The ID of the user to be blocked

// Check if the user is trying to block themselves
if ($user_id == $block_id) {
    echo "You cannot block yourself.";
    exit();
}

// Insert into Blocked table to block the user
$sql = "INSERT INTO Blocked (user_id, blocked_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $block_id);

if ($stmt->execute()) {
    echo "User blocked successfully.";
} else {
    echo "Error blocking user: " . $conn->error;
}

// Close connection
$stmt->close();
$conn->close();

// Redirect back to the dashboard
header("location: dashboard.php");
exit();
?>
