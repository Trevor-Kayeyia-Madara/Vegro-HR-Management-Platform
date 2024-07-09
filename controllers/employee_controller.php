<?php

require_once '../../database/db_connect.php';
require_once '../../models/Employee.php';

$employeeModel = new Employee($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $salary = $_POST['salary'];
                $address = $_POST['address'];
                $phone = $_POST['phone'];
                $email = $_POST['email'];
                $employmentHistory = $_POST['employment_history'];
                $employeeModel->addEmployee($name, $salary, $address, $phone, $email, $employmentHistory);
                break;
            case 'delete':
                $employeeID = $_POST['employeeID'];
                $employeeModel->deleteEmployee($employeeID);
                break;
            case 'update':
                $employeeID = $_POST['employeeID'];
                $name = $_POST['name'];
                $salary = $_POST['salary'];
                $address = $_POST['address'];
                $phone = $_POST['phone'];
                $email = $_POST['email'];
                $employmentHistory = $_POST['employment_history'];
                $employeeModel->updateEmployee($employeeID, $name, $salary, $address, $phone, $email, $employmentHistory);
                break;
            case 'search':
                $employeeID = $_POST['employeeID'];
                $employee = $employeeModel->getEmployee($employeeID);
                break;
        }
    }
}

$employees = $employeeModel->getAllEmployees();
?>
