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
    header('Content-Type: application/json'); // supaya response JSON

    $id              = $_POST['id'];
    $nama_periode    = $_POST['nama_periode'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $status          = $_POST['status'];

    $conn->begin_transaction();

    try {
        // Update tabel periode
        $stmt2 = $conn->prepare("UPDATE periode_penilaian SET nama_periode=?, tanggal_mulai=?, tanggal_selesai=?, status=? WHERE id=?");
        $stmt2->bind_param("ssssi", $nama_periode, $tanggal_mulai, $tanggal_selesai, $status, $id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();

        echo json_encode(['success' => true]); // kirim response JSON sukses
    } catch (Exception $e) {
        $conn->rollback();

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]); // kirim response JSON error
    }

    exit; // stop script agar tidak lanjut ke render halaman form
} else {
    // ========== TAMPILAN FORM ==========
    $id = $_GET['id'] ?? 0;

    // Ambil data periode
    $sql = "SELECT 
                pp.id as id,
                pp.nama_periode, pp.tanggal_mulai, pp.tanggal_selesai, pp.status
            FROM periode_penilaian pp
            WHERE pp.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo "Data tidak ditemukan";
        exit;
    }

    $stmt->close();

    // Ambil data dropdown
    $jabatans = $conn->query("SELECT id, name FROM jabatans");
    $units    = $conn->query("SELECT id, name FROM unit_projects");
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
                <div class="container fluid">
                    <h2 class="mb-4">Data Periode</h2>
                    <form id="formPeriode">
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">
                        <input type="hidden" name="nama_periode" value="<?= $data['nama_periode'] ?>">

                        <label>Periode</label>
                        <input type="text" name="nama_periode" value="<?= $data['nama_periode'] ?>" class="form-control" required>

                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="<?= $data['tanggal_mulai'] ?>" class="form-control" required>

                        <label>Tanggal Selesai </label>
                        <input type="date" name="tanggal_selesai" value="<?= $data['tanggal_selesai'] ?>" class="form-control" required>

                        <label>Status </label>
                        <select name="status" class="form-control" required>
                            <option value="Active" <?= $data['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Unactive" <?= $data['status'] == 'Unactive' ? 'selected' : '' ?>>Unactive</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary mt-3">Simpan Perubahan</button>
                    </form>
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
    document.getElementById("formPeriode").addEventListener("submit", function(event) {
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
                    text: 'Data periode berhasil diubah.'
                }).then(() => {
                    window.location.href = 'periodepenilaian.php';
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
