<?php
$servername = "localhost";
$username = "root";
$password = "RX3cttemQk5NVHU8";
$dbname = "vegro_farms_ems_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
