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

// Ambil data nama faktor saja
$faktor = [];
$res = $conn->query("SELECT nama FROM faktor_kompetensi ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $faktor[] = $row['nama'];  // hanya simpan nama sebagai string
}
$n = count($faktor);
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
                        <h3>Input Perbandingan Faktor AHP</h3>
                        <p class="text-muted">
                            Isi nilai perbandingan antar faktor sesuai kepentingannya.<br />
                            1 = sama penting, 3 = sedikit lebih penting, 5 = lebih penting, 7 = sangat penting, 9 = mutlak lebih penting.<br />
                            Gunakan nilai desimal atau kebalikan (misal 0.3333) untuk menyatakan sebaliknya.
                        </p>

                        <form method="post" action="ahp_process.php" id="ahpForm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Faktor</th>
                                        <?php foreach ($faktor as $namaFaktor): ?>
                                            <th><?= htmlspecialchars($namaFaktor) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i < $n; $i++): ?>
                                        <tr>
                                            <th><?= htmlspecialchars($faktor[$i]) ?></th>
                                            <?php for ($j = 0; $j < $n; $j++): ?>
                                                <?php if ($i == $j): ?>
                                                    <td><input type="number" value="1" readonly class="form-control"></td>
                                                <?php elseif ($i < $j): ?>
                                                    <td>
                                                        <input type="number" step="any" min="0.1111" max="9"
                                                            name="matrix[<?= $i ?>][<?= $j ?>]"
                                                            class="form-control" required />
                                                    </td>
                                                <?php else: ?>
                                                    <td>
                                                        <input type="number" readonly class="form-control bg-light" id="mirror_<?= $i ?>_<?= $j ?>" />
                                                    </td>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>

                            <button type="submit" class="btn btn-primary">Hitung & Simpan Bobot</button>
                        </form>
                    </div>

                    <script>
                    document.querySelectorAll('input[type="number"]').forEach(function(input) {
                        input.addEventListener('input', function() {
                            let name = this.name;
                            if (!name) return;
                            let match = name.match(/matrix\[(\d+)\]\[(\d+)\]/);
                            if (match) {
                                let i = match[1], j = match[2];
                                let val = parseFloat(this.value);
                                if (val && val > 0) {
                                    let reverse = (1 / val).toFixed(4);
                                    let mirrorInput = document.querySelector(`#mirror_${j}_${i}`);
                                    if (mirrorInput) mirrorInput.value = reverse;
                                }
                            }
                        });
                    });
                    </script>
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
