<?php
$host = 'localhost';
$db = 'railway_db';
$user = 'root';
$pass = 'root'; // empty by default in XAMPP

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
