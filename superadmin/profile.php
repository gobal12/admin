<?php
session_start();
require_once '../db_connection.php';

// Cek role
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('admin');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

// Cek jika AJAX request untuk ubah password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header("Content-Type: application/json");

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "Anda belum login."]);
        exit;
    }

    $new_password = trim($_POST['password']);

    if (strlen($new_password) < 6) {
        echo json_encode(["success" => false, "message" => "Password minimal 6 karakter."]);
        exit;
    }

    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Password berhasil diubah."]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal mengubah password."]);
    }
    exit;
}

// Ambil data user untuk ditampilkan
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo "Anda harus login terlebih dahulu.";
    exit();
}

$query = "
    SELECT 
        u.name, u.email, u.password,
        k.karyawan_id,
        j.name AS nama_jabatan,
        up.name AS nama_unit
    FROM users u
    LEFT JOIN karyawans k ON u.id = k.user_id
    LEFT JOIN jabatans j ON k.jabatan_id = j.id
    LEFT JOIN unit_projects up ON k.unit_project_id = up.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
} else {
    echo "Data tidak ditemukan.";
    exit();
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

    <title>KPI Nutech Operation - Profile</title>

    <!-- Custom fonts for this template -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <!-- <h1 class="h3 mb-2 text-gray-800">Profile</h1> -->

                    <div class="card">
                        <div class="card-header" style="background-color: rgb(237, 237, 237);" >
                         <b> Informasi Data Diri </b>
                        </div>
                        <div class="card-group">
                            <div class="card">
                                <img src="https://images.unsplash.com/photo-1511367461989-f85a21fda167?q=80&w=1631&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="card-img-top" alt="...">
                            </div>
                            <div class="card">
                                <div class="card-body">
                                  <h5 class="card-title">Data Diri</h5>
                                    <form id="formUbahPassword">
                                        <div class="mb-3">
                                            <label>Nama</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['name']) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label>Email</label>
                                            <input type="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label>ID Karyawan</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['karyawan_id'] ?? '') ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label>Jabatan</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_jabatan'] ?? '') ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label>Unit / Project</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_unit'] ?? '') ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label>Password Baru (Opsional)</label>
                                            <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ingin ubah password">
                                        </div>

                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </form>
                                </div>
                            </div>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("formUbahPassword").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const password = formData.get("password");

    if (!password || password.trim().length < 6) {
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Password harus minimal 6 karakter.'
        });
        return;
    }

    fetch("profile.php", {
        method: "POST",
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.message
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: err.message
        });
    });
});
</script>

</body>

</html>