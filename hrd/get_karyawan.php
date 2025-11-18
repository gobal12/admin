<?php
include '../db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['unit_id'])) {
    echo json_encode(['error' => 'unit_id not provided']);
    exit;
}

$unit_id = (int) $_GET['unit_id'];

// 1. Kueri SQL diperbarui dengan filter status
$sql = "SELECT k.id AS karyawan_id, u.name 
        FROM karyawans k
        JOIN users u ON k.user_id = u.id
        WHERE k.unit_project_id = ? AND k.status = 'Aktif'"; // <-- Filter 'Aktif' ditambahkan di sini

// 2. Menggunakan Prepared Statements untuk keamanan
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $unit_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($data);
?>