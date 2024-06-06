<?php
require_once '../db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    // Mengambil ID dari parameter GET
    $id = $_GET["id"];
    
    // Mempersiapkan pernyataan SQL dengan parameter
    $sql = "DELETE FROM roles WHERE id=?";
    
    // Membuat prepared statement
    $stmt = $conn->prepare($sql);
    
    // Membind parameter ke prepared statement
    $stmt->bind_param("i", $id);
    
    // Mengeksekusi pernyataan
    if ($stmt->execute()) {
        // Redirect ke halaman datauser.php setelah penghapusan berhasil
        header("Location: user-role.php");
    } else {
        // Menampilkan pesan kesalahan jika terjadi masalah dalam penghapusan
        echo "Error deleting record: " . $stmt->error;
    }
    
    // Menutup prepared statement
    $stmt->close();
}

// Menutup koneksi database
$conn->close();
?>
