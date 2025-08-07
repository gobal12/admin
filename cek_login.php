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

        // Tidak ada join dengan tabel 'roles'
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                // Store session data
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role']; // langsung ambil dari kolom users.role
                $_SESSION['name'] = $row['name'];
                $_SESSION['loggedin'] = true;

                // Clear buffer and redirect based on role
                ob_end_clean();

                switch ($row['role']) {
                    case 'admin':
                        header("Location: superadmin/charts.php");
                        break;
                    case 'hrd':
                        header("Location: hrd/charts.php");
                        break;
                    case 'manager':
                        header("Location: manager/charts.php");
                        break;
                    case 'karyawan':
                        header("Location: karyawan/charts.php");
                        break;
                    default:
                        throw new Exception("Role tidak valid");
                }
                exit();
            } else {
                throw new Exception("Password salah");
            }
        } else {
            throw new Exception("Email tidak ditemukan");
        }
    } catch (Exception $e) {
        ob_end_clean(); // Clear buffer before redirect
        $_SESSION['error_message'] = $e->getMessage(); // Simpan pesan error ke session
        header("Location: index.php"); // Redirect kembali ke halaman login
        exit();
    } finally {
        if (isset($conn) && $conn->connect_error == null) {
            $conn->close();
        }
    }
} else {
    // Bukan POST, redirect ke login
    header("Location: index.php");
    exit();
}
