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

// Check if the friend_id is passed through the form
if (isset($_POST["friend_id"])) {
    $friend_id = $_POST["friend_id"];

    // Ensure that the logged-in user cannot add themselves as a friend
    if ($user_id == $friend_id) {
        $_SESSION['message'] = "You cannot add yourself as a friend.";
        header("location: dashboard.php");
        exit();
    }

    // Check if the user and friend are already friends or the request is pending
    $sql = "SELECT * FROM Friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // The users are already friends or the request is pending
        $_SESSION['message'] = "You are already friends or a request is pending.";
    } else {
        // Add the user as a friend with 'pending' status
        $sql = "INSERT INTO Friends (user_id, friend_id, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $friend_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Friend request sent.";
        } else {
            $_SESSION['message'] = "Oops! Something went wrong. Please try again.";
        }
    }

    // Redirect back to the dashboard
    header("location: dashboard.php");
    exit();
} else {
    $_SESSION['message'] = "No friend ID provided.";
    header("location: dashboard.php");
    exit();
}

$stmt->close();
$conn->close();
?>
