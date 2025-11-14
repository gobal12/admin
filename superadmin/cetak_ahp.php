<?php
require '../vendor/autoload.php'; // Lokasi Dompdf
use Dompdf\Dompdf;
require_once '../db_connection.php'; // Pastikan file koneksi DB disertakan

// Ambil ID penilaian dari URL
if (!isset($_GET['penilaian_id'])) {
    echo "ID Penilaian tidak ditemukan.";
    exit;
}
$penilaian_id = (int) $_GET['penilaian_id'];

// ===== 1. AMBIL DATA MASTER AHP (Termasuk Karyawan ID & Periode ID) =====
$stmt_ahp_master = $conn->prepare("SELECT 
    pkahp.id, pkahp.total_nilai, pkahp.catatan,
    p.nama_periode, u.name AS nama_karyawan,
    k.id AS karyawan_id, p.id AS periode_id,
    j.name AS jabatan, k.hire_date,
    up.name AS unit_project  /* <-- SUDAH DIPERBAIKI */
FROM penilaian_kpi_ahp pkahp
JOIN karyawans k ON pkahp.karyawan_id = k.id
JOIN users u ON k.user_id = u.id
JOIN jabatans j ON k.jabatan_id = j.id
JOIN unit_projects up ON k.unit_project_id = up.id /* <-- SUDAH DIPERBAIKI */
JOIN periode_penilaian p ON pkahp.periode_id = p.id
WHERE pkahp.id = ?");

$stmt_ahp_master->bind_param("i", $penilaian_id);
$stmt_ahp_master->execute();
$result = $stmt_ahp_master->get_result();
$penilaian = $result->fetch_assoc();
$stmt_ahp_master->close();

if (!$penilaian) {
    echo "Data penilaian AHP tidak ditemukan.";
    exit;
}

$karyawan_id = $penilaian['karyawan_id'];
$periode_id = $penilaian['periode_id'];
$unit_project = $penilaian['unit_project']; // <-- SUDAH DIPERBAIKI

// ===== 2. CARI ID KPI DAN TGL INPUT DARI PENILAIAN MENTAH =====
$kpi_penilaian_id = null;
$tanggal_input_kpi = null; // <-- BARU
$stmt_kpi_id = $conn->prepare("SELECT id, tanggal_input FROM penilaian_kpi WHERE karyawan_id = ? AND periode_id = ? LIMIT 1"); // <-- BARU
$stmt_kpi_id->bind_param("ii", $karyawan_id, $periode_id);
$stmt_kpi_id->execute();
$kpi_id_result = $stmt_kpi_id->get_result();
if ($kpi_id_result->num_rows > 0) {
    $kpi_data = $kpi_id_result->fetch_assoc(); // <-- BARU
    $kpi_penilaian_id = (int) $kpi_data['id'];
    $tanggal_input_kpi = $kpi_data['tanggal_input']; // <-- BARU
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

// ===== 3.5. HITUNG RATA-RATA NILAI FAKTOR (DARI DATA KPI) =====
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
    .header-table th, .header-table td { padding: 6px; font-size: 12px; }
    .main-table th { font-size: 10px; }
    .main-table td { font-size: 10px; }
    .catatan-table td { padding: 8px; }
    
    /* Style untuk sel AHP yang digabung */
    .cell-merged {
        background-color: #f0f0f0;
        vertical-align: top;
        text-align: center;
    }
</style>

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
        <td><?= htmlspecialchars($unit_project) ?></td>
        <td style="font-size: 11px;">3,00 — <strong>Baik</strong></td>

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

<?php
$html = ob_get_clean();

// Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("penilaian_ahp_{$penilaian['nama_karyawan']}.pdf", ["Attachment" => false]); // Set Attachment ke false untuk preview
?>