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

// Ambil daftar karyawan (karyawans.id) dan nama user (users.name)
$sql = "SELECT k.id AS karyawan_id, u.name AS user_name, up.name AS unit_name, up.id AS unit_id
        FROM karyawans k
        JOIN users u ON k.user_id = u.id
        JOIN unit_projects up ON k.unit_project_id = up.id";
$result = mysqli_query($conn, $sql);

// Ambil daftar periode
$periode = mysqli_query($conn, "SELECT id, nama_periode FROM periode_penilaian WHERE Status = 'Active'");

// Ambil faktor dan indikator
$faktor = mysqli_query($conn, "SELECT * FROM faktor_kompetensi");

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
                    <h2 class="mb-4">Form Penilian</h2>
                        <form id="formPenilaian" method="POST">
<div class="form-group">
    <label for="unit_id">Unit</label>
    <select name="unit_id" id="unit_id" class="form-control" required>
        <option value="">-- Pilih Unit / Project --</option>
        <?php
        $unit_result = mysqli_query($conn, "SELECT id, name FROM unit_projects");
        while ($u = mysqli_fetch_assoc($unit_result)) {
            echo "<option value='" . $u['id'] . "'>" . htmlspecialchars($u['name']) . "</option>";
        }
        ?>
    </select>
</div>

<div class="form-group">
    <label for="karyawan_id">Karyawan</label>
    <select name="karyawan_id" id="karyawan_id" class="form-control" required>
        <option value="">-- Pilih Karyawan --</option>
        <!-- Diisi dinamis via JS -->
    </select>
</div>

                            <div class="form-group"> 
                                <label for="periode_id">Periode</label>
                                <select name="periode_id" class="form-control" required>
                                    <option value="">-- Pilih Periode --</option>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($periode)) {
                                        echo "<option value='" . htmlspecialchars($row['id']) . "' selected>" . 
                                            htmlspecialchars($row['nama_periode']) . 
                                            "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <?php
                            while ($f = mysqli_fetch_assoc($faktor)) {
                                echo "<h4>" . htmlspecialchars($f['nama']) . "</h4>";
                                echo "<table class='table table-bordered'>";
                                echo "<thead><tr><th>Indikator</th><th>Bobot (%)</th><th>Target</th><th>Nilai (%)</th></tr></thead><tbody>";

                                $indikator = mysqli_query($conn, "SELECT * FROM indikator_kompetensi WHERE faktor_id = {$f['id']}");
                                while ($i = mysqli_fetch_assoc($indikator)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($i['nama']) . "</td>";
                                    echo "<td>" . htmlspecialchars($i['bobot']) . "</td>";
                                    echo "<td>" . htmlspecialchars($i['target']) . "</td>";
                                    echo "<td>
                                            <input type='number' name='nilai[" . $i['id'] . "]' step='0.01' min='0' max='100' required>
                                            <input type='hidden' name='bobot[" . $i['id'] . "]' value='" . $i['bobot'] . "'>
                                        </td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table>";
                            }
                            ?>

                            <div class="form-group">
                                <label for="catatan">Catatan</label>
                                <textarea name="catatan" class="form-control" rows="4" placeholder="Tulis catatan penilaian di sini..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Simpan</button>
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

    <!-- Get Nama Karyawan by Unit -->
    <script>
    document.getElementById('unit_id').addEventListener('change', function () {
        const unitId = this.value;
        const karyawanSelect = document.getElementById('karyawan_id');
        karyawanSelect.innerHTML = '<option value="">-- Pilih Karyawan --</option>';

        if (unitId) {
            fetch('get_karyawan.php?unit_id=' + unitId)
                .then(response => response.json())
                .then(data => {
                    data.forEach(function (karyawan) {
                        const option = document.createElement('option');
                        option.value = karyawan.karyawan_id;
                        option.text = karyawan.name;
                        karyawanSelect.add(option);
                    });
                })
                .catch(error => {
                    console.error('Gagal memuat karyawan:', error);
                });
        }
    });
    </script>

    <!-- Konfirmasi Penilaian KPI -->
    <script>
    document.getElementById('formPenilaian').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('simpan_penilaian.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: data.message || 'Data penilaian berhasil disimpan.'
                }).then(() => {
                    window.location.href = 'datakpi.php'; // ganti sesuai halaman tujuan
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Terjadi kesalahan saat menyimpan data.'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        });
    });
    </script>

</body>

</html>
