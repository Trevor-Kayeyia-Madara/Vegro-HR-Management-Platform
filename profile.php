<?php
session_start();
if (!isset($_SESSION['session_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once "db.php";

// Fetch session details
$session_id = $_SESSION['session_id'];
$sql_session = "SELECT * FROM sessions WHERE session_id = '$session_id'";
$result_session = $conn->query($sql_session);

if ($result_session && $result_session->num_rows == 1) {
    $session = $result_session->fetch_assoc();
    $user_id = $session['user_id'];
} else {
    echo "Error: Session not found.";
    exit();
}

// Fetch user details based on session
$sql_user = "SELECT * FROM users WHERE user_id = $user_id";
$result_user = $conn->query($sql_user);

if ($result_user && $result_user->num_rows == 1) {
    $user = $result_user->fetch_assoc();
} else {
    echo "Error: User not found.";
    exit();
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = htmlspecialchars($_POST['full_name']);
    $address = htmlspecialchars($_POST['address']);
    $phone = htmlspecialchars($_POST['phone']);
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);
    
    if (!empty($password)) {
        // Hash the password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_update = "UPDATE users SET full_name = '$full_name', address = '$address', phone = '$phone', email = '$email', password = '$hashed_password' WHERE user_id = $user_id";
    } else {
        $sql_update = "UPDATE users SET full_name = '$full_name', address = '$address', phone = '$phone', email = '$email' WHERE user_id = $user_id";
    }

    if ($conn->query($sql_update)) {
        echo "Profile updated successfully.";
        // Refresh user details
        $sql_user = "SELECT * FROM users WHERE user_id = $user_id";
        $result_user = $conn->query($sql_user);
        if ($result_user && $result_user->num_rows == 1) {
            $user = $result_user->fetch_assoc();
        } else {
            echo "Error: Unable to fetch updated user data.";
        }
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
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

        input[type="text"], input[type="email"], input[type="password"], textarea {
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
        <h2>Profile</h2>
        <form method="post" action="profile.php">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>

            <label for="address">Address:</label>
            <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>

            <label for="password">Password (leave blank to keep current):</label>
            <input type="password" id="password" name="password">

            <input type="submit" value="Update Profile">
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>
