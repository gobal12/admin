<?php
require_once 'db_connection.php';

$sql = "SELECT layanan_terdampak, COUNT(*) as count FROM report GROUP BY layanan_terdampak";
$result = $conn->query($sql);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>
