<?php
require_once '../db_connection.php';

// Periksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Periksa apakah ID telah diberikan
if(isset($_GET['id'])) {
    // Sanitasi ID
    $id = htmlspecialchars($_GET['id']);

    // Persiapkan statement SQL untuk menghapus data
    $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->bind_param("i", $id);

    // Eksekusi statement
    if ($stmt->execute()) {
        // Jika penghapusan berhasil, arahkan kembali ke halaman user-role.php
        header("Location: user-role.php");
        exit;
    } else {
        // Jika terjadi kesalahan, tampilkan pesan error
        echo "Error deleting record: " . $stmt->error;
    }

    // Tutup statement
    $stmt->close();
} else {
    // Jika ID tidak diberikan, tampilkan pesan error
    echo "ID not provided";
}

// Tutup koneksi
$conn->close();
?>
