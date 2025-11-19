<?php
session_start();
include '../db_connection.php';

// Set header untuk memberitahu klien bahwa respons adalah JSON
header('Content-Type: application/json');

// Pastikan request adalah POST dan ada data matrix
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['matrix'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing data.']);
    exit();
}

// Ambil data faktor
$faktor = [];
$res = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $faktor[] = $row;
}
$n = count($faktor);

function hitungKonsistensi($matrix, $bobot) {
    $n = count($matrix);
    if ($n <= 1) return ['lambda_max' => 0, 'CI' => 0, 'CR' => 0, 'status' => "Konsisten"];
    
    $lambdaMax = 0;
    // Perhitungan Lambda Max (A * W) / W
    for ($i = 0; $i < $n; $i++) {
        $rowSum = 0;
        for ($j = 0; $j < $n; $j++) {
            $rowSum += $matrix[$i][$j] * $bobot[$j];
        }
        // Hindari pembagian dengan nol jika bobot[i] adalah nol
        if ($bobot[$i] != 0) {
            $lambdaMax += $rowSum / $bobot[$i];
        }
    }
    $lambdaMax /= $n;
    
    $CI = ($lambdaMax - $n) / ($n - 1);
    $RI_table = [1=>0.00,2=>0.00,3=>0.58,4=>0.90,5=>1.12,6=>1.24,7=>1.32,8=>1.41,9=>1.45,10=>1.49];
    $RI = $RI_table[$n] ?? 1.49;
    $CR = ($RI == 0) ? 0 : $CI / $RI;
    
    return [
        'lambda_max' => $lambdaMax,
        'CI' => $CI,
        'CR' => $CR,
        'status' => ($CR <= 0.1) ? "Konsisten" : "Tidak Konsisten"
    ];
}

try {
    // 1. Ambil input matrix dari form & Siapkan Data
    $matrix = array_fill(0, $n, array_fill(0, $n, 1));

    // Looping untuk membangun matriks dari input POST (hanya segitiga atas)
    foreach ($_POST['matrix'] as $i => $row) {
        foreach ($row as $j => $value) {
            $val = floatval($value);
            
            // Masukkan nilai input dan nilai kebalikannya untuk perhitungan
            $matrix[$i][$j] = $val;
            $matrix[$j][$i] = 1 / $val;
        }
    }

    // --- PENYIMPANAN DATA MENTAH MATRIX (ahp_matrix) ---
    $conn->query("TRUNCATE TABLE ahp_matrix");
    $stmtMatrix = $conn->prepare("INSERT INTO ahp_matrix (faktor_id_1, faktor_id_2, nilai) VALUES (?, ?, ?)");

    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $fid1 = $faktor[$i]['id'];
            $fid2 = $faktor[$j]['id'];
            $val  = $matrix[$i][$j];
            
            $stmtMatrix->bind_param("iid", $fid1, $fid2, $val);
            $stmtMatrix->execute();
        }
    }
    $stmtMatrix->close();

    // --- PERHITUNGAN AHP ---

    // Hitung jumlah kolom
    $sumCols = array_fill(0, $n, 0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $sumCols[$j] += $matrix[$i][$j];
        }
    }

    // Normalisasi
    $normalized = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            // Hindari pembagian dengan nol
            $normalized[$i][$j] = ($sumCols[$j] != 0) ? $matrix[$i][$j] / $sumCols[$j] : 0;
        }
    }

    // Hitung bobot (rata-rata baris matriks normalisasi)
    $weights = [];
    for ($i = 0; $i < $n; $i++) {
        $weights[$i] = array_sum($normalized[$i]) / $n;
    }

    // --- PENYIMPANAN BOBOT (bobot_ahp) ---
    $conn->query("TRUNCATE TABLE bobot_ahp");
    $stmt = $conn->prepare("INSERT INTO bobot_ahp (faktor_id, bobot) VALUES (?, ?)");
    foreach ($faktor as $idx => $fk) {
        $stmt->bind_param("id", $fk['id'], $weights[$idx]);
        $stmt->execute();
    }
    $stmt->close();

    // --- HITUNG DAN SIMPAN KONSISTENSI (ahp_konsistensi) ---
    $hasilKonsistensi = hitungKonsistensi($matrix, $weights);

    $conn->query("TRUNCATE TABLE ahp_konsistensi");
    $stmt = $conn->prepare("INSERT INTO ahp_konsistensi (lambda_max, ci, cr, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ddds", $hasilKonsistensi['lambda_max'], $hasilKonsistensi['CI'], $hasilKonsistensi['CR'], $hasilKonsistensi['status']);
    $stmt->execute();
    $stmt->close();

    // Respons JSON SUKSES
    echo json_encode([
        'success' => true, 
        'message' => 'Perhitungan dan Bobot berhasil disimpan.',
        'consistency' => $hasilKonsistensi['status']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
?>