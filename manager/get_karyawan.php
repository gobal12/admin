<?php
include '../db_connection.php';

header('Content-Type: application/json');

// 1. Validasi kedua parameter (unit_id DAN periode_id)
if (!isset($_GET['unit_id']) || !isset($_GET['periode_id'])) {
    echo json_encode(['error' => 'unit_id dan periode_id harus disediakan']);
    exit;
}

$unit_id = (int) $_GET['unit_id'];
$periode_id = (int) $_GET['periode_id']; // <-- Ambil parameter baru

// 2. Kueri SQL diperbarui dengan filter status DAN pengecekan NOT EXISTS
// Ini hanya akan mengambil karyawan yang (status='Aktif') DAN (sesuai unit)
// DAN (BELUM ada di 'penilaian_kpi' untuk periode_id yang diberikan)
$sql = "SELECT k.id AS karyawan_id, u.name 
        FROM karyawans k
        JOIN users u ON k.user_id = u.id
        WHERE 
            k.unit_project_id = ? 
            AND k.status = 'Aktif'
            AND NOT EXISTS (
                SELECT 1 
                FROM penilaian_kpi pk 
                WHERE pk.karyawan_id = k.id AND pk.periode_id = ?
            )";

// 3. Menggunakan Prepared Statements untuk keamanan
$stmt = mysqli_prepare($conn, $sql);
// Bind 2 parameter integer (ii)
mysqli_stmt_bind_param($stmt, "ii", $unit_id, $periode_id); 
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