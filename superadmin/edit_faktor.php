<?php
session_start();

// Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('admin');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

include '../db_connection.php';

// --- Ambil data faktor berdasarkan ID ---
if (!isset($_GET['id'])) {
    echo "<script>alert('ID faktor tidak ditemukan.'); window.location.href='data_faktor.php';</script>";
    exit();
}

$id_faktor = intval($_GET['id']);
$result = $conn->query("SELECT * FROM faktor_kompetensi WHERE id = $id_faktor");
if ($result->num_rows === 0) {
    echo "<script>alert('Data faktor tidak ditemukan.'); window.location.href='data_faktor.php';</script>";
    exit();
}

$faktor = $result->fetch_assoc();

// --- Proses Update Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // supaya fetch menerima JSON

    $nama_faktor = trim($_POST['nama_faktor']);
    $response = [];

    if ($nama_faktor === '') {
        $response = ['success' => false, 'message' => 'Nama faktor tidak boleh kosong!'];
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("UPDATE faktor_kompetensi SET nama = ? WHERE id = ?");
    $stmt->bind_param("si", $nama_faktor, $id_faktor);

    if ($stmt->execute()) {
        $response = ['success' => true];
    } else {
        $response = ['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui faktor.'];
    }

    $stmt->close();
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>KPI Nutech Operation - Edit Faktor Kompetensi</title>

    <!-- Custom fonts and styles -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <div class="card-header py-3 bg-primary text-white">
        <h4 class="m-0 font-weight-bold">Edit Faktor Kompetensi</h4>
    </div>
    <div class="card">
        <div class="card-body">
            <form id="editfaktor">
                <div class="mb-3">
                    <label class="form-label">Nama Faktor Kompetensi</label>
                    <input type="text" name="nama_faktor" class="form-control"
                        value="<?= htmlspecialchars($faktor['nama']) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary text-white">Update</button>
                <a href="ddataindikator.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<!-- Scripts -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
document.getElementById('editfaktor').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Faktor Kompetensi berhasil diperbarui.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'dataindikator.php';
            });
        } else {
            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Gagal mengirim data.', 'error');
    });
});
</script>
</body>
</html>
