<?php
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Employee') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <h1>Welcome, Employee <?php echo $_SESSION['Username']; ?></h1>
    <!-- Employee-specific content goes here -->
</body>
</html>
