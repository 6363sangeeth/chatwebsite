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

// Get logged-in user's information
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$email = $_SESSION["email"];
$current_image = isset($_SESSION["image"]) ? $_SESSION["image"] : null; // Ensure image exists in session

// Fetch friends and blocked users
$friends = getFriends($conn, $user_id);

// Fetch existing chat messages (filtered by a selected user, if any)
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null; // Get selected user's ID from the URL
$chats = ($selected_user_id) ? getChatsWithUser($conn, $user_id, $selected_user_id) : [];

// Fetch chats with a specific user
function getChatsWithUser($conn, $user_id, $selected_user_id) {
    $sql = "SELECT m.message_text, m.timestamp, u.username AS sender_username 
            FROM Messages m
            JOIN Users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.timestamp DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get friends
function getFriends($conn, $user_id) {
    $sql = "SELECT user_id, username FROM Users WHERE user_id IN (SELECT friend_id FROM Friends WHERE user_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { width: 80%; margin: 0 auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); }
        .search-form, .chat-form { margin-bottom: 20px; }
        .user-info { padding: 20px; margin: 10px 0; background-color: #f9f9f9; border-radius: 6px; }
        .logout-btn { background-color: #f44336; color: white; padding: 10px; cursor: pointer; border-radius: 5px; }
        .logout-btn:hover { background-color: #e53935; }
        .chat-box { border: 1px solid #ccc; padding: 10px; height: 200px; overflow-y: auto; margin-bottom: 20px; }
        .search-result { padding: 10px; margin: 5px 0; background-color: #f0f0f0; border-radius: 5px; }
        .search-result a { text-decoration: none; }
        .friends, .blocked { margin-top: 20px; }
        .friends, .blocked { list-style: none; padding: 0; }
        .friends li, .blocked li { padding: 5px 0; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; overflow: hidden; margin: 20px auto; }
        .profile-img img { width: 100%; height: 100%; object-fit: cover; }
        .user-info form { display: flex; flex-direction: column; }
        .user-info form input[type="text"], .user-info form input[type="email"], .user-info form input[type="file"] {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .user-info form button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .user-info form button:hover {
            background-color: #45a049;
        }
        .edit-profile-btn {
            background-color: #008CBA;
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .edit-profile-btn:hover {
            background-color: #007bb5;
        }
    </style>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

<div class="container">

    <!-- Display Profile Image -->
    <div class="profile-img">
        <?php if (!empty($current_image)): ?>
            <img src="<?php echo $current_image; ?>" alt="Profile Image">
        <?php else: ?>
            <img src="uploads/profile_images/default.jpg" alt="Default Profile Image">
        <?php endif; ?>
    </div>

    <!-- Edit Profile Button -->
    <div class="user-info">
        <form action="dashboard.php" method="post" enctype="multipart/form-data">
            <h3>Edit Profile</h3>
            <label for="username">Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

            <label for="image">Profile Image:</label>
            <input type="file" name="image"><br><br>

            <button type="submit">Update Profile</button>
        </form>
    </div>

    <!-- Search form -->
    <div class="search-form">
        <form action="dashboard.php" method="post">
            <input type="text" name="search_query" placeholder="Search for users..." required>
            <button type="submit" name="search">Search</button>
        </form>
    </div>

    <!-- Display Search Results -->
    <?php if (isset($search_result)): ?>
        <h3>Search Results:</h3>
        <?php while ($row = $search_result->fetch_assoc()): ?>
            <div class="search-result">
                <p><?php echo htmlspecialchars($row['username']); ?> - <?php echo htmlspecialchars($row['email']); ?></p>
                <form action="add_friend.php" method="post">
                    <input type="hidden" name="friend_id" value="<?php echo $row['user_id']; ?>">
                    <button type="submit">Add as Friend</button>
                </form>
                <form action="block_user.php" method="post">
                    <input type="hidden" name="block_id" value="<?php echo $row['user_id']; ?>">
                    <button type="submit">Block User</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <!-- List of Friends -->
    <div class="friends">
        <h3>Your Friends</h3>
        <ul>
            <?php while ($friend = $friends->fetch_assoc()): ?>
                <li>
                    <a href="?user_id=<?php echo $friend['user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <!-- Display Chat Messages -->
    <?php if ($selected_user_id): ?>
        <div class="chat-box">
            <h3>Chat with <?php echo htmlspecialchars($selected_user_id); ?></h3>
            <?php while ($chat = $chats->fetch_assoc()): ?>
                <p><strong><?php echo htmlspecialchars($chat['sender_username']); ?>:</strong> <?php echo htmlspecialchars($chat['message_text']); ?></p>
            <?php endwhile; ?>
        </div>

        <!-- Send a Message -->
        <div class="chat-form">
            <form action="send_message.php" method="post">
                <textarea name="message" placeholder="Type a message..." required></textarea><br>
                <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                <button type="submit">Send Message</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Logout Button -->
    <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Logout</button>
    </form>

</div>

</body>
</html>

<?php
$conn->close();
?>
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
$username = $_SESSION["username"];

// Fetch pending friend requests
$sql = "SELECT * FROM Friends WHERE friend_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_requests = $stmt->get_result();

// Fetch user's friends
$sql_friends = "SELECT * FROM Friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'";
$stmt_friends = $conn->prepare($sql_friends);
$stmt_friends->bind_param("ii", $user_id, $user_id);
$stmt_friends->execute();
$friends = $stmt_friends->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

<h3>Your Friends:</h3>
<ul>
    <?php while ($friend = $friends->fetch_assoc()): ?>
        <li>
            <?php echo htmlspecialchars($friend['user_id'] == $user_id ? $friend['friend_id'] : $friend['user_id']); ?>
        </li>
    <?php endwhile; ?>
</ul>

<h3>Pending Friend Requests:</h3>
<?php if ($pending_requests->num_rows > 0): ?>
    <?php while ($request = $pending_requests->fetch_assoc()): ?>
        <form action="accept_request.php" method="post">
            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
            <button type="submit" name="action" value="accept">Accept</button>
        </form>
        <form action="decline_request.php" method="post">
            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
            <button type="submit" name="action" value="decline">Decline</button>
        </form>
    <?php endwhile; ?>
<?php else: ?>
    <p>No pending requests.</p>
<?php endif; ?>

<h3>Send a Friend Request:</h3>
<form action="add_friend.php" method="post">
    <input type="text" name="friend_id" placeholder="Friend's User ID" required>
    <button type="submit">Send Friend Request</button>
</form>

</body>
</html>

<?php
$conn->close();
?>
