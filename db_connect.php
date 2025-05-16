<?php
$host = 'localhost';
$dbname = 'railway';  // change to your actual DB name
$user = 'root';
$pass = 'root';  // 🔑 <== put your actual password here

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<?php
$host = 'localhost';
$dbname = 'railway';  // change to your actual DB name
$user = 'root';
$pass = 'NaNdu@79#05';  // 🔑 <== put your actual password here

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
