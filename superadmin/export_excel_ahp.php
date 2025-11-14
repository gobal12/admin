<?php
session_start();
// Pastikan path ini benar
require_once '../vendor/autoload.php';

// Gunakan class dari PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Cek role user
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        die("Akses ditolak. Anda harus login sebagai admin untuk mengunduh file ini.");
    }
}
check_role('admin');

require_once '../db_connection.php';

// ==== Ambil filter dari GET ====
$periode_id = isset($_GET['periode_id']) ? (int) $_GET['periode_id'] : 0;
$unit_id    = isset($_GET['unit_id']) ? (int) $_GET['unit_id'] : 0;

$periode_filter = $periode_id; // Alias untuk konsistensi
$unit_filter = $unit_id;

// ===== 2. AMBIL SEMUA DATA PENDUKUNG (SEKALI SAJA) =====
// (Logika ini disalin dari cetak_all_ahp.php)

// 2.1. AMBIL SEMUA DATA KPI (ID & TGL INPUT)
$kpi_data_map = []; // [k_id][p_id] => ['id' => 123, 'tgl' => '...']
$sql_kpi_ids = "SELECT id, karyawan_id, periode_id, tanggal_input FROM penilaian_kpi";
if ($periode_filter > 0) $sql_kpi_ids .= " WHERE periode_id = $periode_filter";
$kpi_id_res = $conn->query($sql_kpi_ids);
while ($row = $kpi_id_res->fetch_assoc()) {
    $kpi_data_map[$row['karyawan_id']][$row['periode_id']] = [
        'id' => $row['id'],
        'tgl' => $row['tanggal_input']
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

// ===== 1. (DI SINI) AMBIL SEMUA DATA MASTER AHP (LOOP) =====
$sql = "SELECT 
            pkahp.id, pkahp.total_nilai, pkahp.catatan,
            p.nama_periode, u.name AS nama_karyawan,
            k.id AS karyawan_id, p.id AS periode_id,
            up.name AS unit_project
        FROM penilaian_kpi_ahp pkahp
        JOIN karyawans k ON pkahp.karyawan_id = k.id
        JOIN users u ON k.user_id = u.id
        JOIN unit_projects up ON k.unit_project_id = up.id
        JOIN periode_penilaian p ON pkahp.periode_id = p.id";

$where = [];
if ($periode_filter > 0) $where[] = "pkahp.periode_id = $periode_filter";
if ($unit_filter > 0) $where[] = "k.unit_project_id = $unit_filter";
if (count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY up.name, u.name";

$result_master = $conn->query($sql);

// ===== START GENERASI EXCEL =====
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Hasil AHP');

// Definisikan Style
$header_style = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sub_header_style = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$data_style = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$total_style = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6E0B4']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

// === 3. BUAT HEADER (BERTINGKAT) ===
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nama Karyawan');
$sheet->setCellValue('C1', 'Unit / Project');
$sheet->setCellValue('D1', 'Periode');
$sheet->setCellValue('E1', 'Tgl. Penilaian');
// Merge header statis
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->getStyle('A1:E2')->applyFromArray($header_style);

$current_col = 6; // Kolom 'F'

// Loop Faktor untuk buat header
foreach ($struktur as $faktor) {
    $start_col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col);
    
    // Hitung jumlah kolom untuk faktor ini
    // (Jumlah Indikator + 4 kolom kalkulasi)
    $col_span = count($faktor['indikator']) + 4;
    
    // Baris 1: Nama Faktor
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '1', $faktor['nama']);
    
    // Baris 2: Nama Indikator & Kalkulasi
    foreach ($faktor['indikator'] as $ind) {
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2', $ind['nama']);
        $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col))->setWidth(15);
        $current_col++;
    }
    
    // 4 Kolom Kalkulasi
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2', 'Rata-Rata Faktor');
    $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2')->getFill()->getStartColor()->setRGB('D0E2FF');
    $current_col++;
    
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2', 'Normalisasi (A)');
    $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2')->getFill()->getStartColor()->setRGB('D0E2FF');
    $current_col++;
    
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2', 'Bobot AHP (B)');
    $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2')->getFill()->getStartColor()->setRGB('D0E2FF');
    $current_col++;
    
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2', 'Hasil (A x B)');
    $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . '2')->getFill()->getStartColor()->setRGB('D0E2FF');
    $current_col++;

    // Selesaikan merge Baris 1
    $end_col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col - 1);
    $sheet->mergeCells($start_col_letter . '1:' . $end_col_letter . '1');
    
    // Style header faktor
    $sheet->getStyle($start_col_letter . '1:' . $end_col_letter . '1')->applyFromArray($header_style);
    $sheet->getStyle($start_col_letter . '2:' . $end_col_letter . '2')->applyFromArray($sub_header_style);
}

// Kolom Total Terakhir
$total_col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col);
$sheet->setCellValue($total_col_letter . '1', 'TOTAL SCORE AHP');
$sheet->mergeCells($total_col_letter . '1:' . $total_col_letter . '2');
$sheet->getStyle($total_col_letter . '1:' . $total_col_letter . '2')->applyFromArray($total_style);

// === 4. ISI DATA (BODY) ===
$current_row = 3; // Mulai dari baris 3
$no = 1;

if ($result_master->num_rows > 0) {
    while ($penilaian = $result_master->fetch_assoc()) {
        
        // Ambil data pendukung
        $penilaian_id_ahp = $penilaian['id'];
        $karyawan_id = $penilaian['karyawan_id'];
        $periode_id = $penilaian['periode_id'];
        
        $kpi_data_lookup = $kpi_data_map[$karyawan_id][$periode_id] ?? null;
        $penilaian_id_kpi = $kpi_data_lookup ? $kpi_data_lookup['id'] : null;
        $tanggal_input_kpi = $kpi_data_lookup ? $kpi_data_lookup['tgl'] : null;
        
        $manager_scores = $manager_scores_all[$penilaian_id_kpi] ?? [];
        $avg_faktor_scores = $avg_faktor_scores_all[$penilaian_id_kpi] ?? [];
        $ahp_scores = $ahp_scores_all[$penilaian_id_ahp] ?? [];
        
        // Kolom Statis
        $sheet->setCellValue('A' . $current_row, $no++);
        $sheet->setCellValue('B' . $current_row, $penilaian['nama_karyawan']);
        $sheet->setCellValue('C' . $current_row, $penilaian['unit_project']);
        $sheet->setCellValue('D' . $current_row, $penilaian['nama_periode']);
        $sheet->setCellValue('E' . $current_row, $tanggal_input_kpi ? date('Y-m-d', strtotime($tanggal_input_kpi)) : '-');

        $current_col = 6; // Kolom 'F'
        
        // Loop Faktor untuk isi data
        foreach ($struktur as $faktor_id => $faktor) {
            
            // Isi nilai indikator
            foreach ($faktor['indikator'] as $ind) {
                $nilai_manajer = $manager_scores[$ind['id']] ?? '-';
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $nilai_manajer);
                $current_col++;
            }
            
            // Isi 4 Kolom Kalkulasi
            $skor_faktor = $ahp_scores[$faktor_id] ?? null;
            $avg_nilai = $avg_faktor_scores[$faktor_id] ?? null;
            
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $avg_nilai ? $avg_nilai : 0);
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->getNumberFormat()->setFormatCode('0.00');
            $current_col++;

            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $skor_faktor ? $skor_faktor['normalisasi'] : 0);
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->getNumberFormat()->setFormatCode('0.0000');
            $current_col++;
            
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $skor_faktor ? $skor_faktor['bobot'] : 0);
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->getNumberFormat()->setFormatCode('0.0000');
            $current_col++;
            
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $skor_faktor ? $skor_faktor['hasil'] : 0);
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->getNumberFormat()->setFormatCode('0.0000');
            $current_col++;
        }
        
        // Kolom Total Terakhir
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row, $penilaian['total_nilai']);
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->applyFromArray($total_style);
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($current_col) . $current_row)->getNumberFormat()->setFormatCode('0.0000');

        // Style baris
        $sheet->getStyle('A' . $current_row . ':' . $total_col_letter . $current_row)->applyFromArray($data_style);
        
        $current_row++;
    }
} else {
    $sheet->setCellValue('A3', 'Tidak ada data penilaian yang ditemukan.');
    $sheet->mergeCells('A3:' . $total_col_letter . '3');
}

// Auto-size kolom statis
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('E')->setAutoSize(true);
$sheet->getColumnDimension($total_col_letter)->setAutoSize(true);

// ===== 5. KIRIM FILE KE BROWSER =====
$filename = "data_ahp_transparan_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit();
?>