<?php
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../database/db_connect.php';

// Handle delete operation
if (isset($_GET['delete'])) {
    $userID = $_GET['delete'];
    $sql = "DELETE FROM UserCredentials WHERE UserID = :userID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userID', $userID);
    if ($stmt->execute()) {
        echo "User deleted successfully";
    } else {
        echo "Error deleting user: " . $stmt->errorInfo()[2];
    }
}

// Retrieve users
$sql = "SELECT * FROM UserCredentials";
$stmt = $conn->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Manage Users</h1>
        <table>
            <thead>
                <tr>
                    <th>UserID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['UserID']; ?></td>
                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                    <td><?php echo $user['Role']; ?></td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $user['UserID']; ?>">Edit</a>
                        <a href="manage_users.php?delete=<?php echo $user['UserID']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn = null;
?>
