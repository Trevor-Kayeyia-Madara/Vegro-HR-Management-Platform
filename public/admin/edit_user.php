<?php
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../database/db_connect.php';

// Retrieve user details
if (isset($_GET['id'])) {
    $userID = $_GET['id'];
    $sql = "SELECT * FROM UserCredentials WHERE UserID = :userID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userID', $userID);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found";
        exit();
    }
} else {
    echo "No user ID specified";
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['Password'];
    $role = $_POST['role'];

    $sql = "UPDATE UserCredentials SET Username = :username, Password = :password, Role = :role WHERE UserID = :userID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':userID', $userID);

    if ($stmt->execute()) {
        echo "User updated successfully";
        header("Location: manage_users.php");
        exit();
    } else {
        echo "Error updating user: " . $stmt->errorInfo()[2];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Edit User</h1>
        <form method="POST" action="edit_user.php?id=<?php echo $user['UserID']; ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
            <label for="password">Password (leave blank to keep current password):</label>
            <input type="password" id="password" name="password">
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="Admin" <?php echo $user['Role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="Manager" <?php echo $user['Role'] == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                <option value="Employee" <?php echo $user['Role'] == 'Employee' ? 'selected' : ''; ?>>Employee</option>
            </select>
            <button type="submit">Update User</button>
        </form>
    </div>
</body>
</html>

<?php
$conn = null;
?>
