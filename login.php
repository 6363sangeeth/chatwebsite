<?php
// Database connection parameters
$servername = "localhost";
$username = "root";  // Change this to your MySQL username
$password = "";      // Change this to your MySQL password
$dbname = "ChatApp"; // Name of the database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define variables and initialize with empty values
$email = $password_input = "";
$email_err = $password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email field is not empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Check if password field is not empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password_input = trim($_POST["password"]);
    }

    // If no errors, proceed to check credentials in the database
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT user_id, username, email, password_hash FROM Users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            // Execute the query
            if ($stmt->execute()) {
                $stmt->store_result();

                // Check if the email exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($user_id, $username, $email, $hashed_password);
                    if ($stmt->fetch()) {
                        // Verify password
                        if (password_verify($password_input, $hashed_password)) {
                            // Start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email; // Store email in the session

                            // Redirect user to the dashboard
                            header("location: dashboard.php"); // Redirect to your dashboard page
                        } else {
                            $password_err = "The password you entered is incorrect.";
                        }
                    }
                } else {
                    $email_err = "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <p>Please fill in your credentials to log in.</p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo $email; ?>">
            <span><?php echo $email_err; ?></span>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" name="password" id="password">
            <span><?php echo $password_err; ?></span>
        </div>

        <div>
            <input type="submit" value="Login">
        </div>
        <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
    </form>
</body>
</html>
