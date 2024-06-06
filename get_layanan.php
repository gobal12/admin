<?php
require_once '../db_connection.php';

$sql = "SELECT id_layanan, nama_layanan FROM layanan";
$result = $conn->query($sql);

$layanans = [];
while ($row = $result->fetch_assoc()) {
    $layanans[] = $row;
}

echo json_encode($layanans);
$conn->close();
?>
