<?php
include '../db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['unit_id'])) {
    echo json_encode(['error' => 'unit_id not provided']);
    exit;
}

$unit_id = (int) $_GET['unit_id'];

$sql = "SELECT k.id AS karyawan_id, u.name 
        FROM karyawans k
        JOIN users u ON k.user_id = u.id
        WHERE k.unit_project_id = $unit_id";

$result = mysqli_query($conn, $sql);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode($data);
