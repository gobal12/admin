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

// Ambil data utama penilaian
$stmt = $conn->prepare("SELECT 
    pk.id,
    pk.total_nilai,
    pk.tanggal_input,
    pk.catatan,
    p.nama_periode AS nama_periode,
    u.name AS nama_karyawan,
    j.name AS jabatan,
    up.name AS unit_project,
    k.hire_date
FROM penilaian_kpi pk
JOIN karyawans k ON pk.karyawan_id = k.id
JOIN users u ON k.user_id = u.id
JOIN jabatans j ON k.jabatan_id = j.id
JOIN unit_projects up ON k.unit_project_id = up.id
JOIN periode_penilaian p ON pk.periode_id = p.id
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
</style>

<h3 style="text-align: center; margin-bottom: 15px;">KEY PERFORMANCE INDICATOR (KPI) KARYAWAN PT. NUTECH INTEGRASI</h3>

<table width="100%" class="header-table">
    <tr>
        <th style="width: 25%; text-align: left;">PERIODE PENILAIAN</th>
        <td style="width: 25%;"><?= htmlspecialchars($penilaian['nama_periode']) ?></td>
        
        <th style="width: 25%; text-align: center;">NILAI</th>
        <td style="width: 25%; text-align: center;">PENILAIAN / DAFTAR NILAI</td>
    </tr>

    <tr>
        <th style="text-align: left;">Nama Karyawan</th>
        <td><?= htmlspecialchars($penilaian['nama_karyawan']) ?></td>

        <td rowspan="4" style="text-align: center; vertical-align: middle; font-size: 20px; font-weight: bold;">
            <?= number_format($penilaian['total_nilai'], 2) ?>
        </td>
        <td style="font-size: 11px;">4,00 — <strong>Sangat Baik</strong></td>
    </tr>

    <tr>
        <th style="text-align: left;">Bagian - Unit</th>
        <td><?= htmlspecialchars($penilaian['unit_project']) ?></td>

        <td style="font-size: 11px;">3,00 — <strong>Baik</strong></td>
    </tr>

    <tr>
        <th style="text-align: left;">Tgl. Mulai Bekerja</th>
        <td><?= date('d F Y', strtotime($penilaian['hire_date'])) ?></td>

        <td style="font-size: 11px;">2,00 — <strong>Kurang</strong></td>
    </tr>

    <tr>
        <th style="text-align: left;">Tgl. Penilaian</th>
        <td><?= date('d F Y', strtotime($penilaian['tanggal_input'])) ?></td>

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
            <th style="width: 40%;">FAKTOR KOMPETENSI</th>
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
                      if ($currentFaktor !== '') {
                          echo "<tr style='background-color: #d0e2ff; font-weight: bold;'>
                                  <td style='text-align:center;'>Total {$currentFaktor}</td>
                                  <td style='text-align:center;'>" . number_format($subtotalBobot, 2) . "%</td>
                                  <td style='text-align:center;'>" . number_format($subtotalTarget, 2) . "</td>
                                  <td style='text-align:center;'>Score</td>
                                  <td style='text-align:center;'>" . number_format($subtotalHasil, 2) . "</td>
                                </tr>";
                          $totalHasil += $subtotalHasil;
                      }

                      $currentFaktor = $row['nama_faktor'];
                      $subtotalBobot = $subtotalTarget = $subtotalHasil = 0;

                      echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>
                              <td colspan='5'>{$currentFaktor}</td>
                            </tr>";
                  endif;

                  // === Logika Baru ===
                  $target_dinamis = ($row['bobot_indikator'] / 100) * 4.00;

                  echo "<tr>
                          <td>" . htmlspecialchars($row['nama_indikator']) . "</td>
                          <td style='text-align:center;'>" . number_format($row['bobot_indikator'], 2) . "</td>
                          <td style='text-align:center;'>" . number_format($target_dinamis, 2) . "</td>
                          <td style='text-align:center;'>" . (int)$row['nilai'] . "</td>
                          <td style='text-align:center;'>" . number_format($row['hasil'], 2) . "</td>
                        </tr>";

                  $subtotalBobot += $row['bobot_indikator'];
                  $subtotalTarget += $target_dinamis;
                  $subtotalHasil += $row['hasil'];
              endwhile;

              // Cetak subtotal terakhir
              echo "<tr style='background-color: #d0e2ff; font-weight: bold;'>
                      <td style='text-align:center;'>Total {$currentFaktor}</td>
                      <td style='text-align:center;'>" . number_format($subtotalBobot, 2) . "%</td>
                      <td style='text-align:center;'>" . number_format($subtotalTarget, 2) . "</td>
                      <td style='text-align:center;'>Score</td>
                      <td style='text-align:center;'>" . number_format($subtotalHasil, 2) . "</td>
                    </tr>";
              
              $totalHasil += $subtotalHasil;

              // Cetak total score
              echo "<tr style='background-color: #c8f7c5; font-weight: bold;'>
                      <td colspan='4' style='text-align:right;'>TOTAL SCORE</td>
                      <td style='text-align:center;'>" . number_format($penilaian['total_nilai'], 2) . "</td>
                    </tr>";
          else:
              echo "<tr><td colspan='5' style='text-align:center;'>Detail penilaian tidak ditemukan.</td></tr>";
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
$dompdf->setPaper('A4', 'portrait'); // Perbaikan: 'Potrait' menjadi 'portrait'
$dompdf->render();
$dompdf->stream("penilaian_kpi_{$penilaian['nama_karyawan']}.pdf", ["Attachment" => false]); // Set Attachment ke false untuk preview
?>