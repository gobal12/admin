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

    // Ambil daftar faktor
    $faktorList = [];
    $fQ = $conn->query("SELECT id FROM faktor_kompetensi ORDER BY id");
    while ($r = $fQ->fetch_assoc()) $faktorList[] = (int)$r['id'];

    // Ambil bobot AHP
    $bobotAHP = [];
    $bQ = $conn->query("SELECT faktor_id, bobot FROM bobot_ahp");
    while ($r = $bQ->fetch_assoc()) $bobotAHP[(int)$r['faktor_id']] = (float)$r['bobot'];

    // Ambil semua karyawan yang punya penilaian di periode ini
    $karyawanList = [];
    $kQ = $conn->query("SELECT DISTINCT pk.karyawan_id FROM penilaian_kpi pk WHERE pk.periode_id = $periode_id");
    while ($r = $kQ->fetch_assoc()) $karyawanList[] = (int)$r['karyawan_id'];

    // Rata-rata nilai per faktor
    $stmt = $conn->prepare("
        SELECT pk.karyawan_id, ik.faktor_id, AVG(dp.nilai) AS avg_nilai
        FROM detail_penilaian dp
        JOIN penilaian_kpi pk ON pk.id = dp.penilaian_id
        JOIN indikator_kompetensi ik ON ik.id = dp.indikator_id
        WHERE pk.periode_id = ?
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

    // Cek jika tidak ada data rata-rata (avg kosong)
    if (empty($avg)) {
        $conn->rollback();
        $response['message'] = "Data penilaian tidak ditemukan untuk periode $periode_id.";
        echo json_encode($response);
        exit;
    }

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

    foreach ($karyawanList as $karyawan_id) {
        $total_skor = 0.0;

        $cek = $conn->query("SELECT id FROM penilaian_kpi_ahp WHERE karyawan_id = $karyawan_id AND periode_id = $periode_id LIMIT 1");
        if ($cek && $cek->num_rows > 0) {
            $penilaian_id = (int)$cek->fetch_assoc()['id'];
            $conn->query("DELETE FROM detail_penilaian_ahp WHERE penilaian_id = $penilaian_id");
        } else {
            $cat = '';
            $insertHeaderStmt->bind_param("idsi", $karyawan_id, $total_skor, $cat, $periode_id);
            $insertHeaderStmt->execute();
            $penilaian_id = (int)$insertHeaderStmt->insert_id;
        }

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

        $catatan = "Hasil perhitungan AHP otomatis";
        $updateHeaderStmt->bind_param("dsi", $total_skor, $catatan, $penilaian_id);
        $updateHeaderStmt->execute();
    }

    $conn->commit();
    $insertDetailStmt->close();
    $insertHeaderStmt->close();
    $updateHeaderStmt->close();

    $response['success'] = true;
    $response['message'] = "Perhitungan AHP untuk periode $periode_id berhasil.";
} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = "Terjadi error: " . $e->getMessage();
}

echo json_encode($response);
