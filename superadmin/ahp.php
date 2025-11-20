<?php
session_start();

//Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('admin');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

include '../db_connection.php';

// Ambil daftar periode
$periode = $conn->query("SELECT * FROM periode_penilaian ORDER BY tanggal_mulai DESC");
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

    <title>Perhitungan AHP</title>

    <!-- Custom fonts for this template -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

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
                <div class="container-fluid">
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>Perhitungan AHP Global</h5>
                        </div>
                        <div class="card-body">
                            <form id="form">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label for="periode_id" class="form-label fw-bold">
                                            <i class="fas fa-calendar-alt me-1"></i>Pilih Periode
                                        </label>
                                        <select name="periode_id" id="periode_id" class="form-control shadow-sm" required>
                                            <option value="">-- Pilih Periode --</option>
                                            <?php while ($p = $periode->fetch_assoc()) : ?>
                                                <option value="<?= $p['id'] ?>">
                                                    <?= htmlspecialchars($p['nama_periode']) ?> (<?= $p['Status'] ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success w-100 shadow-sm">
                                            <i class="fas fa-cogs me-1"></i>Proses Semua
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <!-- End of Main Content -->
    <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
    <!-- End of Footer -->
    </div>
</div>
<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>
    
    <!-- Konfirmasi Hitung Penilaian dengan AHP -->
    <script>
    document.querySelector("form").addEventListener("submit", function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch("hitung_ahp.php", {
            method: "POST",
            body: formData
        })
        
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Berhasil' : 'Gagal',
                text: data.message
            }).then(() => {
                if (data.success) {
                window.location.href = 'hasil_ahp.php?periode_id=' + encodeURIComponent(formData.get('periode_id'));
                }
            });
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Tidak dapat terhubung ke server.'
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
