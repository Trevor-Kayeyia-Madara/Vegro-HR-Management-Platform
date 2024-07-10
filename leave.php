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

// Handle leave application actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($role == 'employee' && isset($_POST['apply_leave'])) {
        // Apply for leave (handled in the previous version)
        $employee_id = htmlspecialchars($_POST['employee_id']);
        $start_date = htmlspecialchars($_POST['start_date']);
        $end_date = htmlspecialchars($_POST['end_date']);
        $reason = htmlspecialchars($_POST['reason']);

        $sql_apply_leave = "INSERT INTO leave_applications(employee_id, start_date, end_date, reason, status) VALUES ('$employee_id', '$start_date', '$end_date', '$reason', 'Pending')";
        $conn->query($sql_apply_leave);
    } elseif (($role == 'admin' || $role == 'manager') && isset($_POST['update_status'])) {
        // Update leave status
        $leave_id = htmlspecialchars($_POST['leave_id']);
        $status = htmlspecialchars($_POST['status']);

        $sql_update_status = "UPDATE leave_applications SET status = '$status' WHERE leave_id = $leave_id";
        if ($conn->query($sql_update_status)) {
            echo '<p class="message">Leave status updated successfully.</p>';
        } else {
            echo '<p class="error">Error updating leave status: ' . $conn->error . '</p>';
        }
    }
}

// Fetch leave applications
$sql_leaves = "SELECT * FROM leave_applications";
$result_leaves = $conn->query($sql_leaves);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
    <link rel="stylesheet" href="leave.css">
</head>
<body>
    <div class="container">
        <div class="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="content">
            <h2>Leave Management</h2>
            
            <?php if ($role == 'employee'): ?>
            <form method="post" action="leave.php">
                <label for="employee_id">Employee ID:</label>
                <input type="text" id="employee_id" name="employee_id" required>

                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>

                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" required></textarea>

                <input type="submit" name="apply_leave" value="Apply for Leave">
            </form>
            <?php endif; ?>

            <?php if ($role == 'admin' || $role == 'manager'): ?>
            <h3>Leave Applications</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Employee ID</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php while ($leave = $result_leaves->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $leave['leave_id']; ?></td>
                    <td><?php echo htmlspecialchars($leave['employee_id']); ?></td>
                    <td><?php echo htmlspecialchars($leave['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($leave['end_date']); ?></td>
                    <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                    <td>
                        <form method="post" action="leave.php">
                            <input type="hidden" name="leave_id" value="<?php echo $leave['leave_id']; ?>">
                            <select name="status">
                                <option value="Pending" <?php if ($leave['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                <option value="Approved" <?php if ($leave['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                <option value="Rejected" <?php if ($leave['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
                            </select>
                            <input type="submit" name="update_status" value="Update">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
