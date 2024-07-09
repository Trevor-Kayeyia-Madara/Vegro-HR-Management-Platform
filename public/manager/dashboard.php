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
</head>
<body>
    <h1>Welcome, Manager <?php echo $_SESSION['Username']; ?></h1>
    <!-- Manager-specific content goes here -->
</body>
</html>
