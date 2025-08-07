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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_unit = trim($_POST['nama_unit']);

    if (!empty($nama_unit)) {
        $stmt = $conn->prepare("INSERT INTO unit_projects (name) VALUES (?)");
        $stmt->bind_param("s", $nama_unit);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nama Unit / Project tidak boleh kosong']);
        exit();
    }
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

    <title>KPI Nutech Operation - Data Unit / Project</title>

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
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Tambah Unit/Project</h1>
                    <div class="card">
                        <div class="card-body">
                            <form id="jabatanForm">
                                <div class="form-group">
                                    <label for="nama_unit">Nama Unit / Project</label>
                                    <input type="text" class="form-control" id="nama_unit" name="nama_unit" required>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="DataUnit_Projects.php" class="btn btn-secondary">Kembali</a>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- End of Main Content -->

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
        document.getElementById('jabatanForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil', 'Jabatan berhasil ditambahkan', 'success')
                        .then(() => window.location.href = 'DataJabatan.php');
                } else {
                    Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Gagal mengirim data', 'error');
            });
        });
    </script>
</body>

</html>
