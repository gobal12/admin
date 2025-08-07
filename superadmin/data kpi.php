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

// Query untuk ambil data penilaian_kpi + join karyawan dan periode
$sql = "SELECT pk.id, k.karyawan_id, u.name AS nama_karyawan, pp.nama_periode, pk.total_nilai, pk.tanggal_input
        FROM penilaian_kpi pk
        JOIN karyawans k ON pk.karyawan_id = k.id
        JOIN users u ON k.user_id = u.id
        JOIN periode_penilaian pp ON pk.periode_id = pp.id
        ORDER BY pk.tanggal_input DESC";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>KPI Nutech Operation - Data Karyawan</title>

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
                    <h1 class="h3 mb-2 text-gray-800">Data KPI</h1>
                    <p class="mb-4">Menampilkan Data KPI yang sudah di Input</p>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
    

    
                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Karyawan</th>
                                        <th>Periode</th>
                                        <th>Total Nilai</th>
                                        <th>Tanggal Input</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if ($result->num_rows > 0): 
                                    ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                                <td><?= number_format($row['total_nilai'], 2) ?></td>
                                                <td><?= htmlspecialchars($row['tanggal_input']) ?></td>
                                                <td>
                                                    <a href="detailkpi.php?penilaian_id=<?= htmlspecialchars($row['id']) ?>" 
                                                    class="btn btn-outline-info btn-sm" 
                                                    title="Lihat Detail Penilaian">
                                                    <i class="fas fa-eye"></i> Detail
                                                    </a>

                                                    <a href="cetak_kpi.php?penilaian_id=<?= htmlspecialchars($row['id']) ?>" 
                                                    class="btn btn-outline-primary btn-sm" 
                                                    title="Cetak Penilaian" target="_blank">
                                                    <i class="fas fa-print"></i> Cetak
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">Tidak ada data penilaian.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
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
    
</body>

</html>
