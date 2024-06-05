<?php
require_once '../db_connection.php';

// Pastikan data yang diterima dari form tidak kosong
if(empty($_POST['idrole']) || empty($_POST['rolename'])) {
    $_SESSION['message'] = "ID role dan nama role harus diisi.";
    header("Location: user-role.php");
    exit; // Hentikan proses lebih lanjut jika data kosong
}

// Ambil data dari form dan lakukan sanitasi
$idrole = htmlspecialchars($_POST['idrole']);
$rolename = htmlspecialchars($_POST['rolename']);

// Query untuk menyimpan data ke dalam database dengan parameterized query
$stmt = $conn->prepare("INSERT INTO roles (id, role_name) VALUES (?, ?)");
$stmt->bind_param("ss", $idrole, $rolename);

if ($stmt->execute()) {
    // Jika query berhasil dijalankan, set pesan sukses ke session
    $_SESSION['message'] = "Data berhasil ditambahkan";
} else {
    // Jika terjadi kesalahan, set pesan error ke session
    $_SESSION['message'] = "Error: " . $stmt->error;
}

// Tutup statement dan koneksi
$stmt->close();
$conn->close();

// Redirect pengguna ke halaman user-role.php
header("Location: user-role.php");
exit; // Hentikan proses lebih lanjut
?>
