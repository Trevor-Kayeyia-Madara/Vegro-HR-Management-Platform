<?php
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Manager') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        .sidebar a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            display: block;
            transition: background-color 0.3s ease;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .container {
            margin-left: 250px;
            width: calc(100% - 250px);
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
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                width: 100%;
                padding: 10px;
            }
            .header h1 {
                font-size: 2rem;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .sidebar a {
                float: left;
                padding: 10px;
            }
            .container {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="text-align: center;">Manager Menu</h2>
        <a href="add_employee.php">Add Employee</a>
        <a href="delete_employee.php">Delete Employee</a>
        <a href="modify_employee.php">Modify Employee</a>
        <a href="search_employee.php">Search Record</a>
        <a href="display_basic_info.php">Display Basic Info</a>
        <a href="display_basic_contact_info.php">Display Basic Contact Info</a>
        <a href="employee_list.php">List of Employees</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="container">
        <div class="header">
            <h1>Welcome, Manager <?php echo htmlspecialchars($_SESSION['Username']); ?></h1>
        </div>
        <div class="content">
            <p class="welcome-message">Welcome to the Manager Dashboard. You can manage employees and tasks here.</p>
            <!-- Placeholder for manager-specific content -->
        </div>
    </div>
</body>
</html>
