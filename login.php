<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css"> <!-- Link your CSS file for styling -->
</head>
<body>
    <div class="login-container">
        
        <form action="login.php" method="post">
        <h2>Login</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            
            <button type="submit" name="submit">Login</button>
        </form>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        include_once "db.php"; // Include database connection
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Query to check user credentials
        $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            session_start();
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            
            // Redirect based on role
            if ($_SESSION['role'] == 'admin') {
                header("Location: dashboard.php");
            } elseif ($_SESSION['role'] == 'manager') {
                header("Location: dashboard.php");
            } elseif ($_SESSION['role'] == 'employee') {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            echo "<p>Invalid username or password.</p>";
        }
        $conn->close();
    }
    ?>
</body>
</html>