<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbport";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT lokasi_perangkat, COUNT(*) as count FROM report GROUP BY lokasi_perangkat";
$result = $conn->query($sql);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>
