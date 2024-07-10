<?php
session_start();

// Check if user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Initialize $role with a default value (optional)
$role = '';

// Fetch user details based on session
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);

if ($result_user && $result_user->num_rows == 1) {
    $user = $result_user->fetch_assoc();
    $role = $user['role']; // Assign the role fetched from the database
} else {
    // Handle error if user not found or query fails
    echo "Error: Unable to fetch user details.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            display: flex; /* Use flexbox for layout */
            max-width: 1000px; /* Adjust max-width as needed */
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .sidebar {
            flex: 1; /* Sidebar takes 1 part of 4 */
            padding: 20px;
            background-color: #f9f9f9;
            border-right: 1px solid #ddd; /* Optional: Separator line */
        }

        .main-content {
            flex: 3; /* Main content takes 3 parts of 4 */
            padding: 20px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        h3 {
            color: #555;
            margin-bottom: 10px;
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
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #dc3545;
        }

        .logout-link:hover {
            text-decoration: underline;
        }
        .table-container {
        max-width: 100%;
        overflow-x: auto;
    }

    .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .user-table th, .user-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .user-table th {
        background-color: #f2f2f2;
        color: #333;
    }

    .user-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <!-- Sidebar content -->
            <h3>Navigation</h3>
            <ul>
                <?php if ($role == 'admin'): ?>
                    <li><a href="add_user.php">Add User</a></li>
                    <li><a href="delete_user.php">Delete User</a></li>
                    <li><a href="reports.php">Generate Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                
                <?php elseif ($role == 'manager'): ?>
                    <li><a href="employee.php">Employee Management</a></li>
                    <li><a href="leave.php">Leave Management</a></li>
                    <li><a href="tasks.php">Task Management</a></li>
                    <li><a href="profile.php">Settings</a></li>
                
                <?php elseif ($role == 'employee'): ?>
                    <li><a href="leave.php">Leave Management</a></li>
                    <li><a href="tasks.php">Task Management</a></li>
                    <li><a href="settings.php">Settings</a></li>
                
                <?php endif; ?>
                <li><a class="logout-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <!-- Main content -->
            <h2>Welcome, <?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'User'; ?>!</h2>
            
            <?php if ($role == 'admin'): ?>
                <div class="admin-section">
        <h3>Admin Dashboard</h3>
        <p>Admin-specific content here.</p>

        <h4>List of Users</h4>
        <?php
        // Fetch users from the database
        $sql_users = "SELECT * FROM users";
        $result_users = $conn->query($sql_users);

        if ($result_users->num_rows > 0) {
            echo '<div class="table-container">';
            echo '<table class="user-table">';
            echo '<thead>';
            echo '<tr><th>User ID</th><th>Username</th><th>Role</th></tr>';
            echo '</thead>';
            echo '<tbody>';
            while ($row = $result_users->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo "No users found.";
        }
        ?>
    </div>
          
            <?php elseif ($role == 'manager'): ?>
                <div class="manager-section">
                    <h3>Manager Dashboard</h3>
                    <p>Manager-specific content here.</p>
                </div>
            
            <?php elseif ($role == 'employee'): ?>
                <div class="employee-section">
                    <h3>Employee Dashboard</h3>
                    <p>Employee-specific content here.</p>
                </div>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
