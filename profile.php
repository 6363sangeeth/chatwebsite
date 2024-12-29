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
$current_username = $_SESSION["username"];
$current_email = $_SESSION["email"];
$current_image = $_SESSION["image"];

// Update profile information
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];

    // Validate input
    if (empty($new_username) || empty($new_email)) {
        $_SESSION['message'] = "Please fill out all fields.";
    } else {
        // Update the user's information in the database
        $sql = "UPDATE Users SET username = ?, email = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
        
        if ($stmt->execute()) {
            // Update session variables with the new profile info
            $_SESSION["username"] = $new_username;
            $_SESSION["email"] = $new_email;
            $_SESSION['message'] = "Profile updated successfully.";
        } else {
            $_SESSION['message'] = "Something went wrong. Please try again.";
        }
    }

    // Handle profile image upload
    if (isset($_FILES['image'])) {
        $image = $_FILES['image'];

        // Check if the file is an image and has no errors
        if ($image['error'] == 0) {
            // Define allowed file types and max file size
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 500000; // 500 KB

            if (in_array($image['type'], $allowed_types) && $image['size'] <= $max_size) {
                $upload_dir = 'uploads/profile_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
                }

                // Create a unique filename for the uploaded image
                $image_name = uniqid('profile_', true) . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
                $image_path = $upload_dir . $image_name;

                // Move the uploaded file to the server
                if (move_uploaded_file($image['tmp_name'], $image_path)) {
                    // Update the database with the new image path
                    $sql = "UPDATE Users SET profile_image = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $image_path, $user_id);
                    if ($stmt->execute()) {
                        // Update session variable for image path
                        $_SESSION["image"] = $image_path;
                        $_SESSION['message'] = "Profile image updated successfully.";
                    } else {
                        $_SESSION['message'] = "Failed to update profile image.";
                    }
                } else {
                    $_SESSION['message'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['message'] = "Invalid image file. Please upload a JPEG, PNG, or GIF image under 500 KB.";
            }
        } else {
            $_SESSION['message'] = "Error uploading image.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { width: 80%; margin: 0 auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); }
        .message { color: green; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .profile-img { width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 10px 0; }
        .profile-img img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Profile</h2>

    <!-- Display message -->
    <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?php echo $_SESSION['message']; ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Profile image display -->
    <div class="profile-img">
        <?php if ($current_image): ?>
            <img src="<?php echo $current_image; ?>" alt="Profile Image">
        <?php else: ?>
            <img src="uploads/profile_images/default.jpg" alt="Default Profile Image">
        <?php endif; ?>
    </div>

    <!-- Profile update form -->
    <form action="profile.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
        </div>

        <div class="form-group">
            <label for="image">Profile Image:</label>
            <input type="file" name="image">
        </div>

        <button type="submit">Update Profile</button>
    </form>

    <!-- Logout button -->
    <form action="logout.php" method="post" style="margin-top: 20px;">
        <button type="submit">Logout</button>
    </form>

</div>

</body>
</html>

<?php
$conn->close();
?>
