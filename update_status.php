<?php
session_start();
require_once '../db_connection.php';

// Function to check user role
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

// Check access for admin
check_role('admin');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $tanggal_close = date("Y-m-d H:i:s");

    // Update the status to 'Close' and set the 'tanggal_close' to the current date and time
    $sql = "UPDATE report SET status='Close', tanggal_close=? WHERE id_report=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $tanggal_close, $id);
    if ($stmt->execute()) {
        header("Location: datareport.php?status=success");
    } else {
        header("Location: datareport.php?status=error");
    }
    $stmt->close();
}

$conn->close();
?>
