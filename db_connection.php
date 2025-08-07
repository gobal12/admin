<?php
$servername = "localhost";
$username = "root";
$dbPassword = "";
<<<<<<< HEAD
$dbname = "admin";
=======
$dbname = "portdb";
>>>>>>> 4dfaca510ccf12c8be0198884ff0f62957c3143e

// Create connection to database
$conn = new mysqli($servername, $username, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
