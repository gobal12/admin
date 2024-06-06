<?php
require_once '../db_connection.php';

// Assuming you have a session started and user ID stored in session
session_start();
$user_id = $_SESSION['user_id'];

$sql = "SELECT first_name, last_name, email, divisi FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $divisi);
$stmt->fetch();
$stmt->close();
$conn->close();

// Mengonversi data ke format JSON
$profile_data = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'divisi' => $divisi
];

// Mengirim respons JSON
echo json_encode(['status' => 'success', 'data' => $profile_data]);
?>
