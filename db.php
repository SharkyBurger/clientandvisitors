<?php 
$host = "localhost";
$user = "root";
$pass = "";
$db = "clientandvisitor_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// FIX: Set charset to UTF-8 to prevent encoding issues/attacks
$conn->set_charset("utf8mb4");
?>