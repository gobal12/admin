<?php
session_start();

// ====== BARU: Autoloader Composer ======
// Pastikan path ini benar, sesuaikan jika file ini ada di dalam sub-folder
// Jika export_excel_wide.php ada di /admin/ maka path-nya mungkin ../vendor/autoload.php
require_once '../vendor/autoload.php';

// ====== BARU: Gunakan class dari PhpSpreadsheet ======
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Cek role user
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        // Jangan kirim header HTML, langsung die
        die("Akses ditolak. Anda harus login sebagai admin untuk mengunduh file ini.");
    }
}

check_role('admin');

require_once '../db_connection.php';

// ==== Ambil filter dari GET ====
$periode_id = isset($_GET['periode_id']) ? (int) $_GET['periode_id'] : 0;
$unit_id    = isset($_GET['unit_id']) ? (int) $_GET['unit_id'] : 0;

// ===== 1. AMBIL SEMUA INDIKATOR (HEADER KOLOM) =====
// (Logika ini tidak berubah)
$indicator_sql = "SELECT 
                        ik.id, 
                        ik.nama AS nama_indikator, 
                        fk.nama AS nama_faktor 
                    FROM indikator_kompetensi ik 
                    JOIN faktor_kompetensi fk ON ik.faktor_id = fk.id 
                    ORDER BY fk.id, ik.id";

$indicator_result = $conn->query($indicator_sql);
$all_indicators = [];
$faktor_headers = []; 
while ($row = $indicator_result->fetch_assoc()) {
    $all_indicators[] = $row; 
    $faktor_nama = $row['nama_faktor'];
    if (!isset($faktor_headers[$faktor_nama])) {
        $faktor_headers[$faktor_nama] = [];
    }
    $faktor_headers[$faktor_nama][] = $row;
}

// ===== 2. AMBIL SEMUA DATA PENILAIAN (Data Mentah) =====
// (Logika ini tidak berubah)
$sql = "SELECT 
            pk.id AS penilaian_id, u.name AS nama_karyawan, up.name AS unit_project,
            pp.nama_periode, pk.total_nilai, pk.tanggal_input,
            dp.indikator_id, dp.nilai
        FROM 
            detail_penilaian dp
        JOIN penilaian_kpi pk ON dp.penilaian_id = pk.id
        JOIN karyawans k ON pk.karyawan_id = k.id
        JOIN users u ON k.user_id = u.id
        JOIN unit_projects up ON k.unit_project_id = up.id
        JOIN periode_penilaian pp ON pk.periode_id = pp.id";

$where = [];
if ($periode_id > 0) $where[] = "pp.id = $periode_id";
if ($unit_id > 0) $where[] = "up.id = $unit_id";
if (count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY pk.id, dp.indikator_id";
$result = $conn->query($sql);

// ===== 3. PROSES DATA DARI "PANJANG" KE "LEBAR" (PIVOT) =====
// (Logika ini tidak berubah)
$processed_data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pid = $row['penilaian_id'];
        if (!isset($processed_data[$pid])) {
            $processed_data[$pid] = [
                'nama_karyawan' => $row['nama_karyawan'], 'unit_project'  => $row['unit_project'],
                'nama_periode'  => $row['nama_periode'],  'total_nilai'   => $row['total_nilai'],
                'tanggal_input' => $row['tanggal_input'], 'scores'        => [] 
            ];
        }
        $processed_data[$pid]['scores'][$row['indikator_id']] = $row['nilai'];
    }
}

// ===== START GENERASI EXCEL (Menggunakan PhpSpreadsheet) =====

// 1. Buat Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data KPI Wide');

// 2. Definisikan Style (Opsional, tapi membuat rapi)
$header_style = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$data_style = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

// === 3. BUAT HEADER BERTINGKAT ===
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nama Karyawan');
$sheet->setCellValue('C1', 'Unit / Project');
$sheet->setCellValue('D1', 'Periode');
$sheet->setCellValue('E1', 'Total Nilai');
$sheet->setCellValue('F1', 'Tanggal Input');

// Gabungkan sel header statis
$sheet->mergeCells('A1:A2');
$sheet->mergeCells('B1:B2');
$sheet->mergeCells('C1:C2');
$sheet->mergeCells('D1:D2');
$sheet->mergeCells('E1:E2');
$sheet->mergeCells('F1:F2');

// Terapkan style ke header statis
$sheet->getStyle('A1:F2')->applyFromArray($header_style);

// Buat Header Dinamis (Faktor & Indikator)
$current_col = 'G'; // Mulai dari kolom G
foreach ($faktor_headers as $faktor_nama => $indicators) {
    $start_col = $current_col;
    $col_span = count($indicators);
    
    // Baris 1: Nama Faktor
    $sheet->setCellValue($start_col . '1', $faktor_nama);
    
    // Baris 2: Nama Indikator
    $col_index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($start_col);
    foreach ($indicators as $indicator) {
        $col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index);
        $nama_indikator_bersih = str_replace(" (Nilai)", "", $indicator['nama_indikator']);
        $sheet->setCellValue($col_letter . '2', $nama_indikator_bersih . ' (Nilai)');
        
        // Auto-size kolom indikator (opsional)
        $sheet->getColumnDimension($col_letter)->setAutoSize(true);
        $col_index++;
    }
    
    // Hitung kolom akhir
    $end_col_index = $col_index - 1;
    $end_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($end_col_index);

    // Gabungkan sel untuk Nama Faktor (Baris 1)
    if ($col_span > 1) {
        $sheet->mergeCells($start_col . '1:' . $end_col . '1');
    }

    // Terapkan style ke header dinamis
    $sheet->getStyle($start_col . '1:' . $end_col . '2')->applyFromArray($header_style);

    // Pindahkan $current_col ke posisi berikutnya
    $current_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($end_col_index + 1);
}

// === 4. ISI DATA (BODY) ===
$current_row = 3; // Mulai dari baris 3
$no = 1;

if (!empty($processed_data)) {
    foreach ($processed_data as $pid => $data) {
        // Kolom Statis
        $sheet->setCellValue('A' . $current_row, $no++);
        $sheet->setCellValue('B' . $current_row, $data['nama_karyawan']);
        $sheet->setCellValue('C' . $current_row, $data['unit_project']);
        $sheet->setCellValue('D' . $current_row, $data['nama_periode']);
        $sheet->setCellValue('E' . $current_row, $data['total_nilai']);
        $sheet->getStyle('E' . $current_row)->getNumberFormat()->setFormatCode('0.00'); // Format angka
        $sheet->setCellValue('F' . $current_row, $data['tanggal_input']);

        // Kolom Dinamis (Nilai Indikator)
        $col_index = 7; // Mulai dari kolom 7 (G)
        foreach ($all_indicators as $indicator) {
            $col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index);
            $indicator_id = $indicator['id'];
            
            if (isset($data['scores'][$indicator_id])) {
                $sheet->setCellValue($col_letter . $current_row, (int)$data['scores'][$indicator_id]);
            } else {
                $sheet->setCellValue($col_letter . $current_row, '-');
            }
            $col_index++;
        }
        
        // Terapkan style border ke baris data
        $last_col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index - 1);
        $sheet->getStyle('A' . $current_row . ':' . $last_col_letter . $current_row)->applyFromArray($data_style);

        $current_row++;
    }
} else {
    // Tidak ada data
    $sheet->setCellValue('A3', 'Tidak ada data penilaian yang ditemukan.');
    $sheet->mergeCells('A3:' . $current_col . '3');
}

// Auto-size kolom statis
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('F')->setAutoSize(true);

// ===== 5. KIRIM FILE KE BROWSER =====

// 1. Tentukan Nama File
$filename = "data_kpi_wide_format_" . date('Y-m-d') . ".xlsx"; // Ekstensi .xlsx

// 2. Set HTTP Headers BARU untuk .xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// 3. Buat Writer dan simpan ke 'php://output'
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Tutup koneksi dan hentikan script
$conn->close();
exit();
?>