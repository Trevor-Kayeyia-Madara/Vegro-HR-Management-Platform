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

// Handle task CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['create'])) {
        // Create new task
        $task_name = mysqli_real_escape_string($conn, $_POST['task_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $assigned_to = intval($_POST['assigned_to']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        $assigned_by = $user_id; // Assuming the current user assigns the task

        $sql_create = "INSERT INTO tasks (task_name, description, assigned_to, assigned_by, due_date, status) VALUES ('$task_name', '$description', $assigned_to, $assigned_by, '$due_date', 'Pending')";
        if ($conn->query($sql_create)) {
            // Notify assigned employee (optional based on your notification mechanism)
            $notification_message = "You have a new task assigned: $task_name";
            $sql_notify_employee = "INSERT INTO notifications (user_id, message, status) VALUES ($assigned_to, '$notification_message', 'unread')";
            $conn->query($sql_notify_employee);

            echo '<p class="message">Task created successfully.</p>';
        } else {
            echo '<p class="error">Error creating task: ' . $conn->error . '</p>';
        }
    } elseif (isset($_POST['update'])) {
        // Update task details
        $task_id = intval($_POST['task_id']);
        $task_name = mysqli_real_escape_string($conn, $_POST['task_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $assigned_to = intval($_POST['assigned_to']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);

        $sql_update = "UPDATE tasks SET task_name = '$task_name', description = '$description', assigned_to = $assigned_to, due_date = '$due_date' WHERE task_id = $task_id";
        if ($conn->query($sql_update)) {
            echo '<p class="message">Task updated successfully.</p>';
        } else {
            echo '<p class="error">Error updating task: ' . $conn->error . '</p>';
        }
    } elseif (isset($_POST['delete'])) {
        // Delete task
        $task_id = intval($_POST['task_id']);
        $sql_delete = "DELETE FROM tasks WHERE task_id = $task_id";
        if ($conn->query($sql_delete)) {
            echo '<p class="message">Task deleted successfully.</p>';
        } else {
            echo '<p class="error">Error deleting task: ' . $conn->error . '</p>';
        }
    } elseif (isset($_POST['update_status'])) {
        // Update task status manually
        $task_id = intval($_POST['task_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

        $sql_update_status = "UPDATE tasks SET status = '$new_status' WHERE task_id = $task_id";
        if ($conn->query($sql_update_status)) {
            echo '<p class="message">Task status updated successfully.</p>';
        } else {
            echo '<p class="error">Error updating task status: ' . $conn->error . '</p>';
        }
    }
}

// Fetch all tasks
$sql_tasks = "SELECT * FROM tasks";
$result_tasks = $conn->query($sql_tasks);

// Fetch all employees for assignment dropdown
$sql_employees = "SELECT * FROM employees";
$result_employees = $conn->query($sql_employees);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management</title>
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

        input[type="text"], input[type="date"], textarea, select {
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
        <h2>Task Management</h2>
        <?php if ($role == 'admin' || $role == 'manager'): ?>
        <form method="post" action="tasks.php">
            <label for="task_name">Task Name:</label>
            <input type="text" id="task_name" name="task_name" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="assigned_to">Assigned To:</label>
            <select id="assigned_to" name="assigned_to" required>
                <option value="">Select Employee</option>
                <?php while ($employee = $result_employees->fetch_assoc()): ?>
                    <option value="<?php echo $employee['employee_id']; ?>"><?php echo htmlspecialchars($employee['employee_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <label for="due_date">Due Date:</label>
            <input type="date" id="due_date" name="due_date" required>

            <input type="submit" name="create" value="Add Task">
        </form>
        <?php endif; ?>

        <h3>Task List</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Task Name</th>
                <th>Description</th>
                <th>Assigned To</th>
                <th>Due Date</th>
                <?php if ($role == 'admin' || $role == 'manager'): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
            <?php while ($task = $result_tasks->fetch_assoc()): ?>
            <tr>
                <td><?php echo $task['task_id']; ?></td>
                <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                <td><?php echo htmlspecialchars($task['description']); ?></td>
                <td><?php echo htmlspecialchars($task['assigned_to']); ?></td>
                <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                <?php if ($role == 'admin' || $role == 'manager'): ?>
                <td>
                    <form method="post" action="tasks.php" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                        <input type="submit" name="delete" value="Delete">
                    </form>
                    <form method="post" action="tasks.php" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                        <input type="hidden" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>">
                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($task['description']); ?>">
                        <input type="hidden" name="assigned_to" value="<?php echo htmlspecialchars($task['assigned_to']); ?>">
                        <input type="hidden" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>">
                        <input type="submit" name="update" value="Edit">
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php if ($role != 'admin' && $role != 'manager'): ?>
        <h3>Update Task Status</h3>
        <form method="post" action="tasks.php">
            <label for="task_id_select">Select Task:</label>
            <select id="task_id_select" name="task_id" required>
                <option value="">Select Task</option>
                <?php
                $result_tasks->data_seek(0); // Reset result pointer
                while ($task = $result_tasks->fetch_assoc()): ?>
                    <option value="<?php echo $task['task_id']; ?>"><?php echo htmlspecialchars($task['task_name']); ?></option>
                <?php endwhile; ?>
            </select>
            <label for="new_status">New Status:</label>
            <select id="new_status" name="new_status" required>
                <option value="">Select Status</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select>
            <input type="submit" name="update_status" value="Update Status">
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
