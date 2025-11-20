<?php
session_start();

//Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('hrd');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

include '../db_connection.php';

// // Ambil data jabatan & unit_project untuk select option
// $jabatan = $conn->query("SELECT id, name FROM jabatans");
// $unit = $conn->query("SELECT id, name FROM unit_projects");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data
    $nama         = $_POST['nama'] ?? '';
    $email        = $_POST['email'] ?? '';
    $password     = password_hash('Nutech123', PASSWORD_DEFAULT);
    // $role         = 'karyawan'; // <-- HAPUS BARIS INI
    $role         = $_POST['role'] ?? ''; // <-- GANTI DENGAN BARIS INI

    // Validasi sederhana untuk role (opsional tapi disarankan)
    $allowed_roles = ['admin', 'hrd', 'manager', 'karyawan'];
    if (empty($role) || !in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Role yang dipilih tidak valid.']);
        exit;
    }


    $conn->begin_transaction(); // ✅ Mulai transaksi

    try {
        // Insert ke tabel users
        $stmt1 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("ssss", $nama, $email, $password, $role); // Variabel $role sudah dinamis
        $stmt1->execute();
        $user_id = $stmt1->insert_id;
        $stmt1->close();

        $conn->commit(); // ✅ Commit jika berhasil semua
        echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan.']);
    } catch (Exception $e) {
        $conn->rollback(); // ❌ Rollback jika salah satu gagal
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include 'layouts/style.php';?>

    <title>KPI Nutech Operation - Data Jabatan</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

                <div class="container fluid">
                    <div class="card-header py-3 bg-primary text-white">
                        <h4 class="m-0 font-weight-bold">Tambah User</h4>
                    </div>
                    <div class="card-body">
                    <form id="formUser">
                        <div class="form-group mb-3">
                            <label>Nama</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Role</label>
                            <select class="form-control" name="role" required>
                                <option value="" disabled selected>Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="hrd">HRD</option>
                                <option value="manager">Manager</option>
                                <option value="karyawan">Karyawan</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="datakaryawan.php" class="btn btn-secondary">Kembali</a>
                    </form>
                    </div>
                </div>

            <?php include 'layouts/footer.php'; ?>
    <div>
</div>

<?php include 'layouts/page_end.php'; ?>
    
    <script>
    document.getElementById("formUser").addEventListener("submit", function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch("", { // Action URL dikosongkan agar request ke halaman ini sendiri
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data user berhasil ditambahkan.'
                }).then(() => {
                    // Arahkan ke datakaryawan.php atau reset form di halaman ini
                    window.location.href = 'datakaryawan.php'; // Diubah agar ke daftar karyawan
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Terjadi kesalahan.'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        });
    });
    </script>

<script>
$(document).ready(function() {
    // 1. Tangkap klik pada tombol hamburger (di topbar atau sidebar)
    $('#sidebarToggle, #sidebarToggleTop').on('click', function(e) {
        
        // Biarkan script bawaan SB Admin bekerja dulu (menambah class 'toggled')
        // Kita beri sedikit delay 50ms agar class 'toggled' sudah terpasang
        setTimeout(function() {
            
            var sidebar = $('.sidebar');
            var contentWrapper = $('#content-wrapper');
            var body = $('body');

            // Cek apakah sidebar sekarang dalam kondisi 'toggled' (tertutup)?
            var isClosed = sidebar.hasClass('toggled') || body.hasClass('sidebar-toggled');

            if (isClosed) {
                // --- KONDISI TERTUTUP ---
                // 1. Paksa lebar sidebar jadi 0
                sidebar.css('width', '0px !important');
                sidebar.attr('style', 'width: 0px !important'); 
                
                // 2. Sembunyikan overflow biar teks tidak bocor
                sidebar.css('overflow', 'hidden');

                // 3. Paksa konten jadi full width (Margin kiri 0)
                contentWrapper.css('margin-left', '0px');
                contentWrapper.attr('style', 'margin-left: 0px !important'); 

            } else {
                // --- KONDISI TERBUKA ---
                // 1. Kembalikan lebar sidebar (sesuaikan, biasanya 14rem atau 224px)
                sidebar.attr('style', ''); // Hapus inline style biar balik ke CSS asli
                
                // 2. Kembalikan margin konten
                contentWrapper.attr('style', ''); // Hapus inline style biar balik ke CSS asli
                // contentWrapper.css('margin-left', '14rem'); // Jika perlu dipaksa
            }

        }, 50); // Delay 50ms sangat cepat, mata tidak akan melihat
    });

    // 2. Cek status awal saat halaman dimuat (agar tidak loncat)
    if ($('.sidebar').hasClass('toggled')) {
        $('#content-wrapper').css('margin-left', '0px');
    }
});
</script>
</body>

</html>