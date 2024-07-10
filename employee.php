<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Check if the logged-in user is an admin or manager
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT role FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);
if ($result_user->num_rows == 1) {
    $user = $result_user->fetch_assoc();
    $role = $user['role'];
    if ($role != 'admin' && $role != 'manager') {
        echo "Access denied.";
        exit();
    }
} else {
    echo "Error: User not found.";
    exit();
}

// Handle employee CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['create'])) {
        // Create new employee
        $full_name = htmlspecialchars($_POST['full_name']);
        $address = htmlspecialchars($_POST['address']);
        $phone = htmlspecialchars($_POST['phone']);
        $email = htmlspecialchars($_POST['email']);
        $salary = htmlspecialchars($_POST['salary']);
        $employment_start = htmlspecialchars($_POST['employment_start']);

        $sql_create = "INSERT INTO employees (full_name, address, phone, email, salary, employment_start) VALUES ('$full_name', '$address', '$phone', '$email', '$salary', '$employment_start')";
        $conn->query($sql_create);
    } elseif (isset($_POST['update'])) {
        // Update employee details
        $employee_id = htmlspecialchars($_POST['employee_id']);
        $full_name = htmlspecialchars($_POST['full_name']);
        $address = htmlspecialchars($_POST['address']);
        $phone = htmlspecialchars($_POST['phone']);
        $email = htmlspecialchars($_POST['email']);
        $salary = htmlspecialchars($_POST['salary']);
        $employment_start = htmlspecialchars($_POST['employment_start']);

        $sql_update = "UPDATE employees SET full_name = '$full_name', address = '$address', phone = '$phone', email = '$email', salary = '$salary', employment_start = '$employment_start' WHERE employee_id = $employee_id";
        $conn->query($sql_update);
    } elseif (isset($_POST['delete'])) {
        // Delete employee
        $employee_id = htmlspecialchars($_POST['employee_id']);
        $sql_delete = "DELETE FROM employees WHERE employee_id = $employee_id";
        $conn->query($sql_delete);
    }
}

// Fetch all employees
$sql_employees = "SELECT * FROM employees";
$result_employees = $conn->query($sql_employees);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <style>
        /* Add your styles here */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"], input[type="date"], input[type="number"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f9f9f9;
        }

        .message {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Employee Management</h2>
        <form method="post" action="employee.php">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required>

            <label for="address">Address:</label>
            <input type="text" id="address" name="address" required>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required>

            <label for="email">Email:</label>
            <input type="text" id="email" name="email" required>

            <label for="salary">Salary:</label>
            <input type="number" id="salary" name="salary" required>

            <label for="employment_start">Employment Start Date:</label>
            <input type="date" id="employment_start" name="employment_start" required>

            <input type="submit" name="create" value="Add Employee">
        </form>

        <h3>Employee List</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Salary</th>
                <th>Employment Start</th>
                <th>Actions</th>
            </tr>
            <?php while ($employee = $result_employees->fetch_assoc()): ?>
            <tr>
                <td><?php echo $employee['employee_id']; ?></td>
                <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                <td><?php echo htmlspecialchars($employee['address']); ?></td>
                <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                <td><?php echo htmlspecialchars($employee['salary']); ?></td>
                <td><?php echo htmlspecialchars($employee['employment_start']); ?></td>
                <td>
                    <form method="post" action="employee.php" style="display:inline;">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                        <input type="submit" name="delete" value="Delete">
                    </form>
                    <form method="post" action="employee.php" style="display:inline;">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                        <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                        <input type="hidden" name="address" value="<?php echo htmlspecialchars($employee['address']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>">
                        <input type="hidden" name="salary" value="<?php echo htmlspecialchars($employee['salary']); ?>">
                        <input type="hidden" name="employment_start" value="<?php echo htmlspecialchars($employee['employment_start']); ?>">
                        <input type="submit" name="edit" value="Edit">
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
