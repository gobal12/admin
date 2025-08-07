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

    $user_id         = $_POST['user_id'];
    $karyawan_pk     = $_POST['karyawan_pk'];
    $name            = $_POST['name'];
    $email           = $_POST['email'];
    $password        = $_POST['password'];
    $role            = $_POST['role'];
    $karyawan_id     = $_POST['karyawan_id'];
    $jabatan_id      = $_POST['jabatan_id'];
    $unit_project_id = $_POST['unit_project_id'];
    $hire_date       = $_POST['hire_date'];

    $conn->begin_transaction();

    try {
        // Update tabel users
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt1 = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
            $stmt1->bind_param("ssssi", $name, $email, $hashed, $role, $user_id);
        } else {
            $stmt1 = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt1->bind_param("sssi", $name, $email, $role, $user_id);
        }
        $stmt1->execute();
        $stmt1->close();

        // Update tabel karyawans
        $stmt2 = $conn->prepare("UPDATE karyawans SET karyawan_id=?, jabatan_id=?, unit_project_id=?, hire_date=? WHERE id=?");
        $stmt2->bind_param("siisi", $karyawan_id, $jabatan_id, $unit_project_id, $hire_date, $karyawan_pk);
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

    // Ambil data karyawan dan user
    $sql = "SELECT 
                u.id as user_id,
                u.name, u.email, u.role,
                k.id as karyawan_pk,
                k.karyawan_id, k.jabatan_id, k.unit_project_id, k.hire_date
            FROM users u
            JOIN karyawans k ON k.user_id = u.id
            WHERE k.id = ?";
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
                <div class="container fluid">
                    <h2 class="mb-4">Data Karyawan</h2>
                    <form id="formKaryawan">
                        <input type="hidden" name="user_id" value="<?= $data['user_id'] ?>">
                        <input type="hidden" name="karyawan_pk" value="<?= $data['karyawan_pk'] ?>">

                        <label>Nama</label>
                        <input type="text" name="name" value="<?= $data['name'] ?>" class="form-control" required>

                        <label>Email</label>
                        <input type="email" name="email" value="<?= $data['email'] ?>" class="form-control" required>

                        <label>Password (Kosongkan jika tidak ingin diganti)</label>
                        <input type="password" name="password" class="form-control">

                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <?php foreach (['admin', 'hrd', 'manager', 'karyawan'] as $role): ?>
                                <option value="<?= $role ?>" <?= $data['role'] === $role ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('-', ' ', $role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Karyawan ID</label>
                        <input type="text" name="karyawan_id" value="<?= $data['karyawan_id'] ?>" class="form-control" required>

                        <label>Jabatan</label>
                        <select name="jabatan_id" class="form-control" required>
                            <?php while($j = $jabatans->fetch_assoc()): ?>
                                <option value="<?= $j['id'] ?>" <?= $j['id'] == $data['jabatan_id'] ? 'selected' : '' ?>>
                                    <?= $j['name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label>Unit / Project</label>
                        <select name="unit_project_id" class="form-control" required>
                            <?php while($u = $units->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id'] == $data['unit_project_id'] ? 'selected' : '' ?>>
                                    <?= $u['name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label>Hire Date</label>
                        <input type="date" name="hire_date" value="<?= $data['hire_date'] ?>" class="form-control" required>

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
