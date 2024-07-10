<?php
session_start();

// Check if user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Fetch user details based on session (optional)
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);
$user = $result_user->fetch_assoc(); // Assuming you need user details in this page

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data
    $user_id_to_delete = $_POST['user_id_to_delete'];

    // Validate and sanitize input (example: ensure user_id is numeric and exists)

    // Delete user from database
    $sql_delete = "DELETE FROM users WHERE user_id = $user_id_to_delete";
    if ($conn->query($sql_delete) === TRUE) {
        echo "User deleted successfully!";
        // Optionally, redirect or refresh the page after deletion
        // header("Location: delete_user.php");
        // exit();
    } else {
        echo "Error: " . $sql_delete . "<br>" . $conn->error;
    }
}

// Fetch all users for display in the table
$sql_users = "SELECT * FROM users";
$result_users = $conn->query($sql_users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #bd2130;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h3>Navigation</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_user.php">Add User</a></li>
                <li><a href="reports.php">Generate Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="content">
            <h2>Delete User</h2>
            <?php
            if ($result_users->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>User ID</th><th>Username</th><th>Role</th><th>Action</th></tr>";
                while ($row = $result_users->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['user_id']}</td>";
                    echo "<td>{$row['username']}</td>";
                    echo "<td>{$row['role']}</td>";
                    echo "<td><form method='POST' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'><input type='hidden' name='user_id_to_delete' value='{$row['user_id']}'><button type='submit' class='delete-btn'>Delete</button></form></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No users found.";
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
