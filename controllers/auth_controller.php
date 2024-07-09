<?php
session_start();
require_once '../database/db_connect.php';
require_once '../models/User.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // You should sanitize and validate $username and $password here

    // Instantiate the User class to handle authentication
    $user = new User($conn);

    // Attempt to authenticate user
    if ($user->authenticate($username, $password)) {
        $_SESSION['UserID'] = $user->getUserID();
        $_SESSION['Username'] = $user->getUsername();
        $_SESSION['Role'] = $user->getRole();

        // Redirect based on user role
        switch ($user->getRole()) {
            case 'Admin':
                header("Location: ../public/admin/dashboard.php");
                break;
            case 'Manager':
                header("Location: ../public/manager/dashboard.php");
                break;
            case 'Employee':
                header("Location: ../public/employee/dashboard.php");
                break;
            default:
                // Handle other roles or unexpected cases
                header("Location: ../public/login.php?error=unknown_role");
                break;
        }
        exit();
    } else {
        // Redirect back to login page with error message for invalid credentials
        header("Location: ../public/login.php?error=invalid_credentials");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: ../public/login.php");
    exit();
}
?>
