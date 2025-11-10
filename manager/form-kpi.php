<?php
session_start();
require_once '../db_connection.php';

// 🔒 Cek role user
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('manager');

$logged_in_user = $_SESSION['name'] ?? 'Guest';

// 🧩 Ambil daftar unit-project, periode aktif, faktor & indikator
$unit_result = mysqli_query($conn, "SELECT id, name FROM unit_projects ORDER BY name ASC");
$periode_result = mysqli_query($conn, "SELECT id, nama_periode FROM periode_penilaian WHERE Status = 'Active'");
$faktor_result = mysqli_query($conn, "SELECT * FROM faktor_kompetensi ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KPI Nutech Operation - Form Penilaian</title>

    <!-- Custom fonts for this template -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    
    <!-- Custom styles for this template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Form Penilaian Kinerja Karyawan (KPI)</h4>
        </div>

        <div class="card-body">
            <form id="formPenilaian" method="POST">

                <!-- Pilihan Unit -->
                <div class="form-group">
                    <label for="unit_id"><strong>Unit / Project</strong></label>
                    <select name="unit_id" id="unit_id" class="form-control" required>
                        <option value="">-- Pilih Unit / Project --</option>
                        <?php while ($u = mysqli_fetch_assoc($unit_result)): ?>
                            <option value="<?= htmlspecialchars($u['id']) ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Pilihan Karyawan -->
                <div class="form-group">
                    <label for="karyawan_id"><strong>Karyawan</strong></label>
                    <select name="karyawan_id" id="karyawan_id" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                    </select>
                </div>

                <!-- Pilihan Periode -->
                <div class="form-group">
                    <label for="periode_id"><strong>Periode Penilaian</strong></label>
                    <select name="periode_id" id="periode_id" class="form-control" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php while ($p = mysqli_fetch_assoc($periode_result)): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>" selected>
                                <?= htmlspecialchars($p['nama_periode']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <hr>

                <!-- Tabel Faktor dan Indikator -->
                <?php while ($f = mysqli_fetch_assoc($faktor_result)): ?>
                    <div class="mt-4">
                        <h5 class="text-primary font-weight-bold mb-3"><?= htmlspecialchars($f['nama']) ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="70%">Indikator Kompetensi</th>
                                        <th width="30%" class="text-center">Nilai (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $indikator_result = mysqli_query(
                                        $conn,
                                        "SELECT * FROM indikator_kompetensi WHERE faktor_id = {$f['id']} ORDER BY id ASC"
                                    );
                                    while ($i = mysqli_fetch_assoc($indikator_result)):
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($i['nama']) ?></td>
                                            <td class="text-center">
                                                <input type="number" 
                                                       name="nilai[<?= $i['id'] ?>]" 
                                                       class="form-control text-center" 
                                                       step="0.01" min="0" max="100" required>
                                                <input type="hidden" name="bobot[<?= $i['id'] ?>]" 
                                                       value="<?= htmlspecialchars($i['bobot']) ?>">
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Catatan -->
                <div class="form-group mt-4">
                    <label for="catatan"><strong>Catatan Tambahan</strong></label>
                    <textarea name="catatan" id="catatan" class="form-control" rows="4"
                        placeholder="Tulis catatan penilaian di sini..."></textarea>
                </div>

                <!-- Tombol Simpan -->
                <div class="text-left">
                    <button type="submit" class="btn btn-primary mt-3 px-4">
                        <i class=""></i> Simpan Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<!-- Script JS -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<!-- Dynamic Karyawan Load -->
<script>
document.getElementById('unit_id').addEventListener('change', function() {
    const unitId = this.value;
    const karyawanSelect = document.getElementById('karyawan_id');
    karyawanSelect.innerHTML = '<option value="">-- Pilih Karyawan --</option>';

    if (unitId) {
        fetch('get_karyawan.php?unit_id=' + unitId)
            .then(res => res.json())
            .then(data => {
                data.forEach(k => {
                    const option = document.createElement('option');
                    option.value = k.karyawan_id;
                    option.text = k.name;
                    karyawanSelect.add(option);
                });
            })
            .catch(err => console.error('Gagal memuat karyawan:', err));
    }
});
</script>

<!-- Submit Penilaian -->
<script>
document.getElementById('formPenilaian').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('simpan_penilaian.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.success ? 'Berhasil' : 'Gagal',
            text: data.message || (data.success
                ? 'Data penilaian berhasil disimpan.'
                : 'Terjadi kesalahan saat menyimpan data.')
        }).then(() => {
            if (data.success) location.reload();
        });
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    });
});
</script>

</body>
</html>
