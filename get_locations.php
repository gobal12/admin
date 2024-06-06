<?php
require_once '../db_connection.php';

$device_type_id = $_GET['device_type_id'];

$query = "";
switch ($device_type_id) {
    case '1001':
        $query = "SELECT id_lokasi_cl AS id, lokasi_vm AS name FROM lokasi_cloud WHERE id_perangkat='$device_type_id'";
        break;
    case '1002':
        $query = "SELECT id_lokasi_server AS id, lokasi_server AS name FROM lokasi_server WHERE id_perangkat='$device_type_id'";
        break;
    case '1003':
        $query = "SELECT id_lokasi_lks AS id, lokasi_lks AS name FROM lokasi_lks WHERE id_perangkat='$device_type_id'";
        break;
    case '1004':
        $query = "SELECT id_lokasi_lkns AS id, lokasi_lkns AS name FROM lokasi_lkns WHERE id_perangkat='$device_type_id'";
        break;
    case '1005':
        $query = "SELECT id_lokasi_lkm AS id, lokasi_loket_motor AS name FROM lokasi_lkm WHERE id_perangkat='$device_type_id'";
        break;
    case '1006':
        $query = "SELECT id_lokasi_lpnp AS id, lokasi_loket_penumpang AS name FROM lokasi_lpnp WHERE id_perangkat='$device_type_id'";
        break;
    case '1007':
        $query = "SELECT id_lokasi_vm AS id, lokasi_vm AS name FROM lokasi_vm WHERE id_perangkat='$device_type_id'";
        break;
    case '1008':
        $query = "SELECT id_lokasi_gatein AS id, lokasi_gatein AS name FROM lokasi_gatein WHERE id_perangkat='$device_type_id'";
        break;
    case '1009':
        $query = "SELECT id_lokasi_gbrd AS id, lokasi_gate_boarding AS name FROM lokasi_gbrd WHERE id_perangkat='$device_type_id'";
        break;
    case '1010':
        $query = "SELECT id_lokasi_gbk AS id, lokasi_dermaga AS name FROM lokasi_gbk WHERE id_perangkat='$device_type_id'";
        break;
    // Add more cases as needed
}

$result = $conn->query($query);
$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

echo json_encode($locations);
$conn->close();
?>
