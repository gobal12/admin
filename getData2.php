<?php
require_once 'db_connection.php';

$sql = "SELECT lokasi_perangkat, COUNT(*) as count FROM report GROUP BY lokasi_perangkat";
$result = $conn->query($sql);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>
