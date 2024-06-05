<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User</title>
    <script>
        function confirmDelete(id) {
            // Munculkan konfirmasi popup
            var confirmation = confirm("Are you sure you want to delete this user?");

            // Jika pengguna menekan OK
            if (confirmation) {
                // Redirect ke halaman delete-user.php dengan ID pengguna yang akan dihapus
                window.location.href = "delete-user.php?id=" + id;
            }
        }
    </script>
</head>

<body>

    <?php
    // Periksa apakah parameter 'id' telah diset dan bukan null
    if (isset($_GET["id"]) && !empty($_GET["id"])) {
        // Simpan ID pengguna dari parameter GET
        $id = $_GET["id"];
        ?>

        <!-- Tambahkan tombol untuk memanggil fungsi konfirmasi hapus -->
        <button onclick="confirmDelete(<?php echo $id; ?>)">Delete User</button>

    <?php
    } else {
        // Tampilkan pesan jika ID tidak ditemukan
        echo "User ID not found.";
    }
    ?>

</body>

</html>
