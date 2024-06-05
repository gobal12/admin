<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

session_start();

require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['Email'];
    $password = $_POST['Password'];

    try {
        // Prevent SQL Injection
        $email = $conn->real_escape_string($email);

        $sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Store data in session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role_name'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['loggedin'] = true;

                // Clear buffer and redirect based on role_name
                ob_end_clean();

                switch ($row['role_name']) {
                    case 'admin':
                        header("Location: index-superadmin/charts.php");
                        break;
                    case 'user':
                        header("Location: index-user/charts.php");
                        break;
                    case 'manager':
                        header("Location: index-manager/charts.php");
                        break;
                    default:
                        throw new Exception("Invalid role");
                }
                exit();
            } else {
                throw new Exception("Invalid password");
            }
        } else {
            throw new Exception("No user found with that email address");
        }
    } catch (Exception $e) {
        ob_end_clean(); // Clear buffer before redirect
        $_SESSION['error_message'] = $e->getMessage(); // Save error message
        header("Location: index.php");
        exit();
    } finally {
        if (isset($conn) && $conn->connect_error == null) {
            $conn->close();
        }
    }
} else {
    // If not a POST request, redirect to the login page
    header("Location: index.php");
    exit();
}
?>
