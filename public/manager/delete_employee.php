<?php include '../../controllers/employee_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Employee</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <h1>Delete Employee</h1>
    <form method="post" action="">
        <input type="hidden" name="action" value="delete">
        <label for="employeeID">Employee ID:</label>
        <input type="text" id="employeeID" name="employeeID" required><br>
        <button type="submit">Delete Employee</button>
    </form>
</body>
</html>
