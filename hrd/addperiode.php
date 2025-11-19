<?php
session_start();

// Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('hrd');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
include '../db_connection.php';

// ========== SIMPAN DATA BARU ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // supaya response JSON

    $nama_periode    = $_POST['nama_periode'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $status          = $_POST['status'];

    $conn->begin_transaction();

    try {
        // Insert ke tabel periode
        $stmt = $conn->prepare("INSERT INTO periode_penilaian (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama_periode, $tanggal_mulai, $tanggal_selesai, $status);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo json_encode(['success' => true]); 
    } catch (Exception $e) {
        $conn->rollback();

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
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

    <title>KPI Nutech Operation - Tambah Periode</title>

    <!-- Custom fonts for this template -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!--Konfirmasi Delete -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

    <!-- Begin Page Content -->
    <div class="container fluid">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Tambah Periode Baru</h4>
        </div>

        <div class="card-body">

        <form id="formPeriode" method="POST">
            <label>Periode</label>
            <input type="text" name="nama_periode" class="form-control" placeholder="Contoh: Q1 - 2026" required>

            <label>Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control" required>

            <label>Tanggal Selesai </label>
            <input type="date" name="tanggal_selesai" class="form-control" required>

            <label>Status </label>
            <select name="status" class="form-control" required>
                <option value="">-- Pilih Status --</option>
                <option value="Aktif">Aktif</option>
                <option value="Non Aktif">Non Aktif</option>
            </select>
            
            <button type="submit" class="btn btn-primary mt-3">Simpan Periode</button>
        </form>
        </div>
    </div>
    <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
    <!-- End of Footer -->
    <div>
</div>

<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>
    
    <!-- Konfirmasi Simpan -->
    <script>
    document.getElementById("formPeriode").addEventListener("submit", function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch("", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data periode berhasil ditambahkan.'
                }).then(() => {
                    window.location.href = 'periodepenilaian.php';
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
