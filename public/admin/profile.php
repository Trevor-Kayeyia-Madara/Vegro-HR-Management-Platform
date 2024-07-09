<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user profile data from database based on $_SESSION['UserID']

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        /* Add relevant styles here */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Profile</h1>
        </div>
        <div class="content">
            <!-- Profile information goes here -->
            <p>Name: <?php echo htmlspecialchars($_SESSION['Username']); ?></p>
            <!-- Add form to update profile information -->
        </div>
    </div>
</body>
</html>
