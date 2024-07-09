<?php 
require_once '../../controllers/employee_controller.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Employee</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 800px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
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
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 10px;
            color: #555;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modify Employee</h1>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Salary</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Employment History</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?php echo $employee['EmployeeID']; ?></td>
                    <td><?php echo htmlspecialchars($employee['Name']); ?></td>
                    <td><?php echo $employee['Salary']; ?></td>
                    <td><?php echo htmlspecialchars($employee['Address']); ?></td>
                    <td><?php echo $employee['Phone']; ?></td>
                    <td><?php echo htmlspecialchars($employee['Email']); ?></td>
                    <td><?php echo htmlspecialchars($employee['EmploymentHistory']); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="employeeID" value="<?php echo $employee['EmployeeID']; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($employee['Name']); ?>" required>
                            <input type="text" name="salary" value="<?php echo $employee['Salary']; ?>" required>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($employee['Address']); ?>" required>
                            <input type="text" name="phone" value="<?php echo $employee['Phone']; ?>" required>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($employee['Email']); ?>" required>
                            <input type="text" name="employment_history" value="<?php echo htmlspecialchars($employee['EmploymentHistory']); ?>" required>
                            <button type="submit">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
