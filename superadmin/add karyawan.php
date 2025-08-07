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
    $karyawan_id  = $_POST['karyawan_id'] ?? '';
    $jabatan_id   = $_POST['jabatan_id'] ?? '';
    $unit_id      = $_POST['unit_id'] ?? '';
    $hire_date    = $_POST['hire_date'] ?? '';
    $password     = password_hash('Nutech123', PASSWORD_DEFAULT);
    $role         = 'karyawan';

    // Ganti ke unit_project_id
    $unit_project_id = $unit_id;

    $conn->begin_transaction(); // ✅ Mulai transaksi

    try {
        // Insert ke tabel users
        $stmt1 = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("ssss", $nama, $email, $password, $role);
        $stmt1->execute();
        $user_id = $stmt1->insert_id;
        $stmt1->close();

        // Insert ke tabel karyawans
        $stmt2 = $conn->prepare("INSERT INTO karyawans (karyawan_id, user_id, jabatan_id, unit_project_id, hire_date) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("siiis", $karyawan_id, $user_id, $jabatan_id, $unit_project_id, $hire_date);
        $stmt2->execute();
        $stmt2->close();

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
                    <h2 class="mb-4">Tambah Karyawan</h2>
                    <form id="formKaryawan">
                        <div class="form-group mb-3">
                            <label>Nama</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>ID Karyawan</label>
                            <input type="text" class="form-control" name="karyawan_id" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Jabatan</label>
                            <select class="form-control" name="jabatan_id" required>
                                <option value="">Pilih Jabatan</option>
                                <?php
                                $q = $conn->query("SELECT id, name FROM jabatans");
                                while ($row = $q->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Unit/Project</label>
                            <select class="form-control" name="unit_id" required>
                                <option value="">Pilih Unit/Project</option>
                                <?php
                                $q = $conn->query("SELECT id, name FROM unit_projects");
                                while ($row = $q->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Tanggal Hire</label>
                            <input type="date" class="form-control" name="hire_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="DataKaryawan.php" class="btn btn-secondary">Kembali</a>
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
    document.getElementById("formKaryawan").addEventListener("submit", function(event) {
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
                    text: 'Data karyawan berhasil ditambahkan.'
                }).then(() => {
                    window.location.href = 'DataKaryawan.php';
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
