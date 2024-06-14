<?php
session_start();
require_once '../db_connection.php';

// Function to check user role
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

// Check access for admin
check_role('admin');

// Get ticket ID from the request
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

// Redirect URL back to datareport.php with query parameters
$redirect_url = "datareport.php";

// Append existing query parameters
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $query_params);
    unset($query_params['ticket_id']);  // Remove the ticket_id parameter
    $redirect_url .= '?' . http_build_query($query_params);
}

if ($ticket_id > 0) {
    // Fetch current status
    $sql = "SELECT status FROM report WHERE id_report = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $status = $result->fetch_assoc()['status'];

    // Check if status is 'open'
    if ($status === 'open') {
        $tanggal_close = date("Y-m-d H:i:s");
        $sql = "UPDATE report SET status='close', tanggal_close=? WHERE id_report=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $tanggal_close, $ticket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: " . $redirect_url . "&status=success");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: " . $redirect_url . "&status=error");
            exit();
        }
    } else {
        header("Location: " . $redirect_url . "&status=not_open");
        exit();
    }
} else {
    header("Location: " . $redirect_url . "&status=invalid_id");
    exit();
}
?>
