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

// Ambil daftar periode
$periode = $conn->query("SELECT * FROM periode_penilaian ORDER BY tanggal_mulai DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Perhitungan AHP</title>

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
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>Perhitungan AHP Global</h5>
                        </div>
                        <div class="card-body">
                            <form id="form">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label for="periode_id" class="form-label fw-bold">
                                            <i class="fas fa-calendar-alt me-1"></i>Pilih Periode
                                        </label>
                                        <select name="periode_id" id="periode_id" class="form-control shadow-sm" required>
                                            <option value="">-- Pilih Periode --</option>
                                            <?php while ($p = $periode->fetch_assoc()) : ?>
                                                <option value="<?= $p['id'] ?>">
                                                    <?= htmlspecialchars($p['nama_periode']) ?> (<?= $p['Status'] ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success w-100 shadow-sm">
                                            <i class="fas fa-cogs me-1"></i>Proses Semua
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <!-- End of Main Content -->
                                            </div>
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
    
    <!-- Konfirmasi Hitung Penilaian dengan AHP -->
    <script>
    document.querySelector("form").addEventListener("submit", function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch("hitung_ahp.php", {
            method: "POST",
            body: formData
        })
        
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Berhasil' : 'Gagal',
                text: data.message
            }).then(() => {
                if (data.success) {
                window.location.href = 'hasil_ahp.php?periode_id=' + encodeURIComponent(formData.get('periode_id'));
                }
            });
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Tidak dapat terhubung ke server.'
            });
        });
    });
    </script>

</body>
</html>
