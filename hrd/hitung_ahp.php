<?php
header('Content-Type: application/json');
include '../db_connection.php';

$response = ['success' => false, 'message' => ''];

try {
    // Ambil periode_id
    $periode_id = null;
    if (isset($_POST['periode_id'])) {
        $periode_id = filter_var($_POST['periode_id'], FILTER_VALIDATE_INT);
    }
    if ($periode_id === false || $periode_id === null) {
        $response['message'] = 'Periode ID tidak valid. Silakan pilih periode terlebih dahulu.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    // ===== [BARU] TAHAP 1: GATEKEEPING (Penjagaan Sesuai Permintaan Anda) =====

    // 1. Hitung total karyawan AKTIF
    // (Di masa depan, Anda bisa menambahkan filter 'unit_id' di sini jika perlu)
    $res_total_aktif = $conn->query("SELECT COUNT(id) AS total FROM karyawans WHERE status = 'Aktif'");
    $total_aktif = (int) $res_total_aktif->fetch_assoc()['total'];

    if ($total_aktif == 0) {
        $conn->rollback();
        $response['message'] = 'Perhitungan AHP Gagal. Tidak ditemukan karyawan "Aktif" di dalam sistem.';
        echo json_encode($response);
        exit;
    }

    // 2. Hitung total karyawan AKTIF yang SUDAH DINILAI di periode ini
    $stmt_total_dinilai = $conn->prepare("
        SELECT COUNT(DISTINCT pk.karyawan_id) AS total
        FROM penilaian_kpi pk
        JOIN karyawans k ON pk.karyawan_id = k.id
        WHERE pk.periode_id = ? AND k.status = 'Aktif'
    ");
    $stmt_total_dinilai->bind_param("i", $periode_id);
    $stmt_total_dinilai->execute();
    $total_dinilai = (int) $stmt_total_dinilai->get_result()->fetch_assoc()['total'];
    $stmt_total_dinilai->close();

    // 3. Bandingkan dan hentikan jika tidak lengkap
    if ($total_dinilai < $total_aktif) {
        $conn->rollback();
        $belum_dinilai = $total_aktif - $total_dinilai;
        $response['message'] = "Perhitungan AHP Gagal. Masih ada $belum_dinilai karyawan aktif yang belum dinilai pada periode ini.";
        echo json_encode($response);
        exit;
    }

    // ===== [TAHAP 2] LANJUTKAN PROSES (Data sudah divalidasi) =====

    // Ambil daftar faktor
    $faktorList = [];
    $fQ = $conn->query("SELECT id FROM faktor_kompetensi ORDER BY id");
    while ($r = $fQ->fetch_assoc()) $faktorList[] = (int)$r['id'];

    // Ambil bobot AHP
    $bobotAHP = [];
    $bQ = $conn->query("SELECT faktor_id, bobot FROM bobot_ahp");
    while ($r = $bQ->fetch_assoc()) $bobotAHP[(int)$r['faktor_id']] = (float)$r['bobot'];

    // Ambil semua karyawan AKTIF (DAN CATATANNYA) yang punya penilaian
    // Filter k.status = 'Aktif' di sini penting agar daftarnya sama persis
    // dengan yang kita hitung di 'total_dinilai'.
    $karyawanData = [];
    $kQ_sql_stmt = $conn->prepare("
        SELECT DISTINCT pk.karyawan_id, pk.catatan 
        FROM penilaian_kpi pk
        JOIN karyawans k ON pk.karyawan_id = k.id 
        WHERE pk.periode_id = ? AND k.status = 'Aktif'
    ");
    $kQ_sql_stmt->bind_param("i", $periode_id);
    $kQ_sql_stmt->execute();
    $kQ = $kQ_sql_stmt->get_result();
    while ($r = $kQ->fetch_assoc()) {
        $karyawanData[(int)$r['karyawan_id']] = $r['catatan'] ?? '';
    }
    $kQ_sql_stmt->close();


    // Rata-rata nilai per faktor
    $stmt = $conn->prepare("
        SELECT pk.karyawan_id, ik.faktor_id, AVG(dp.nilai) AS avg_nilai
        FROM detail_penilaian dp
        JOIN penilaian_kpi pk ON pk.id = dp.penilaian_id
        JOIN indikator_kompetensi ik ON ik.id = dp.indikator_id
        JOIN karyawans k ON pk.karyawan_id = k.id -- Pastikan hanya ambil dari karyawan aktif
        WHERE pk.periode_id = ? AND k.status = 'Aktif'
        GROUP BY pk.karyawan_id, ik.faktor_id
    ");
    $stmt->bind_param("i", $periode_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $avg = [];
    $max = [];
    while ($row = $res->fetch_assoc()) {
        $kid = (int)$row['karyawan_id'];
        $fid = (int)$row['faktor_id'];
        $val = (float)$row['avg_nilai'];
        $avg[$kid][$fid] = $val;
        if (!isset($max[$fid]) || $val > $max[$fid]) $max[$fid] = $val;
    }
    $stmt->close();
    
    // (Pengecekan 'empty($avg)' tidak lagi diperlukan karena sudah divalidasi di TAHAP 1)

    // Siapkan statement SQL
    $insertDetailStmt = $conn->prepare("
        INSERT INTO detail_penilaian_ahp (penilaian_id, periode_id, faktor_id, nilai, hasil)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertHeaderStmt = $conn->prepare("
        INSERT INTO penilaian_kpi_ahp (karyawan_id, total_nilai, catatan, periode_id)
        VALUES (?, ?, ?, ?)
    ");
    $updateHeaderStmt = $conn->prepare("
        UPDATE penilaian_kpi_ahp SET total_nilai = ?, catatan = ? WHERE id = ?
    ");

    // Loop HANYA untuk karyawan yang lolos validasi (semua karyawan aktif)
    foreach ($karyawanData as $karyawan_id => $catatan_manajer) {
        $total_skor = 0.0;

        // Cek/Hapus data AHP lama
        $cek = $conn->query("SELECT id FROM penilaian_kpi_ahp WHERE karyawan_id = $karyawan_id AND periode_id = $periode_id LIMIT 1");
        if ($cek && $cek->num_rows > 0) {
            $penilaian_id = (int)$cek->fetch_assoc()['id'];
            $conn->query("DELETE FROM detail_penilaian_ahp WHERE penilaian_id = $penilaian_id");
        } else {
            // Insert header baru dengan catatan dari manajer
            $insertHeaderStmt->bind_param("idsi", $karyawan_id, $total_skor, $catatan_manajer, $periode_id);
            $insertHeaderStmt->execute();
            $penilaian_id = (int)$insertHeaderStmt->insert_id;
        }

        // Hitung dan masukkan detail
        foreach ($faktorList as $faktor_id) {
            $nilaiKaryawan = isset($avg[$karyawan_id][$faktor_id]) ? (float)$avg[$karyawan_id][$faktor_id] : 0.0;
            $nilaiMax = isset($max[$faktor_id]) ? (float)$max[$faktor_id] : 0.0;

            $nilai_normalisasi = ($nilaiMax > 0.0) ? ($nilaiKaryawan / $nilaiMax) : 0.0;
            $bobot = isset($bobotAHP[$faktor_id]) ? (float)$bobotAHP[$faktor_id] : 0.0;
            $hasil = $nilai_normalisasi * $bobot;

            $insertDetailStmt->bind_param("iiidd", $penilaian_id, $periode_id, $faktor_id, $nilai_normalisasi, $hasil);
            $insertDetailStmt->execute();

            $total_skor += $hasil;
        }

        // Update header dengan total skor akhir (catatan sudah di-set saat insert)
        $updateHeaderStmt->bind_param("dsi", $total_skor, $catatan_manajer, $penilaian_id);
        $updateHeaderStmt->execute();
    }

    $conn->commit();
    $insertDetailStmt->close();
    $insertHeaderStmt->close();
    $updateHeaderStmt->close();

    $response['success'] = true;
    $response['message'] = "Perhitungan AHP untuk $total_dinilai karyawan aktif pada periode $periode_id berhasil.";
} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = "Terjadi error: " . $e->getMessage();
}

echo json_encode($response);
?>