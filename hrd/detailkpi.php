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

// Ambil ID penilaian dari URL
if (!isset($_GET['penilaian_id'])) {
    echo "ID Penilaian tidak ditemukan.";
    exit;
}

$penilaian_id = (int) $_GET['penilaian_id'];

// Ambil data utama penilaian
$stmt = $conn->prepare("SELECT 
    pk.id,
    pk.total_nilai,
    pk.tanggal_input,
    p.nama_periode AS nama_periode,
    u.name AS nama_karyawan,
    pk.catatan
FROM penilaian_kpi pk
JOIN periode_penilaian p ON pk.periode_id = p.id
JOIN karyawans k ON pk.karyawan_id = k.id
JOIN users u ON k.user_id = u.id
WHERE pk.id = ?");
$stmt->bind_param("i", $penilaian_id);
$stmt->execute();
$result = $stmt->get_result();
$penilaian = $result->fetch_assoc();
$stmt->close();

if (!$penilaian) {
    echo "Data penilaian tidak ditemukan.";
    exit;
}

// === Kueri Detail Diperbaiki ===
$stmt = $conn->prepare("SELECT 
    f.nama AS nama_faktor,
    ik.nama AS nama_indikator,
    ik.bobot_indikator,
    dp.nilai,
    dp.hasil
FROM detail_penilaian dp
JOIN indikator_kompetensi ik ON dp.indikator_id = ik.id
JOIN faktor_kompetensi f ON ik.faktor_id = f.id
WHERE dp.penilaian_id = ?
ORDER BY f.id, ik.id"); // Urutkan berdasarkan f.id lalu ik.id
$stmt->bind_param("i", $penilaian_id);
$stmt->execute();
$detail = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include 'layouts/style.php';?>

    <title>KPI Nutech Operation - Detail Penilaian</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .table th, .table td { vertical-align: middle; }
        .bg-light { background-color: #f8f9fa !important; }
        .table-primary { background-color: #cfe2ff; }
        .table-success { background-color: #d1e7dd; }
    </style>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

        <div class="container mt-4 mb-5">
            <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="m-0 font-weight-bold">Detail Penilaian KPI</h4>
                <div>
                    <a href="datakpi.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="cetak_kpi.php?penilaian_id=<?= $penilaian_id ?>" target="_blank" class="btn btn-success btn-sm">
                        <i class="fas fa-print"></i> Cetak PDF
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama Karyawan:</strong><br><?= htmlspecialchars($penilaian['nama_karyawan']) ?></p>
                            <p><strong>Periode:</strong><br><?= htmlspecialchars($penilaian['nama_periode']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Input:</strong><br><?= date('d F Y H:i', strtotime($penilaian['tanggal_input'])) ?></p>
                            <p><strong>Total Nilai:</strong><br>
                                <span class="badge badge-primary" style="font-size: 1.2rem;">
                                    <?= number_format($penilaian['total_nilai'], 2) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light text-center bg-primary text-white">
                            <tr>
                                <th>FAKTOR KOMPETENSI</th>
                                <th>BOBOT (%)</th>
                                <th>TARGET</th>
                                <th>NILAI (1-4)</th>
                                <th>HASIL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentFaktor = '';
                            $subtotalBobot = $subtotalTarget = $subtotalHasil = 0;
                            $totalHasil = 0;

                            if ($detail && $detail->num_rows > 0):
                                while ($row = $detail->fetch_assoc()):
                                    if ($row['nama_faktor'] !== $currentFaktor):
                                        // Cetak subtotal sebelumnya
                                        if ($currentFaktor !== '') {
                                            echo "<tr class='table-primary'>
                                                    <td class='text-center font-weight-bold'>Total {$currentFaktor}</td>
                                                    <td class='text-center font-weight-bold'>" . number_format($subtotalBobot, 2) . "%</td>
                                                    <td class='text-center font-weight-bold'>" . number_format($subtotalTarget, 2) . "</td>
                                                    <td class='text-center font-weight-bold'>Score</td>
                                                    <td class='text-center font-weight-bold'>" . number_format($subtotalHasil, 2) . "</td>
                                                </tr>";

                                            $totalHasil += $subtotalHasil;
                                        }

                                        // Reset subtotal & cetak judul faktor
                                        $currentFaktor = $row['nama_faktor'];
                                        $subtotalBobot = $subtotalTarget = $subtotalHasil = 0;

                                        echo "<tr class='bg-light font-weight-bold'><td colspan='5'>{$currentFaktor}</td></tr>";
                                    endif;

                                    // === Logika Baru ===
                                    $target_dinamis = ($row['bobot_indikator'] / 100) * 4.00;

                                    // Tampilkan data indikator
                                    echo "<tr>
                                            <td>" . htmlspecialchars($row['nama_indikator']) . "</td>
                                            <td class='text-center'>" . number_format($row['bobot_indikator'], 2) . "</td>
                                            <td class='text-center'>" . number_format($target_dinamis, 2) . "</td>
                                            <td class='text-center'>" . (int)$row['nilai'] . "</td> 
                                            <td class='text-center'>" . number_format($row['hasil'], 2) . "</td>
                                        </tr>";

                                    // Tambah subtotal
                                    $subtotalBobot += $row['bobot_indikator'];
                                    $subtotalTarget += $target_dinamis;
                                    $subtotalHasil += $row['hasil'];
                                endwhile;

                                // Cetak subtotal terakhir
                                echo "<tr class='table-primary'>
                                        <td class='text-center font-weight-bold'>Total {$currentFaktor}</td>
                                        <td class='text-center font-weight-bold'>" . number_format($subtotalBobot, 2) . "%</td>
                                        <td class='text-center font-weight-bold'>" . number_format($subtotalTarget, 2) . "</td>
                                        <td class='text-center font-weight-bold'>Score</td>
                                        <td class='text-center font-weight-bold'>" . number_format($subtotalHasil, 2) . "</td>
                                    </tr>";

                                $totalHasil += $subtotalHasil;

                                // Baris total akhir
                                echo "<tr class='table-success'>
                                        <td colspan='4' class='text-right font-weight-bold'>TOTAL SCORE</td>
                                        <td class='text-center font-weight-bold'>" . number_format($penilaian['total_nilai'], 2) . "</td>
                                    </tr>";
                            else:
                                echo "<tr><td colspan='5' class='text-center'>Detail penilaian tidak ditemukan.</td></tr>";
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <?php if (!empty($penilaian['catatan'])): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">Catatan</div>
                        <div class="card-body bg-light">
                            <?= nl2br(htmlspecialchars($penilaian['catatan'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
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