<?php
session_start();
include '../db_connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Akses tidak valid.']);
    exit;
}

// Ambil data dari form BARU
$karyawan_id = $_POST['karyawan_id'] ?? null;
$periode_id = $_POST['periode_id'] ?? null;
$catatan = $_POST['catatan'] ?? null;
$nilai_array = $_POST['nilai'] ?? [];          // Array [indikator_id => nilai 1-4]
$bobot_array = $_POST['bobot_indikator'] ?? []; // Array [indikator_id => bobot %]

if (!$karyawan_id || !$periode_id || empty($nilai_array) || empty($bobot_array)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap (Karyawan, Periode, atau Nilai).']);
    exit;
}

try {
    // 🔹 Cek apakah penilaian sudah ada (Logika Anda sudah benar)
    $stmt_cek = $conn->prepare("SELECT id FROM penilaian_kpi WHERE karyawan_id = ? AND periode_id = ?");
    $stmt_cek->bind_param("ii", $karyawan_id, $periode_id);
    $stmt_cek->execute();
    $stmt_cek->store_result();

    if ($stmt_cek->num_rows > 0) {
        $stmt_cek->close();
        echo json_encode([
            'success' => false,
            'message' => 'Penilaian KPI untuk karyawan ini pada periode tersebut sudah ada.'
        ]);
        exit;
    }
    $stmt_cek->close();

    // 🔹 Mulai transaksi (Logika Anda sudah benar)
    $conn->begin_transaction();

    // 1. Insert penilaian_kpi (master)
    $stmt_master = $conn->prepare("INSERT INTO penilaian_kpi (karyawan_id, periode_id, total_nilai, catatan) VALUES (?, ?, 0, ?)");
    $stmt_master->bind_param("iis", $karyawan_id, $periode_id, $catatan);
    $stmt_master->execute();
    $penilaian_id = $stmt_master->insert_id; // Ini adalah 'penilaian_id' untuk tabel detail
    $stmt_master->close();

    $grand_total_score = 0;
    $detail_to_insert = []; // Tampung data detail

    // 2. Proses setiap indikator yang dinilai
    foreach ($nilai_array as $indikator_id => $nilai) {
        
        // Pastikan bobot untuk indikator ini juga dikirim
        if (!isset($bobot_array[$indikator_id])) {
            throw new Exception("Data bobot tidak ditemukan untuk indikator ID $indikator_id");
        }

        $nilai_skala_1_4 = intval($nilai);
        $bobot_indikator = floatval($bobot_array[$indikator_id]);

        // --- RUMUS KALKULASI BARU ---
        // Hasil = Nilai (1-4) * (Bobot Indikator / 100)
        // Cth: 4 * (25 / 100) = 4 * 0.25 = 1.00
        $hasil_score = $nilai_skala_1_4 * ($bobot_indikator / 100);
        $grand_total_score += $hasil_score;

        // Kumpulkan data untuk insert detail
        $detail_to_insert[] = [
            'indikator_id' => intval($indikator_id),
            'nilai' => $nilai_skala_1_4,
            'hasil' => $hasil_score
        ];
    }

    // 3. Simpan detail penilaian (batch insert)
    $stmt_detail = $conn->prepare("INSERT INTO detail_penilaian (penilaian_id, indikator_id, nilai, hasil) VALUES (?, ?, ?, ?)");
    foreach ($detail_to_insert as $detail) {
        $stmt_detail->bind_param(
            "iidd",
            $penilaian_id,
            $detail['indikator_id'],
            $detail['nilai'],
            $detail['hasil']
        );
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    // 4. Update total_nilai di tabel master (penilaian_kpi)
    $stmt_update = $conn->prepare("UPDATE penilaian_kpi SET total_nilai = ? WHERE id = ?");
    $stmt_update->bind_param("di", $grand_total_score, $penilaian_id);
    $stmt_update->execute();
    $stmt_update->close();

    // 5. Commit semua perubahan
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Penilaian berhasil disimpan. Total Score: ' . number_format($grand_total_score, 2),
        'total_nilai' => number_format($grand_total_score, 2)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan data: ' . $e->getMessage()
    ]);
}

$conn->close();
?>