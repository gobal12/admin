<?php
session_start();
include '../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Akses tidak valid.']);
    exit;
}

$karyawan_id = $_POST['karyawan_id'] ?? null;
$periode_id = $_POST['periode_id'] ?? null;
$nilai_input = $_POST['nilai'] ?? [];
$catatan = $_POST['catatan'] ?? null;

if (!$karyawan_id || !$periode_id || empty($nilai_input)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Insert penilaian_kpi sementara dengan total_nilai = 0
    $stmt = $conn->prepare("INSERT INTO penilaian_kpi (karyawan_id, periode_id, total_nilai, catatan) VALUES (?, ?, 0, ?)");
    $stmt->bind_param("iis", $karyawan_id, $periode_id, $catatan);
    $stmt->execute();
    $penilaian_id = $stmt->insert_id;
    $stmt->close();

    $total_nilai = 0;

    // 2. Proses setiap indikator yang dinilai
    foreach ($nilai_input as $indikator_id => $nilai) {
        // Ambil target dari DB
        $stmt = $conn->prepare("SELECT target FROM indikator_kompetensi WHERE id = ?");
        $stmt->bind_param("i", $indikator_id);
        $stmt->execute();
        $stmt->bind_result($target);
        $stmt->fetch();
        $stmt->close();

        if ($target === null) {
            throw new Exception("Target tidak ditemukan untuk indikator ID $indikator_id");
        }

        // Hitung hasil
        $hasil = $nilai * $target / 100;
        $total_nilai += $hasil;

        // Simpan detail penilaian
        $stmt = $conn->prepare("INSERT INTO detail_penilaian (penilaian_id, indikator_id, nilai, hasil) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iidd", $penilaian_id, $indikator_id, $nilai, $hasil);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Update total_nilai di tabel penilaian_kpi
    $stmt = $conn->prepare("UPDATE penilaian_kpi SET total_nilai = ? WHERE id = ?");
    $stmt->bind_param("di", $total_nilai, $penilaian_id);
    $stmt->execute();
    $stmt->close();

    // 4. Commit semua perubahan
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Penilaian berhasil disimpan.',
        'total_nilai' => $total_nilai
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan data: ' . $e->getMessage()
    ]);
}
