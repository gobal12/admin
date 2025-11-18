<?php
session_start();

//Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('karyawan');

// ==== PERUBAHAN UTAMA: VALIDASI KEPEMILIKAN ====
if (!isset($_SESSION['user_id'])) {
    die("Error: Sesi tidak valid. 'user_id' tidak ditemukan. Silakan login kembali.");
}
$logged_in_user_id = (int)$_SESSION['user_id'];
$logged_in_user = $_SESSION['name'] ?? 'Guest';

include '../db_connection.php';

// Ambil ID penilaian dari URL
if (!isset($_GET['penilaian_id'])) {
    echo "ID Penilaian tidak ditemukan.";
    exit;
}
$penilaian_id = (int) $_GET['penilaian_id']; // Ini adalah ID dari penilaian_kpi_ahp

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
// =================================================

// ===== 1. AMBIL DATA MASTER AHP (Termasuk Karyawan ID & Periode ID) =====
// ==== PERUBAHAN QUERY: TAMBAHKAN VALIDASI KARYAWAN_ID ====
$stmt_ahp_master = $conn->prepare("SELECT 
    pkahp.id, pkahp.total_nilai, pkahp.catatan,
    p.nama_periode, u.name AS nama_karyawan,
    k.id AS karyawan_id, p.id AS periode_id
FROM penilaian_kpi_ahp pkahp
JOIN karyawans k ON pkahp.karyawan_id = k.id
JOIN users u ON k.user_id = u.id
JOIN periode_penilaian p ON pkahp.periode_id = p.id
WHERE pkahp.id = ? AND pkahp.karyawan_id = ?"); // <-- KONDISI BARU

$stmt_ahp_master->bind_param("ii", $penilaian_id, $logged_in_karyawan_id); // <-- BIND PARAMETER BARU
$stmt_ahp_master->execute();
$result = $stmt_ahp_master->get_result();
$penilaian = $result->fetch_assoc();
$stmt_ahp_master->close();

if (!$penilaian) {
    // Jika data tidak ditemukan, bisa jadi karena ID-nya salah
    // ATAU mencoba mengakses milik orang lain.
    echo "Data penilaian AHP tidak ditemukan atau Anda tidak memiliki hak akses.";
    exit;
}

// ---- Mulai dari sini, sisa kode sudah aman karena $penilaian_id sudah terbukti aman ----

$karyawan_id = $penilaian['karyawan_id'];
$periode_id = $penilaian['periode_id'];

// ===== 2. CARI ID PENILAIAN KPI YANG ASLI =====
$kpi_penilaian_id = null;
$stmt_kpi_id = $conn->prepare("SELECT id FROM penilaian_kpi WHERE karyawan_id = ? AND periode_id = ? LIMIT 1");
$stmt_kpi_id->bind_param("ii", $karyawan_id, $periode_id);
$stmt_kpi_id->execute();
$kpi_id_result = $stmt_kpi_id->get_result();
if ($kpi_id_result->num_rows > 0) {
    $kpi_penilaian_id = (int) $kpi_id_result->fetch_assoc()['id'];
}
$stmt_kpi_id->close();

// ===== 3. AMBIL NILAI 1-4 DARI MANAJER (DARI TABEL KPI) =====
$manager_scores = []; // [indikator_id] => nilai
if ($kpi_penilaian_id) {
    $stmt_kpi_detail = $conn->prepare("SELECT indikator_id, nilai FROM detail_penilaian WHERE penilaian_id = ?");
    $stmt_kpi_detail->bind_param("i", $kpi_penilaian_id);
    $stmt_kpi_detail->execute();
    $kpi_detail_result = $stmt_kpi_detail->get_result();
    while ($row = $kpi_detail_result->fetch_assoc()) {
        $manager_scores[(int)$row['indikator_id']] = (int)$row['nilai'];
    }
    $stmt_kpi_detail->close();
}

// ===== 3.5. [BARU] HITUNG RATA-RATA NILAI FAKTOR (DARI DATA KPI) =====
$avg_faktor_scores = []; // [faktor_id] => avg_nilai
if ($kpi_penilaian_id) {
    $stmt_avg = $conn->prepare("
        SELECT ik.faktor_id, AVG(dp.nilai) AS avg_nilai
        FROM detail_penilaian dp
        JOIN indikator_kompetensi ik ON dp.indikator_id = ik.id
        WHERE dp.penilaian_id = ?
        GROUP BY ik.faktor_id
    ");
    $stmt_avg->bind_param("i", $kpi_penilaian_id);
    $stmt_avg->execute();
    $avg_result = $stmt_avg->get_result();
    while ($row = $avg_result->fetch_assoc()) {
        $avg_faktor_scores[(int)$row['faktor_id']] = (float)$row['avg_nilai'];
    }
    $stmt_avg->close();
}

// ===== 4. AMBIL HASIL PERHITUNGAN AHP (DARI TABEL AHP) =====
$ahp_scores = []; // [faktor_id] => [...]
$stmt_ahp_detail = $conn->prepare("SELECT 
    dpa.faktor_id, dpa.nilai AS nilai_normalisasi, b.bobot AS bobot_ahp, dpa.hasil
FROM detail_penilaian_ahp dpa
LEFT JOIN bobot_ahp b ON dpa.faktor_id = b.faktor_id
WHERE dpa.penilaian_id = ? ORDER BY dpa.faktor_id");
$stmt_ahp_detail->bind_param("i", $penilaian_id);
$stmt_ahp_detail->execute();
$ahp_detail_result = $stmt_ahp_detail->get_result();
while ($row = $ahp_detail_result->fetch_assoc()) {
    $ahp_scores[(int)$row['faktor_id']] = [
        'normalisasi' => $row['nilai_normalisasi'],
        'bobot' => $row['bobot_ahp'],
        'hasil' => $row['hasil']
    ];
}
$stmt_ahp_detail->close();

// ===== 5. AMBIL STRUKTUR LENGKAP FAKTOR & INDIKATOR =====
$struktur = []; // [faktor_id] => ['nama' => ..., 'indikator' => [...]]
$stmt_struktur = $conn->prepare("SELECT 
    f.id AS faktor_id, f.nama AS nama_faktor,
    ik.id AS indikator_id, ik.nama AS nama_indikator
FROM faktor_kompetensi f
LEFT JOIN indikator_kompetensi ik ON f.id = ik.faktor_id
ORDER BY f.id, ik.id");
$stmt_struktur->execute();
$struktur_result = $stmt_struktur->get_result();
while ($row = $struktur_result->fetch_assoc()) {
    $fid = (int)$row['faktor_id'];
    if (!isset($struktur[$fid])) {
        $struktur[$fid] = [
            'nama' => $row['nama_faktor'],
            'indikator' => []
        ];
    }
    if ($row['indikator_id']) {
        $struktur[$fid]['indikator'][] = [
            'id' => (int)$row['indikator_id'],
            'nama' => $row['nama_indikator']
        ];
    }
}
$stmt_struktur->close();

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
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .table th, .table td { vertical-align: middle; }
        .bg-light { background-color: #f8f9fa !important; }
        .table-primary { background-color: #cfe2ff; }
        .table-success { background-color: #d1e7dd; }
        .cell-merged {
            background-color: #f8f9fa; 
            vertical-align: top;
            text-align: center;}
    </style>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

    <div class="container-fluid mt-4 mb-5"> <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="m-0 font-weight-bold">Detail Penilaian AHP</h4>
                <div>
                    <a href="hasil_ahp.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
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
                            <p><strong>Total Nilai (Skala 0-1):</strong><br>
                                <span class="badge badge-primary" style="font-size: 1.2rem;">
                                    <?= number_format($penilaian['total_nilai'], 4) ?>
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
                                <th>FAKTOR KOMPETENSI / INDIKATOR</th>
                                <th>NILAI (1-4)</th>
                                <th>RATA-RATA NILAI FAKTOR</th> <th>NORMALISASI (A)</th>
                                <th>BOBOT AHP (B)</th>
                                <th>HASIL (A x B)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($struktur) > 0):
                                foreach ($struktur as $faktor_id => $faktor):
                                    
                                    // Baris Judul Faktor
                                    echo "<tr class='bg-light font-weight-bold'><td colspan='6'>".htmlspecialchars($faktor['nama'])."</td></tr>";

                                    $jumlah_indikator = count($faktor['indikator']);
                                    
                                    if ($jumlah_indikator > 0) {
                                        $is_first_indicator = true; 
                                        
                                        foreach ($faktor['indikator'] as $ind) {
                                            $nilai_manajer = $manager_scores[$ind['id']] ?? '-';
                                            
                                            echo "<tr>";
                                            echo "    <td>" . htmlspecialchars($ind['nama']) . "</td>";
                                            echo "    <td class='text-center'>" . $nilai_manajer . "</td>";

                                            if ($is_first_indicator) {
                                                // Logika penggabungan sel yang Anda kirim sebelumnya (sudah benar)
                                                // Saya pindahkan 4 sel ini ke baris subtotal
                                                $skor_faktor = $ahp_scores[$faktor_id] ?? null;
                                                $avg_nilai = $avg_faktor_scores[$faktor_id] ?? null;

                                                echo "<td rowspan='$jumlah_indikator' class='text-center align-middle'>" . ($avg_nilai ? number_format($avg_nilai, 2) : 'N/A') . "</td>";
                                                echo "<td rowspan='$jumlah_indikator' class='text-center align-middle'>" . ($skor_faktor ? number_format($skor_faktor['normalisasi'], 4) : 'N/A') . "</td>";
                                                echo "<td rowspan='$jumlah_indikator' class='text-center align-middle'>" . ($skor_faktor ? number_format($skor_faktor['bobot'], 4) : 'N/A') . "</td>";
                                                echo "<td rowspan='$jumlah_indikator' class='text-center align-middle'>" . ($skor_faktor ? number_format($skor_faktor['hasil'], 4) : 'N/A') . "</td>";
                                                
                                                $is_first_indicator = false;
                                            }
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center font-italic'>(Tidak ada indikator)</td></tr>";
                                    }

                                    // Baris Subtotal Faktor tidak diperlukan lagi karena sudah digabung (rowspan)
                                    
                                endforeach;

                                // Baris total akhir
                                echo "<tr class='table-success'>
                                        <td colspan='5' class='text-right font-weight-bold'>TOTAL SCORE</td>
                                        <td class='text-center font-weight-bold'>" . number_format($penilaian['total_nilai'], 4) . "</td>
                                    </tr>";
                            else:
                                echo "<tr><td colspan='6' class='text-center'>Struktur Faktor/Indikator tidak ditemukan.</td></tr>";
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

    <?php include 'layouts/footer.php'; ?>
    <?php include 'layouts/page_end.php'; ?>
    
</body>
</html>