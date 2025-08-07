<?php
session_start();

//Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('admin');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

include '../db_connection.php';

// Ambil data jabatan & unit_project untuk select option
$jabatan = $conn->query("SELECT id, name FROM jabatans");
$unit = $conn->query("SELECT id, name FROM unit_projects");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data
    $nama         = $_POST['nama'] ?? '';
    $email        = $_POST['email'] ?? '';
    $password     = password_hash('Nutech123', PASSWORD_DEFAULT);
    $role         = 'karyawan';

    $conn->begin_transaction(); // ✅ Mulai transaksi

    try {
        // Insert ke tabel users
        $stmt1 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("ssss", $nama, $email, $password, $role);
        $stmt1->execute();
        $user_id = $stmt1->insert_id;
        $stmt1->close();

        $conn->commit(); // ✅ Commit jika berhasil semua
        echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan.']);
    } catch (Exception $e) {
        $conn->rollback(); // ❌ Rollback jika salah satu gagal
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>KPI Nutech Operation - Data Jabatan</title>

    <!-- Custom fonts for this template -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!--Konfirmasi Delete -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

                <!-- Begin Page Content -->
                <div class="container fluid">
                    <h2 class="mb-4">Tambah User</h2>
                    <form id="formUser">
                        <div class="form-group mb-3">
                            <label>Nama</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="datakaryawan.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>

            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="../js/demo/datatables-demo.js"></script>
    
    <!-- Konfirmasi Add Jabatan -->
    <script>
    document.getElementById("formUser").addEventListener("submit", function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch("", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data user berhasil ditambahkan.'
                }).then(() => {
                    window.location.href = 'adduser.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Terjadi kesalahan.'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        });
    });
    </script>

</body>

</html>
