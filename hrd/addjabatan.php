<?php
session_start();

// --- 1. LOGIKA PHP DARI ADDJABATAN.PHP ---
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('hrd'); // Sesuaikan role

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

include '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jabatan = trim($_POST['nama_jabatan']);

    if (!empty($nama_jabatan)) {
        $stmt = $conn->prepare("INSERT INTO jabatans (name) VALUES (?)");
        $stmt->bind_param("s", $nama_jabatan);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nama jabatan tidak boleh kosong']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KPI Nutech Operation - Tambah Jabatan</title>
    
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php include 'layouts/style.php';?>
    
    <style>
        /* Sedikit perbaikan agar footer tidak naik */
        html, body { height: 100%; }
        #content-wrapper { display: flex; flex-direction: column; height: 100%; }
        #content { flex: 1 0 auto; }
    </style>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <main class="container-fluid px-4 py-4">
        
        <div class="card-header py-3 bg-primary text-white mb-4">
            <h4 class="m-0 font-weight-bold">Tambah Jabatan</h4>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form id="jabatanForm">
                            <div class="form-group">
                                <label for="nama_jabatan">Nama Jabatan</label>
                                <input type="text" class="form-control" id="nama_jabatan" name="nama_jabatan" required placeholder="Masukkan nama jabatan...">
                            </div>
                            <hr>
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <a href="datajabatan.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include 'layouts/footer.php'; ?>
    
    <div>
    </div>

<?php include 'layouts/page_end.php'; ?>

<script>
document.getElementById('jabatanForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Berhasil', 'Jabatan berhasil ditambahkan', 'success')
                .then(() => window.location.href = 'datajabatan.php');
        } else {
            Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Gagal mengirim data', 'error');
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