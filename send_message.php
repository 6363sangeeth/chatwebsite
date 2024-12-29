<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit();
}

// Database connection (replace with your own credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ChatApp";

// Create the database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the message and receiver_id are set in the POST request
if (isset($_POST['message']) && isset($_POST['receiver_id'])) {
    // Get the message and receiver_id from the form
    $message = $_POST['message']; // The message to send
    $receiver_id = $_POST['receiver_id']; // The user receiving the message
    $sender_id = $_SESSION['user_id']; // Get the sender's ID from the session

    // Get the current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Prepare the SQL query to insert the message into the database
    $sql = "INSERT INTO Messages (sender_id, receiver_id, message_text, timestamp) VALUES (?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }

    // Bind the parameters: i = integer, s = string
    $stmt->bind_param("iiiss", $sender_id, $receiver_id, $message, $timestamp);

    // Execute the query
    if ($stmt->execute()) {
        // Redirect to the chat page after the message is sent
        header("Location: dashboard.php?user_id=" . $receiver_id);
        exit(); // Exit the script to stop further execution
    } else {
        // If there was an error executing the query, show the error
        echo "Error: " . $stmt->error;
    }

    // Close the prepared statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
