<?php
// Set the content type to application/json
header('Content-Type: application/json');

// Get the JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check for JSON parsing errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Extract data from JSON
$nomor_tiket = $data['nomor_tiket'] ?? null;
$tanggal_open = $data['tanggal_open'] ?? null;
$pelabuhan = $data['pelabuhan'] ?? null;
$jenis_perangkat = $data['jenis_perangkat'] ?? null;
$lokasi_perangkat = $data['lokasi_perangkat'] ?? null;
$layanan_terdampak = $data['layanan_terdampak'] ?? null;
$keterangan = $data['keterangan'] ?? '';

// Validate required fields
if (!$nomor_tiket || !$tanggal_open || !$pelabuhan || !$jenis_perangkat || !$lokasi_perangkat) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Simulate database connection
// Replace these variables with your actual database connection settings
$host = 'localhost';
$db = 'dbport2';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO report (nomor_tiket, tanggal_open, pelabuhan, jenis_perangkat, lokasi_perangkat, layanan_terdampak, keterangan) VALUES (:nomor_tiket, :tanggal_open, :pelabuhan, :jenis_perangkat, :lokasi_perangkat, :layanan_terdampak, :keterangan)");

    // Bind parameters
    $stmt->bindParam(':nomor_tiket', $nomor_tiket);
    $stmt->bindParam(':tanggal_open', $tanggal_open);
    $stmt->bindParam(':pelabuhan', $pelabuhan);
    $stmt->bindParam(':jenis_perangkat', $jenis_perangkat);
    $stmt->bindParam(':lokasi_perangkat', $lokasi_perangkat);
    $stmt->bindParam(':layanan_terdampak', $layanan_terdampak);
    $stmt->bindParam(':keterangan', $keterangan);

    // Execute the statement
    $stmt->execute();

    // Return success response
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
