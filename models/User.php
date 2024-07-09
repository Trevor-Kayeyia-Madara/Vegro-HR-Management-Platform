<?php
// User.php

class User {
    private $conn;
    private $UserID;
    private $Username;
    private $Role;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function authenticate($username, $password) {
        // Example query with prepared statement for security
        $query = "SELECT UserID, Username, Role, Password FROM UserCredentials WHERE Username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Verify password (replace with password hashing logic)
                if ($password === $row['Password']) {
                    $this->UserID = $row['UserID'];
                    $this->Username = $row['Username'];
                    $this->Role = $row['Role'];
                    return true;
                }
            }
        }
        return false;
    }

    // Getter methods if needed
    public function getUserID() {
        return $this->UserID;
    }

    public function getUsername() {
        return $this->Username;
    }

    public function getRole() {
        return $this->Role;
    }
}
?>
