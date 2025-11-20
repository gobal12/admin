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

// Ambil nama user dari session
$logged_in_user = $_SESSION['name'] ?? 'Guest';

require_once '../db_connection.php';

// ==== Ambil filter dari GET ====
$periode_id = isset($_GET['periode_id']) ? (int) $_GET['periode_id'] : 0;
$unit_id    = isset($_GET['unit_id']) ? (int) $_GET['unit_id'] : 0;

// Query untuk ambil data penilaian_kpi + join karyawan dan periode
$sql = "SELECT 
            pk.id, 
            k.id AS karyawan_id, 
            u.name AS nama_karyawan, 
            pp.nama_periode, 
            pk.total_nilai, 
            pk.tanggal_input, 
            up.name AS unit_project
        FROM penilaian_kpi pk
        JOIN karyawans k ON pk.karyawan_id = k.id
        JOIN users u ON k.user_id = u.id
        JOIN periode_penilaian pp ON pk.periode_id = pp.id
        JOIN unit_projects up ON k.unit_project_id = up.id";

// ====== Tambahkan Filter jika ada ======
$where = [];
if ($periode_id > 0) {
    $where[] = "pp.id = $periode_id";
}
if ($unit_id > 0) {
    $where[] = "up.id = $unit_id";
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY pk.tanggal_input DESC";

$result = $conn->query($sql);

// Ambil daftar periode dan unit buat dropdown
$periodeList = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$unitList    = $conn->query("SELECT id, name FROM unit_projects ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
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

    <title>KPI Nutech Operation - Data Karyawan</title>

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

                    <!-- Page Heading -->
                    <div class="card-header py-3 bg-primary text-white">
                        <h4 class="m-0 font-weight-bold">Data KPI</h4>
                        <p class="mb-4">Menampilkan Data KPI yang sudah di Input</p>
                    </div>

                    <!-- DataTales Example -->
                <div class="card shadow mb-4">   
                    <div class="card-body">
                        <div class="table-responsive">
                            <!-- Form Filter + Tombol Cetak -->
                            <form method="GET" class="mb-3 row">
                            <!-- Filter Periode -->
                            <div class="col-md-4">
                            <label for="periode_id">Filter Periode:</label>
                            <select name="periode_id" id="periode_id" class="form-control" onchange="this.form.submit()">
                                <option value="0" <?= $periode_id === 0 ? 'selected' : '' ?>>-- Semua Periode --</option>
                                <?php foreach ($periodeList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $periode_id === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nama_periode']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            </div>

                            <!-- Filter Unit -->
                            <div class="col-md-4">
                                <label for="unit_id">Filter Unit / Project:</label>
                                <select name="unit_id" id="unit_id" class="form-control" onchange="this.form.submit()">
                                    <option value="0" <?= $unit_id === 0 ? 'selected' : '' ?>>-- Semua Unit / Project --</option>
                                    <?php foreach ($unitList as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $unit_id === (int)$u['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tombol Cetak Semua & Download Excel-->
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="cetak_all_kpi.php?periode_id=<?= $periode_id ?>&unit_id=<?= $unit_id ?>" 
                                target="_blank" 
                                class="btn btn-success ml-auto">
                                    <i class="fas fa-print"></i> Cetak Semua
                                </a>

                                <a href="export_excel.php?periode_id=<?= $periode_id ?>&unit_id=<?= $unit_id ?>" 
                                class="btn btn-info ml-2">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                            </form>
                            <!-- Tabel Data -->
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Karyawan</th>
                                        <th>Unit / Project</th>
                                        <th>Periode</th>
                                        <th>Total Nilai</th>
                                        <th>Tanggal Input</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if ($result->num_rows > 0): 
                                        while ($row = $result->fetch_assoc()):
                                    ?>
                                        <tr style="white-space: nowrap;">
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                                            <td><?= htmlspecialchars($row['unit_project']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                            <td><?= number_format($row['total_nilai'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['tanggal_input']) ?></td>
                                            <td>
                                                <a href="detailkpi.php?penilaian_id=<?= htmlspecialchars($row['id']) ?>" 
                                                class="btn btn-outline-info btn-sm" 
                                                title="Lihat Detail Penilaian">
                                                <i class="fas fa-eye"></i> Detail
                                                </a>

                                                <a href="cetak_kpi.php?penilaian_id=<?= htmlspecialchars($row['id']) ?>" 
                                                class="btn btn-outline-primary btn-sm" 
                                                title="Cetak Penilaian" target="_blank">
                                                <i class="fas fa-print"></i> Cetak
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else: ?>
                                        <tr><td colspan="7" class="text-center">Tidak ada data penilaian.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
                <!-- /.container-fluid -->
            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
    <!-- End of Footer -->
        <div>
    </div>

<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>
    
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
