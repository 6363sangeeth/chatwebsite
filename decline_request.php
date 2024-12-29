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

// Get the logged-in user_id
$user_id = $_SESSION["user_id"];

// Handle decline request
if (isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    // Delete the friend request from the database
    $sql = "DELETE FROM Friends WHERE id = ? AND friend_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Friend request declined.";
    } else {
        $_SESSION['message'] = "Something went wrong. Please try again.";
    }

    header("location: dashboard.php");
    exit();
}

$stmt->close();
$conn->close();
?>