<?php
require_once '../db_connection.php';

// Query to get data from the report table
$sql = "SELECT jenis_perangkat, lokasi_perangkat, layanan_terdampak FROM report";
$result = $conn->query($sql);

$data = array();

if ($result->num_rows > 0) {
    // Fetch data from the result set
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Close the connection
$conn->close();

// Return the data in JSON format
echo json_encode($data);
?>
