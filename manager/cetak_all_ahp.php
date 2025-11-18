<?php
require '../vendor/autoload.php'; // Lokasi Dompdf
use Dompdf\Dompdf;
require_once '../db_connection.php';

// Tangkap filter dari GET
$where = [];
$periode_filter = 0;
$unit_filter = 0;

if (!empty($_GET['periode_id']) && $_GET['periode_id'] != 0) {
    $periode_filter = intval($_GET['periode_id']);
    $where[] = "pkahp.periode_id = $periode_filter";
}
if (!empty($_GET['unit_id']) && $_GET['unit_id'] != 0) {
    $unit_filter = intval($_GET['unit_id']);
    $where[] = "k.unit_project_id = $unit_filter";
}

// ===== 1. AMBIL SEMUA DATA MASTER AHP (LOOP) =====
// PERBAIKAN: Menambahkan 'up.name AS unit_project' dan JOIN
$sql = "SELECT 
            pkahp.id, pkahp.total_nilai, pkahp.catatan,
            p.nama_periode, u.name AS nama_karyawan,
            k.id AS karyawan_id, p.id AS periode_id,
            j.name AS jabatan, k.hire_date,
            up.name AS unit_project /* <-- BARU */
        FROM penilaian_kpi_ahp pkahp
        JOIN karyawans k ON pkahp.karyawan_id = k.id
        JOIN users u ON k.user_id = u.id
        JOIN jabatans j ON k.jabatan_id = j.id
        JOIN unit_projects up ON k.unit_project_id = up.id /* <-- BARU */
        JOIN periode_penilaian p ON pkahp.periode_id = p.id";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY up.name, u.name"; // urutkan per unit lalu nama

$result_master = $conn->query($sql);

if ($result_master->num_rows === 0) {
    echo "Tidak ada data penilaian untuk filter ini.";
    exit;
}

// ===== 2. AMBIL SEMUA DATA PENDUKUNG (SEKALI SAJA) =====

// 2.1. AMBIL SEMUA DATA KPI (ID & TGL INPUT)
$kpi_data_map = []; // [k_id][p_id] => ['id' => 123, 'tgl' => '...']
$sql_kpi_ids = "SELECT id, karyawan_id, periode_id, tanggal_input FROM penilaian_kpi"; // <-- BARU
if ($periode_filter > 0) $sql_kpi_ids .= " WHERE periode_id = $periode_filter";
$kpi_id_res = $conn->query($sql_kpi_ids);
while ($row = $kpi_id_res->fetch_assoc()) {
    $kpi_data_map[$row['karyawan_id']][$row['periode_id']] = [
        'id' => $row['id'],
        'tgl' => $row['tanggal_input'] // <-- BARU
    ];
}

// 2.2. AMBIL SEMUA NILAI KPI (MANAJER)
$manager_scores_all = [];
$sql_kpi_detail = "SELECT dp.penilaian_id, dp.indikator_id, dp.nilai 
                   FROM detail_penilaian dp
                   JOIN penilaian_kpi pk ON dp.penilaian_id = pk.id";
if ($periode_filter > 0) $sql_kpi_detail .= " WHERE pk.periode_id = $periode_filter";
$kpi_detail_res = $conn->query($sql_kpi_detail);
while ($row = $kpi_detail_res->fetch_assoc()) {
    $manager_scores_all[$row['penilaian_id']][$row['indikator_id']] = $row['nilai'];
}

// 2.3. AMBIL SEMUA RATA-RATA FAKTOR KPI
$avg_faktor_scores_all = [];
$sql_avg = "SELECT pk.id AS penilaian_id, ik.faktor_id, AVG(dp.nilai) AS avg_nilai
            FROM detail_penilaian dp
            JOIN indikator_kompetensi ik ON dp.indikator_id = ik.id
            JOIN penilaian_kpi pk ON dp.penilaian_id = pk.id";
if ($periode_filter > 0) $sql_avg .= " WHERE pk.periode_id = $periode_filter";
$sql_avg .= " GROUP BY pk.id, ik.faktor_id";
$avg_res = $conn->query($sql_avg);
while ($row = $avg_res->fetch_assoc()) {
    $avg_faktor_scores_all[$row['penilaian_id']][$row['faktor_id']] = $row['avg_nilai'];
}

// 2.4. AMBIL SEMUA HASIL AHP
$ahp_scores_all = [];
$sql_ahp_detail = "SELECT 
                      dpa.penilaian_id, dpa.faktor_id, dpa.nilai AS nilai_normalisasi, 
                      b.bobot AS bobot_ahp, dpa.hasil
                   FROM detail_penilaian_ahp dpa
                   LEFT JOIN bobot_ahp b ON dpa.faktor_id = b.faktor_id";
if ($periode_filter > 0) $sql_ahp_detail .= " WHERE dpa.periode_id = $periode_filter";
$ahp_detail_res = $conn->query($sql_ahp_detail);
while ($row = $ahp_detail_res->fetch_assoc()) {
    $ahp_scores_all[$row['penilaian_id']][$row['faktor_id']] = [
        'normalisasi' => $row['nilai_normalisasi'],
        'bobot' => $row['bobot_ahp'],
        'hasil' => $row['hasil']
    ];
}

// 2.5. AMBIL STRUKTUR FAKTOR/INDIKATOR
$struktur = [];
$struktur_res = $conn->query("SELECT 
                                f.id AS faktor_id, f.nama AS nama_faktor,
                                ik.id AS indikator_id, ik.nama AS nama_indikator
                              FROM faktor_kompetensi f
                              LEFT JOIN indikator_kompetensi ik ON f.id = ik.faktor_id
                              ORDER BY f.id, ik.id");
while ($row = $struktur_res->fetch_assoc()) {
    $fid = (int)$row['faktor_id'];
    if (!isset($struktur[$fid])) $struktur[$fid] = ['nama' => $row['nama_faktor'], 'indikator' => []];
    if ($row['indikator_id']) $struktur[$fid]['indikator'][] = ['id' => (int)$row['indikator_id'], 'nama' => $row['nama_indikator']];
}

// Generate HTML
ob_start();
?>
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    table, th, td { border: 1px solid black; border-collapse: collapse; }
    th, td { padding: 5px; }
    .ttd-cell {
        height: 70px;
        vertical-align: bottom;
        text-align: center;
        font-size: 11px;
    }
    .page-break { page-break-after: always; }
    .header-table th, .header-table td { padding: 6px; font-size: 12px; }
    .main-table th { font-size: 10px; }
    .main-table td { font-size: 10px; }
    .catatan-table td { padding: 8px; }
    .cell-merged {
        background-color: #f0f0f0;
        vertical-align: top;
        text-align: center;
    }
</style>
<?php
$totalData = $result_master->num_rows; // hitung total data
$no = 0;
while ($penilaian = $result_master->fetch_assoc()):
    $no++;
    // Ambil data pendukung yang sudah di-query
    $penilaian_id_ahp = $penilaian['id'];
    $karyawan_id = $penilaian['karyawan_id'];
    $periode_id = $penilaian['periode_id'];
    
    // Cari ID KPI dan Tgl Input yang sesuai
    $kpi_data_lookup = $kpi_data_map[$karyawan_id][$periode_id] ?? null; // <-- BARU
    $penilaian_id_kpi = $kpi_data_lookup ? $kpi_data_lookup['id'] : null;
    $tanggal_input_kpi = $kpi_data_lookup ? $kpi_data_lookup['tgl'] : null; // <-- BARU
    
    // Ambil data detail
    $manager_scores = $manager_scores_all[$penilaian_id_kpi] ?? [];
    $avg_faktor_scores = $avg_faktor_scores_all[$penilaian_id_kpi] ?? [];
    $ahp_scores = $ahp_scores_all[$penilaian_id_ahp] ?? [];
    ?>

<h3 style="text-align: center; margin-bottom: 15px;">KEY PERFORMANCE INDICATOR (KPI) KARYAWAN PT. NUTECH INTEGRASI</h3>

    <table width="100%" class="header-table">
        <tr>
            <th style="width: 25%; text-align: left;">PERIODE PENILAIAN</th>
            <td style="width: 25%;"><?= htmlspecialchars($penilaian['nama_periode']) ?></td>
            
            <th style="width: 25%; text-align: center;">NILAI AKHIR (0-1)</th>
            <td style="width: 25%; text-align: center;">PENILAIAN / DAFTAR NILAI</td>
        </tr>
        <tr>
            <th style="text-align: left;">Nama Karyawan</th>
            <td><?= htmlspecialchars($penilaian['nama_karyawan']) ?></td>
            <td rowspan="4" style="text-align: center; vertical-align: middle; font-size: 20px; font-weight: bold;"> <?= number_format($penilaian['total_nilai'], 4) ?>
            </td>
            <td style="font-size: 11px;">4,00 — <strong>Sangat Baik</strong></td>
        </tr>
        <tr>
            <th style="text-align: left;">Bagian - Unit</th>
            <td><?= htmlspecialchars($penilaian['unit_project']) ?></td> <td style="font-size: 11px;">3,00 — <strong>Baik</strong></td>
        </tr>
        <tr>
            <th style="text-align: left;">Tgl. Mulai Bekerja</th>
            <td><?= date('d F Y', strtotime($penilaian['hire_date'])) ?></td>
            <td style="font-size: 11px;">2,00 — <strong>Kurang</strong></td>
        </tr>
        <tr>
            <th style="text-align: left;">Tgl. Penilaian</th>
            <td><?= $tanggal_input_kpi ? date('d F Y', strtotime($tanggal_input_kpi)) : '-' ?></td>
            <td style="font-size: 11px;">1,00 — <strong>Buruk</strong></td>
        </tr>
    </table>
    <br>
    <table width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="width: 70%; vertical-align: top;">
        
        <table width="100%" class="main-table">
            <thead style="background-color: #007bff; color: #ffffff; text-align: center;">
            <tr>
                <th style="width: 30%;">FAKTOR KOMPETENSI / INDIKATOR</th>
                <th>NILAI (1-4)</th>
                <th>RATA-RATA FAKTOR</th>
                <th>NORMALISASI (A)</th>
                <th>BOBOT AHP (B)</th>
                <th>HASIL (A x B)</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (count($struktur) > 0):
                foreach ($struktur as $faktor_id => $faktor):
                    
                    // Baris Judul Faktor
                    echo "<tr style='background-color: #f0f0f0; font-weight: bold;'><td colspan='6'>".htmlspecialchars($faktor['nama'])."</td></tr>";

                    // Baris Indikator (LOGIKA BARU DI SINI)
                    $jumlah_indikator = count($faktor['indikator']);
                    
                    if ($jumlah_indikator > 0) {
                        $is_first_indicator = true; // Flag
                        
                        foreach ($faktor['indikator'] as $ind) {
                            $nilai_manajer = $manager_scores[$ind['id']] ?? '-';
                            
                            echo "<tr>";
                            echo "    <td>" . htmlspecialchars($ind['nama']) . "</td>";
                            echo "    <td style='text-align:center;'>" . $nilai_manajer . "</td>";

                            if ($is_first_indicator) {
                                // PERBAIKAN: 1 sel digabung 4 kolom DAN 'N' baris
                                echo "<td rowspan='$jumlah_indikator' colspan='4' class='cell-merged'>&nbsp;</td>";
                                $is_first_indicator = false; // Matikan flag
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; font-style:italic;'>(Tidak ada indikator)</td></tr>";
                    }

                    // Baris Subtotal Faktor (Hasil AHP) - Ini sudah benar
                    $skor_faktor = $ahp_scores[$faktor_id] ?? null;
                    $avg_nilai = $avg_faktor_scores[$faktor_id] ?? null;
                    
                    echo "<tr style='background-color: #d0e2ff; font-weight: bold;'>";
                    echo "    <td colspan='2' style='text-align:center;'>Total ".htmlspecialchars($faktor['nama'])."</td>"; 
                    echo "    <td style='text-align:center;'>" . ($avg_nilai ? number_format($avg_nilai, 2) : 'N/A') . "</td>";
                    echo "    <td style='text-align:center;'>" . ($skor_faktor ? number_format($skor_faktor['normalisasi'], 4) : 'N/A') . "</td>";
                    echo "    <td style='text-align:center;'>" . ($skor_faktor ? number_format($skor_faktor['bobot'], 4) : 'N/A') . "</td>";
                    echo "    <td style='text-align:center;'>" . ($skor_faktor ? number_format($skor_faktor['hasil'], 4) : 'N/A') . "</td>";
                    echo "</tr>";

                endforeach;

                // Baris total akhir - Ini sudah benar
                echo "<tr style='background-color: #c8f7c5; font-weight: bold;'>
                        <td colspan='5' style='text-align:right;'>TOTAL SCORE</td>
                        <td style='text-align:center;'>" . number_format($penilaian['total_nilai'], 4) . "</td>
                        </tr>";
            else:
                echo "<tr><td colspan='6' style='text-align:center;'>Struktur Faktor/Indikator tidak ditemukan.</td></tr>";
            endif;
            ?>
            </tbody>
        </table>
        
        </td>

        <td style="width: 30%; vertical-align: top;">
        <table width="100%">
            <thead>
            <tr><th style="text-align: center; font-size: 11px;">Tanda Tangan</th></tr>
            </thead>
            <tbody>
            <tr><td class="ttd-cell"><?= $penilaian['nama_karyawan']; ?><br><small>Karyawan</small></td></tr>
            <tr><td class="ttd-cell">Project Manager<br>/ Koordinator Unit</td></tr>
            <tr><td class="ttd-cell">Manager<br>/ Ass Manager</td></tr>
            <tr><td class="ttd-cell">General Manager<br>/ Direktur</td></tr>
            </tbody>
        </table>
        </td>
    </tr>
    </table>
    <table width="100%" class="catatan-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="background-color: #f8f8f8; text-align: left; font-size: 11px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="min-height: 50px; font-size: 10px;"><?= nl2br(htmlspecialchars($penilaian['catatan'] ?? '-')) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if ($no < $totalData): ?>
        <div class="page-break"></div>
    <?php endif; ?>

<?php endwhile; ?>

<?php
$html = ob_get_clean();

// Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("penilaian_ahp_all.pdf", ["Attachment" => false]); // Set Attachment ke false untuk preview
?>