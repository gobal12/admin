<?php
session_start();

// Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('karyawan');

// ==== PERUBAHAN UTAMA: AMBIL DARI USER ID ====
if (!isset($_SESSION['user_id'])) {
    die("Error: Sesi tidak valid. 'user_id' tidak ditemukan. Silakan login kembali.");
}
$logged_in_user_id = (int)$_SESSION['user_id'];
$logged_in_user = $_SESSION['name'] ?? 'Guest';

require_once '../db_connection.php';

// Cari karyawan_id dari user_id
$stmt_karyawan = $conn->prepare("SELECT id FROM karyawans WHERE user_id = ?");
$stmt_karyawan->bind_param("i", $logged_in_user_id);
$stmt_karyawan->execute();
$k_result = $stmt_karyawan->get_result();

if ($k_result->num_rows === 0) {
    die("Error: Tidak dapat menemukan data karyawan untuk user ini.");
}
$logged_in_karyawan_id = (int)$k_result->fetch_assoc()['id'];
$stmt_karyawan->close();
// =============================================

// Ambil filter dari GET
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

// Ambil daftar periode
$periodeList = [];
$resPeriode = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id DESC");
while ($row = $resPeriode->fetch_assoc()) {
    $periodeList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php include 'layouts/style.php';?>
    <title>Hasil AHP Saya</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <div class="card-header py-3 bg-primary text-white">
        <h4 class="m-0 font-weight-bold">Data Hasil AHP Saya</h4>
        <p class="mb-4">Menampilkan Data perhitungan AHP</p>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            
            <form method="GET" class="mb-3 row">
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
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <?php
                            // Ambil daftar faktor untuk header kolom
                            $faktorList = [];
                            $resFaktor = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
                            while ($row = $resFaktor->fetch_assoc()) {
                                $faktorList[] = $row;
                                echo "<th>" . htmlspecialchars($row['nama']) . "</th>";
                            }
                            ?>
                            <th>Nilai Akhir</th>
                            <th>Aksi</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;

                        // Base query
                        $sql = "
                            SELECT 
                                pkahp.id AS penilaian_id, 
                                u.name AS nama_karyawan, 
                                up.name AS nama_unit,
                                pkahp.total_nilai,
                                pp.nama_periode
                            FROM penilaian_kpi_ahp pkahp
                            JOIN karyawans k ON k.id = pkahp.karyawan_id
                            JOIN users u ON u.id = k.user_id
                            JOIN periode_penilaian pp ON pp.id = pkahp.periode_id
                            JOIN unit_projects up ON k.unit_project_id = up.id
                            WHERE pkahp.karyawan_id = ? -- <-- PERUBAHAN UTAMA
                        ";

                        $params = [$logged_in_karyawan_id]; // <-- PERUBAHAN UTAMA
                        $types = "i"; // <-- PERUBAHAN UTAMA

                        if ($periode_id > 0) {
                            $sql .= " AND pkahp.periode_id = ? ";
                            $params[] = $periode_id;
                            $types .= "i";
                        }

                        $sql .= " ORDER BY pp.id DESC, u.name";

                        $stmt = $conn->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Ambil detail nilai
                        $detailSql = "SELECT penilaian_id, faktor_id, hasil FROM detail_penilaian_ahp";
                        $detailResult = $conn->query($detailSql);

                        $detailData = [];
                        while ($d = $detailResult->fetch_assoc()) {
                            $detailData[$d['penilaian_id']][$d['faktor_id']] = $d['hasil'];
                        }

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_periode']) . "</td>";

                            foreach ($faktorList as $f) {
                                $fid = $f['id'];
                                $nilai = isset($detailData[$row['penilaian_id']][$fid]) ? number_format($detailData[$row['penilaian_id']][$fid], 4) : '0.0000';
                                echo "<td>$nilai</td>";
                            }

                            echo "<td>" . number_format($row['total_nilai'], 4) . "</td>";
                            
                            echo '<td style="white-space: nowrap;">
                                    <a href="detail_ahp.php?penilaian_id=' . htmlspecialchars($row['penilaian_id']) . '" 
                                    class="btn btn-outline-info btn-sm" 
                                    title="Lihat Detail Penilaian">
                                    <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>';
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

            <?php include 'layouts/footer.php'; ?>
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