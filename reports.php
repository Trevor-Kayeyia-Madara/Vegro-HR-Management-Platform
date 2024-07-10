<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Fetch user details based on session
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT role FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);
if ($result_user->num_rows == 1) {
    $user = $result_user->fetch_assoc();
    $role = $user['role'];
} else {
    echo "Error: User not found.";
    exit();
}

// Check if user is admin
if ($role != 'admin') {
    echo "Access denied.";
    exit();
}

// Handle report generation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['generate_report'])) {
        $report_type = htmlspecialchars($_POST['report_type']);

        // Generate report based on the selected type
        switch ($report_type) {
            case 'employee_list':
                $sql_report = "SELECT * FROM employees";
                $report_result = $conn->query($sql_report);
                break;
            case 'leave_summary':
                $sql_report = "SELECT employee_id, COUNT(*) AS leave_count FROM leaves GROUP BY employee_id";
                $report_result = $conn->query($sql_report);
                break;
            case 'task_summary':
                $sql_report = "SELECT assigned_to, COUNT(*) AS task_count FROM tasks GROUP BY assigned_to";
                $report_result = $conn->query($sql_report);
                break;
            default:
                $report_result = null;
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports</title>
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

        select {
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
        <h2>Generate Reports</h2>
        <form method="post" action="reports.php">
            <label for="report_type">Report Type:</label>
            <select id="report_type" name="report_type" required>
                <option value="employee_list">Employee List</option>
                <option value="leave_summary">Leave Summary</option>
                <option value="task_summary">Task Summary</option>
            </select>

            <input type="submit" name="generate_report" value="Generate Report">
        </form>

        <?php if (isset($report_result) && $report_result->num_rows > 0): ?>
        <h3>Report Results</h3>
        <table>
            <tr>
                <?php if ($report_type == 'employee_list'): ?>
                <th>ID</th>
                <th>Full Name</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Salary</th>
                <th>Employment Start</th>
                <?php elseif ($report_type == 'leave_summary'): ?>
                <th>Employee ID</th>
                <th>Leave Count</th>
                <?php elseif ($report_type == 'task_summary'): ?>
                <th>Assigned To</th>
                <th>Task Count</th>
                <?php endif; ?>
            </tr>
            <?php while ($row = $report_result->fetch_assoc()): ?>
            <tr>
                <?php if ($report_type == 'employee_list'): ?>
                <td><?php echo $row['employee_id']; ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['address']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['salary']); ?></td>
                <td><?php echo htmlspecialchars($row['employment_start']); ?></td>
                <?php elseif ($report_type == 'leave_summary'): ?>
                <td><?php echo $row['employee_id']; ?></td>
                <td><?php echo $row['leave_count']; ?></td>
                <?php elseif ($report_type == 'task_summary'): ?>
                <td><?php echo htmlspecialchars($row['assigned_to']); ?></td>
                <td><?php echo $row['task_count']; ?></td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
