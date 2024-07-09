<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <script src="https://kit.fontawesome.com/66aa7c98b3.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/login.css">
    </head>
<body>
    <div class="container">
        <form class="form-1" action="../controllers/auth_controller.php" method="POST">
            <h1>Login</h1>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            <span>Forgot Password</span>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>