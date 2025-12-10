<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// FIX: Access Control - Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized: Only admins can delete records.");
}

$id = intval($_GET['id']);

// FIX: Prepared Statement
$stmt = $conn->prepare("DELETE FROM clients WHERE client_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: index.php");
?>