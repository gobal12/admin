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

$faktor = [];
$weights = [];
$res = $conn->query("SELECT fk.nama, b.bobot FROM bobot_ahp b JOIN faktor_kompetensi fk ON b.faktor_id=fk.id");
while ($row = $res->fetch_assoc()) {
    $faktor[] = $row['nama'];
    $weights[] = $row['bobot'];
}

// Ambil hasil konsistensi
$res2 = $conn->query("SELECT * FROM ahp_konsistensi LIMIT 1");
$konsistensi = $res2->fetch_assoc();
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
                <div class="container-fluid">
                <h3 class="mb-4">Hasil Perhitungan AHP</h3>

                <!-- Tombol ke form input -->
                <div class="card-header py-3">
                    <a href="ahp_input.php" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Update Perbandingan</a>           
                    <a href="ahp.php" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Hitung Penilaian</a>
                </div>
                <div class="row">
                    <!-- Card Tabel Bobot -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 bg-primary text-white">
                                <h6 class="m-0 font-weight-bold">Tabel Bobot Faktor</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Faktor</th>
                                            <th>Bobot</th>
                                            <th>Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($faktor as $i => $nama): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($nama) ?></td>
                                                <td><?= number_format($weights[$i], 4) ?></td>
                                                <td><?= number_format($weights[$i] * 100, 2) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Card Uji Konsistensi -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 bg-success text-white">
                                <h6 class="m-0 font-weight-bold">Uji Konsistensi</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($konsistensi): ?>
                                    <p><strong>λmax:</strong> <?= number_format($konsistensi['lambda_max'], 4) ?></p>
                                    <p><strong>CI:</strong> <?= number_format($konsistensi['ci'], 4) ?></p>
                                    <p><strong>CR:</strong> <?= number_format($konsistensi['cr'], 4) ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge <?= ($konsistensi['status'] == 'Konsisten') ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $konsistensi['status'] ?>
                                        </span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted">Belum ada data konsistensi.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
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
                        .then(() => window.location.href = 'datajabatan.php');
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
