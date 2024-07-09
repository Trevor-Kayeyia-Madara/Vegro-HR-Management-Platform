<?php include '../../controllers/employee_controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Employee Record</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            max-width: 400px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            margin-bottom: 10px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        button[type="submit"] {
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
        button[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Search Employee Record</h1>
        <form method="post" action="">
            <input type="hidden" name="action" value="search">
            <div class="form-group">
                <label for="employeeID">Employee ID:</label>
                <input type="text" id="employeeID" name="employeeID" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        
        <?php if (isset($employee)): ?>
        <div class="mt-4">
            <h2>Employee Details</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['Name']); ?></p>
            <p><strong>Salary:</strong> <?php echo htmlspecialchars($employee['Salary']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($employee['Address']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($employee['Phone']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['Email']); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies (optional, for certain components) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
