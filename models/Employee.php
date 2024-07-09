<?php

class Employee {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addEmployee($name, $salary, $address, $phone, $email, $employmentHistory) {
        $sql = "INSERT INTO Employees (Name, Salary, Address, Phone, Email, EmploymentHistory) 
                VALUES (:name, :salary, :address, :phone, :email, :employmentHistory)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':employmentHistory', $employmentHistory);
        $stmt->execute();
    }

    public function deleteEmployee($employeeID) {
        $sql = "DELETE FROM Employees WHERE EmployeeID = :employeeID";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employeeID', $employeeID);
        $stmt->execute();
    }

    public function updateEmployee($employeeID, $name, $salary, $address, $phone, $email, $employmentHistory) {
        $sql = "UPDATE Employees 
                SET Name = :name, Salary = :salary, Address = :address, 
                    Phone = :phone, Email = :email, EmploymentHistory = :employmentHistory 
                WHERE EmployeeID = :employeeID";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employeeID', $employeeID);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':employmentHistory', $employmentHistory);
        $stmt->execute();
    }

    public function getEmployee($employeeID) {
        $sql = "SELECT * FROM Employees WHERE EmployeeID = :employeeID";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employeeID', $employeeID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllEmployees() {
        $sql = "SELECT * FROM Employees";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
