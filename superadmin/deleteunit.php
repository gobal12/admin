<?php
session_start();

// Cek apakah ID disediakan di parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID tidak ditemukan.");
}

require_once '../db_connection.php';

$user_id = intval($_GET['id']); // pastikan tipe integer untuk keamanan

// Mulai transaksi untuk menjaga konsistensi data
$conn->begin_transaction();

try {
    // Hapus dari tabel unit terlebih dahulu
    $stmt1 = $conn->prepare("DELETE FROM unit_projects WHERE id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // Commit transaksi
    $conn->commit();

    // Redirect kembali ke halaman data jabatan
    header("Location: DataUnit_Projects.php?message=success");
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    echo "Terjadi kesalahan saat menghapus data: " . $e->getMessage();
}

$conn->close();
?>
