<?php
session_start();
require '../vendor/autoload.php';
include '../db_connection.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

// Biar mysqli lempar exception => bisa ditangkap try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
    echo json_encode(['success' => false, 'message' => 'Request tidak valid atau file tidak ditemukan.']);
    exit;
}

try {
    $file = $_FILES['excel_file']['tmp_name'];
    if (!is_uploaded_file($file)) {
        throw new Exception('File upload tidak valid.');
    }

    // Baca Excel
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    // toArray(null, calcFormulas, formatData, returnCellRef)
    // returnCellRef=true -> indeks pakai A,B,C... sehingga mudah validasi header
    $rows = $sheet->toArray(null, true, true, true);

    if (empty($rows) || !isset($rows[1])) {
        throw new Exception('Sheet kosong atau header tidak ditemukan.');
    }

    // VALIDASI HEADER (samakan dengan template yang sudah kubuatin)
    $expected = [
        'A' => 'Nama',
        'B' => 'Email',
        'C' => 'Karyawan_ID',
        'D' => 'Jabatan_ID',
        'E' => 'Unit_ID',
        'F' => 'Hire_Date (YYYY-MM-DD)',
    ];

    foreach ($expected as $col => $title) {
        $found = isset($rows[1][$col]) ? trim((string)$rows[1][$col]) : '';
        if ($found !== $title) {
            throw new Exception("Header kolom $col harus '$title', ditemukan '$found'. Gunakan template yang benar.");
        }
    }

    // Siapkan statement reusable
    $stmtCheckEmail    = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheckKaryawan = $conn->prepare("SELECT id FROM karyawans WHERE karyawan_id = ?");
    $stmtCheckJabatan  = $conn->prepare("SELECT id FROM jabatans WHERE id = ?");
    $stmtCheckUnit     = $conn->prepare("SELECT id FROM unit_projects WHERE id = ?");

    $stmtInsertUser = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'karyawan')");
    $stmtInsertUser->bind_param('sss', $nama, $email, $passwordHash);

    $stmtInsertKaryawan = $conn->prepare("INSERT INTO karyawans (karyawan_id, user_id, jabatan_id, unit_project_id, hire_date)
                                          VALUES (?, ?, ?, ?, ?)");
    $stmtInsertKaryawan->bind_param('siiis', $karyawan_id, $user_id, $jabatan_id, $unit_id, $hire_date);

    $errors = [];
    $success = 0;

    $conn->begin_transaction();

    // Mulai dari baris ke-2 (data)
    for ($i = 2; isset($rows[$i]); $i++) {
        $nama         = trim((string)($rows[$i]['A'] ?? ''));
        $email        = trim((string)($rows[$i]['B'] ?? ''));
        $karyawan_id  = trim((string)($rows[$i]['C'] ?? ''));
        $jabatan_id   = (string)($rows[$i]['D'] ?? '');
        $unit_id      = (string)($rows[$i]['E'] ?? '');
        $hire_raw     = $rows[$i]['F'] ?? '';

        // Lewati baris kosong total
        if ($nama === '' && $email === '' && $karyawan_id === '' && $jabatan_id === '' && $unit_id === '' && $hire_raw === '') {
            continue;
        }

        // Validasi wajib isi
        if ($nama === '' || $email === '' || $karyawan_id === '') {
            $errors[] = "Baris $i: Nama/Email/Karyawan_ID wajib diisi.";
            continue;
        }

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Baris $i: Format email tidak valid ('$email').";
            continue;
        }

        // Konversi angka ke int untuk FK
        $jabatan_id = (int)$jabatan_id;
        $unit_id    = (int)$unit_id;

        // Validasi/parse tanggal (boleh serial Excel atau string)
        $hire_date = null;
        if ($hire_raw !== null && $hire_raw !== '') {
            if (is_numeric($hire_raw)) {
                // Excel serial number
                $hire_date = Date::excelToDateTimeObject($hire_raw)->format('Y-m-d');
            } else {
                $dt = date_create($hire_raw);
                if ($dt) {
                    $hire_date = $dt->format('Y-m-d');
                } else {
                    $errors[] = "Baris $i: Tanggal tidak bisa diparsing ('$hire_raw'). Gunakan YYYY-MM-DD.";
                    continue;
                }
            }
        } else {
            // Jika kosong, default ke hari ini (opsional, atau bisa dijadikan error)
            $hire_date = date('Y-m-d');
        }

        // Cek duplikasi email
        $stmtCheckEmail->bind_param('s', $email);
        $stmtCheckEmail->execute();
        $stmtCheckEmail->store_result();
        if ($stmtCheckEmail->num_rows > 0) {
            $errors[] = "Baris $i: Email '$email' sudah terdaftar.";
            continue;
        }

        // Cek duplikasi karyawan_id
        $stmtCheckKaryawan->bind_param('s', $karyawan_id);
        $stmtCheckKaryawan->execute();
        $stmtCheckKaryawan->store_result();
        if ($stmtCheckKaryawan->num_rows > 0) {
            $errors[] = "Baris $i: Karyawan_ID '$karyawan_id' sudah terdaftar.";
            continue;
        }

        // Cek FK jabatan
        if ($jabatan_id <= 0) {
            $errors[] = "Baris $i: Jabatan_ID wajib angka valid.";
            continue;
        }
        $stmtCheckJabatan->bind_param('i', $jabatan_id);
        $stmtCheckJabatan->execute();
        $stmtCheckJabatan->store_result();
        if ($stmtCheckJabatan->num_rows === 0) {
            $errors[] = "Baris $i: Jabatan_ID $jabatan_id tidak ditemukan.";
            continue;
        }

        // Cek FK unit
        if ($unit_id <= 0) {
            $errors[] = "Baris $i: Unit_ID wajib angka valid.";
            continue;
        }
        $stmtCheckUnit->bind_param('i', $unit_id);
        $stmtCheckUnit->execute();
        $stmtCheckUnit->store_result();
        if ($stmtCheckUnit->num_rows === 0) {
            $errors[] = "Baris $i: Unit_ID $unit_id tidak ditemukan.";
            continue;
        }

        // Insert users
        $passwordHash = password_hash('Nutech123', PASSWORD_DEFAULT);
        $stmtInsertUser->execute();
        $user_id = $conn->insert_id;

        // Insert karyawans
        $stmtInsertKaryawan->execute();

        $success++;
    }

    if (!empty($errors)) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Gagal import. Silakan periksa daftar error.',
            'errors'  => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Import berhasil. $success baris ditambahkan."
    ]);

} catch (Throwable $e) {
    // Jika terjadi error tak terduga, rollback dan kirim pesan asli dari DB/PhpSpreadsheet
    if ($conn && $conn->errno === 0) {
        // no-op
    }
    if ($conn) { $conn->rollback(); }
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan tak terduga saat import.',
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
