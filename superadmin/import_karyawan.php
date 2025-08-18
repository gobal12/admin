<?php
session_start();
require '../vendor/autoload.php'; 
include '../db_connection.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $conn->begin_transaction();

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // skip header

            list($nama, $email, $karyawan_id, $jabatan_id, $unit_id, $hire_date) = $row;

            if (empty($nama) || empty($email) || empty($karyawan_id)) {
                throw new Exception("Data tidak lengkap di baris ke-" . ($index + 1));
            }

            // convert date jika format excel serial number
            if (is_numeric($hire_date)) {
                $hire_date = Date::excelToDateTimeObject($hire_date)->format('Y-m-d');
            }

            // cek duplikasi email
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                throw new Exception("Email '$email' sudah ada di baris " . ($index + 1));
            }
            $check->close();

            $password = password_hash('Nutech123', PASSWORD_DEFAULT);
            $role = 'karyawan';

            // Insert ke users
            $stmt1 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("ssss", $nama, $email, $password, $role);
            $stmt1->execute();
            $user_id = $stmt1->insert_id;
            $stmt1->close();

            // Insert ke karyawans
            $stmt2 = $conn->prepare("INSERT INTO karyawans (karyawan_id, user_id, jabatan_id, unit_project_id, hire_date) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("siiis", $karyawan_id, $user_id, $jabatan_id, $unit_id, $hire_date);
            $stmt2->execute();
            $stmt2->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Import berhasil!']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Gagal import: ' . $e->getMessage()]);
    }
}
?>
