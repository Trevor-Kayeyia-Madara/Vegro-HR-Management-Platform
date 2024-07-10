<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Fetch user details based on session
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);

if ($result_user->num_rows == 1) {
    $user = $result_user->fetch_assoc();
} else {
    echo "Error: User not found.";
    exit();
}

// Handle form submission for updating user details
$success_message = "";
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password']; // Hash new password if provided, else keep old password

    $sql_update = "UPDATE users SET username = '$username', password = '$password' WHERE user_id = $user_id";
    
    if ($conn->query($sql_update) === TRUE) {
        $success_message = "Profile updated successfully.";
        // Refresh user data
        $sql_user = "SELECT * FROM users WHERE user_id = $user_id";
        $result_user = $conn->query($sql_user);
        $user = $result_user->fetch_assoc();
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            max-width: 1000px;
            margin: 20px auto;
        }

        .sidebar {
            flex: 1;
            padding: 20px;
            background-color: #f9f9f9;
            border-right: 1px solid #ddd;
        }

        .content {
            flex: 3;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .settings-container {
            max-width: 800px;
            margin: 20px auto;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"], input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .message {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            margin-bottom: 10px;
        }

        ul li a {
            text-decoration: none;
            color: #007bff;
        }

        ul li a:hover {
            text-decoration: underline;
        }

        .logout-link {
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h3>Navigation</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_user.php">Add User</a></li>
                <li><a href="delete_user.php">Delete User</a></li>
                <li><a href="reports.php">Generate Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="content">
            <div class="settings-container">
                <h2>Settings</h2>
                <?php if ($success_message): ?>
                    <p class="message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <form method="post" action="settings.php">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

                    <label for="password">New Password (leave blank to keep current password):</label>
                    <input type="password" id="password" name="password">

                    <input type="submit" value="Update Profile">
                </form>
                <br>
                <a class="logout-link" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
