<?php
session_start();
include '../db_connection.php';


// Ambil data faktor
$faktor = [];
$res = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $faktor[] = $row;
}
$n = count($faktor);

function hitungKonsistensi($matrix, $bobot) {
    $n = count($matrix);
    $lambdaMax = 0;
    for ($i = 0; $i < $n; $i++) {
        $rowSum = 0;
        for ($j = 0; $j < $n; $j++) {
            $rowSum += $matrix[$i][$j] * $bobot[$j];
        }
        $lambdaMax += $rowSum / $bobot[$i];
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

// Ambil input matrix dari form
$matrix = array_fill(0, $n, array_fill(0, $n, 1));
foreach ($_POST['matrix'] as $i => $row) {
    foreach ($row as $j => $value) {
        $matrix[$i][$j] = floatval($value);
        $matrix[$j][$i] = 1 / floatval($value);
    }
}

// Hitung bobot
$sumCols = array_fill(0, $n, 0);
for ($j = 0; $j < $n; $j++) {
    for ($i = 0; $i < $n; $i++) {
        $sumCols[$j] += $matrix[$i][$j];
    }
}

$normalized = [];
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $n; $j++) {
        $normalized[$i][$j] = $matrix[$i][$j] / $sumCols[$j];
    }
}

$weights = [];
for ($i = 0; $i < $n; $i++) {
    $weights[$i] = array_sum($normalized[$i]) / $n;
}

// Simpan ke DB
$conn->query("TRUNCATE TABLE bobot_ahp");
$stmt = $conn->prepare("INSERT INTO bobot_ahp (faktor_id, bobot) VALUES (?, ?)");
foreach ($faktor as $idx => $fk) {
    $stmt->bind_param("id", $fk['id'], $weights[$idx]);
    $stmt->execute();
}
$stmt->close();

// Hitung konsistensi
$hasilKonsistensi = hitungKonsistensi($matrix, $weights);

// Simpan ke DB (hapus dulu isi lama biar cuma ada 1 data)
$conn->query("TRUNCATE TABLE ahp_konsistensi");
$stmt = $conn->prepare("INSERT INTO ahp_konsistensi (lambda_max, ci, cr, status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ddds", $hasilKonsistensi['lambda_max'], $hasilKonsistensi['CI'], $hasilKonsistensi['CR'], $hasilKonsistensi['status']);
$stmt->execute();
$stmt->close();

// Redirect ke halaman hasil
header("Location: ahp_result.php");
exit();
