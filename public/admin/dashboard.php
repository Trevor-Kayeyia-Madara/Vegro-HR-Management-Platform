<?php
session_start();

// Redirect to login page if not logged in as admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Assuming $conn is your database connection
require_once '../../database/db_connect.php';

// Function to fetch user count from database
function getUserCount($conn) {
    $sql = "SELECT COUNT(*) AS total_users FROM UserCredentials";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_users'];
}

// Function to fetch currently logged in users count (assuming you have a table tracking active sessions)
function getLoggedInUsersCount() {
    // Implement your logic to fetch active session count or logged in users count
    // This could involve checking your session management system or activity logs
    return 0; // Placeholder, replace with actual count retrieval
}

$totalUsers = getUserCount($conn);
$loggedInUsers = getLoggedInUsersCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            max-width: 960px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sidebar {
            width: 25%;
            padding: 20px;
            background-color: #000;
            color: #fff;
            height: 100vh; /* Full height of viewport */
            overflow: auto; /* Enable scrolling if content exceeds height */
        }
        .sidebar a {
            display: block;
            padding: 10px 0;
            color: #fff;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #333;
        }
        .sidebar h2 {
            color: #fff;
        }
        .main-content {
            width: 75%;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #333;
            font-size: 2.5rem;
        }
        .content {
            padding: 20px;
        }
        .welcome-message {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #555;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #df3730;
        }
        .user-stats {
            margin-bottom: 20px;
        }
        .user-stats h2 {
            font-size: 1.5rem;
            color: #333;
        }
        .user-stats-item {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar, .main-content {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Menu</h2>
            <ul>
                <li><a href="add_user.php">Add User</a></li>
                <li><a href="manager_user.php">Manage Users</a></li>
                <li><a href="../logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['Username']); ?></h1>
            </div>
            <div class="content">
                <p class="welcome-message">Welcome to the Admin Dashboard. You have access to manage all administrative tasks.</p>
                
                <!-- User Statistics -->
                <div class="user-stats">
                    <h2>User Statistics</h2>
                    <div class="user-stats-item">Total Users: <?php echo $totalUsers; ?></div>
                    <div class="user-stats-item">Logged In Users: <?php echo $loggedInUsers; ?></div>
                </div>
                
                <!-- Placeholder for main content -->
                <!-- This is where you can display other admin-specific content or functionality -->
            </div>
        </div>
    </div>
</body>
</html>

