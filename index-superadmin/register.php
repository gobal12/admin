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
            echo "<script>
                    alert('New record created successfully');
                    window.location.href = 'datauser.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . $sql . "<br>" . $conn->error . "');
                    window.location.href = 'register.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Invalid role selected');
                window.location.href = 'register.php';
              </script>";
    }

    $conn->close();
}
?>
