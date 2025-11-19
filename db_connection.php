<?php
$servername = "localhost";
$username = "root";
$dbPassword = "";
$dbname = "admin";

// Create connection to database
$conn = new mysqli($servername, $username, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
