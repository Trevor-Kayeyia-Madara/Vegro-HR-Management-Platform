<?php
$role = $_SESSION['Role'];
?>
<nav>
    <ul>
        <li><a href="../public/home.php">Home</a></li>
        <?php if ($role == 'Admin' || $role == 'Manager'): ?>
            <li><a href="../public/manager/add_employee.php">Add Employee</a></li>
            <li><a href="../public/manager/delete_employee.php">Delete Employee</a></li>
            <li><a href="../public/manager/modify_employee.php">Modify Employee</a></li>
            <li><a href="../public/manager/employee_list.php">Employee List</a></li>
            <li><a href="../public/manager/search_record.php">Search Record</a></li>
            <li><a href="../public/manager/display_basic_info.php">Basic Info</a></li>
            <li><a href="../public/manager/display_basic_contact_info.php">Contact Info</a></li>
            <li><a href="../public/manager/list_of_employees.php">List of Employees</a></li>
        <?php endif; ?>
        <li><a href="../public/about.php">About</a></li>
        <li><a href="../public/logout.php">Logout</a></li>
    </ul>
</nav>
