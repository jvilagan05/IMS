<?php
$host = "localhost";  
$dbname = "ejmt_trading";  
$username = "root";  
$password = "";  

// MySQLi Connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check MySQLi connection
if (!$conn) {
    die("MySQLi Connection failed: " . mysqli_connect_error());
}

// PDO Connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>
