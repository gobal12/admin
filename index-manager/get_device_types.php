<?php

require_once '../db_connection.php';

$sql = "SELECT id_perangkat, nama_perangkat FROM jenis_perangkat";
$result = $conn->query($sql);

$device_types = array();
while($row = $result->fetch_assoc()) {
    $device_types[] = $row;
}

echo json_encode($device_types);

$conn->close();
?>

