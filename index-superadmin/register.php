<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['FirstName'];
    $lastName = $_POST['LastName'];
    $email = $_POST['Email'];
    $divisi = $_POST['Divisi'];
    $role = $_POST['Role'];
    $plainPassword = $_POST['Password'];
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    require_once '../db_connection.php';

    // Get role_id from roles table
    $roleQuery = "SELECT id FROM roles WHERE role_name='$role'";
    $roleResult = $conn->query($roleQuery);
    if ($roleResult->num_rows > 0) {
        $roleRow = $roleResult->fetch_assoc();
        $roleId = $roleRow['id'];

        $sql = "INSERT INTO users (first_name, last_name, email, divisi, role_id, password) VALUES ('$firstName', '$lastName', '$email', '$divisi', '$roleId', '$hashedPassword')";

        if ($conn->query($sql) === TRUE) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'New record created successfully'
                    }).then(function() {
                        window.location.href = 'datauser.php';
                    });
                  </script>";
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error: " . $conn->error . "'
                    }).then(function() {
                        window.location.href = 'register.php';
                    });
                  </script>";
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid role',
                    text: 'Invalid role selected'
                }).then(function() {
                    window.location.href = 'register.php';
                });
              </script>";
    }

    $conn->close();
}
?>
