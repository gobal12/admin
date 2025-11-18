<?php
session_start();
require_once '../db_connection.php';

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id_karyawan = intval($_GET['id']); // ID dari tabel karyawans

// Ambil user_id yang terhubung
$sql_get_user = "SELECT user_id FROM karyawans WHERE id = ?";
$stmt_get = $conn->prepare($sql_get_user);
$stmt_get->bind_param("i", $id_karyawan);
$stmt_get->execute();
$stmt_get->bind_result($user_id);
$stmt_get->fetch();
$stmt_get->close();

// Validasi jika user_id ditemukan
if (!$user_id) {
    die("Data user terkait tidak ditemukan.");
}

// Hapus dari tabel karyawans
$sql_delete_karyawan = "DELETE FROM karyawans WHERE id = ?";
$stmt_delete_karyawan = $conn->prepare($sql_delete_karyawan);
$stmt_delete_karyawan->bind_param("i", $id_karyawan);

if (!$stmt_delete_karyawan->execute()) {
    die("Gagal menghapus data karyawan: " . $stmt_delete_karyawan->error);
}
$stmt_delete_karyawan->close();

// Hapus dari tabel users
$sql_delete_user = "DELETE FROM users WHERE id = ?";
$stmt_delete_user = $conn->prepare($sql_delete_user);
$stmt_delete_user->bind_param("i", $user_id);

if (!$stmt_delete_user->execute()) {
    die("Gagal menghapus data user: " . $stmt_delete_user->error);
}
$stmt_delete_user->close();

$conn->close();

// Kembali ke halaman data karyawan
header("Location: datakaryawan.php?msg=deleted");
exit();
?>
