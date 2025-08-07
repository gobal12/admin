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

// Ambil nama user dari session
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

require_once '../db_connection.php';

// Query baru sesuai struktur database
$sql = "SELECT 
            p.id, 
            p.nama_periode AS periode,
            p.status 
        FROM periode_penilaian p";

$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
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

    <title>KPI Nutech Operation - Data Periode</title>

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

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Data Periode</h1>
                    <p class="mb-4">Menampilkan list Periode</p>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
    
                    <div class="card-header py-3">
                        <a href="addperiode.php" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Tambah Periode</a>
                    </div>

                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $nomor = 1;
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $nomor . "</td>";
                                            echo "<td>" . $row["periode"] . "</td>";
                                            echo "<td>" . $row["status"] . "</td>";
                                            echo "<td>";
                                            echo "<a href='editperiode.php?id=" . $row["id"] . "' class='btn btn-primary' title='Edit'><i class='fas fa-edit'></i></a> ";
                                            echo "<button type='button' class='btn btn-danger' onclick='confirmDelete(" . $row["id"] . ", event)' title='Delete'><i class='far fa-trash-alt'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                            $nomor++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>Data tidak ditemukan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
            <!-- End of Footer -->

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
    
    <!-- Konfirmasi Delete -->
    <script>
    function confirmDelete(id, event) {
        // Prevent the default action of the link
        event.preventDefault();

        // Show the SweetAlert confirmation popup
        Swal.fire({
            title: 'Apakah Kamu Yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus Akun!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the delete URL
                window.location.href = "deleteperiode.php?id=" + id;
            }
        });
    }
    </script>
</body>

</html>
