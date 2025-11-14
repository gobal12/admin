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
check_role('admin');

$logged_in_user = $_SESSION['name'] ?? 'Guest';

// 🧩 --- LOGIKA PENGAMBILAN DATA BARU (Lebih Efisien) ---
$unit_result = mysqli_query($conn, "SELECT id, name FROM unit_projects ORDER BY name ASC");
$periode_result = mysqli_query($conn, "SELECT id, nama_periode FROM periode_penilaian WHERE Status = 'Active'");

// 1. Ambil semua faktor
$faktor_sql = "SELECT id, nama, bobot_faktor FROM faktor_kompetensi ORDER BY id ASC";
$faktor_query = $conn->query($faktor_sql);
$faktors = $faktor_query->fetch_all(MYSQLI_ASSOC);

// 2. Ambil semua indikator
$indikator_sql = "SELECT id, faktor_id, nama, bobot_indikator FROM indikator_kompetensi ORDER BY faktor_id, id ASC";
$indikator_query = $conn->query($indikator_sql);
$indikators_raw = $indikator_query->fetch_all(MYSQLI_ASSOC);

// 3. Susun indikator ke dalam array per faktor_id
$indikators_by_faktor = [];
foreach ($indikators_raw as $ind) {
    if (!isset($indikators_by_faktor[$ind['faktor_id']])) {
        $indikators_by_faktor[$ind['faktor_id']] = [];
    }
    $indikators_by_faktor[$ind['faktor_id']][] = $ind;
}
// --- END LOGIKA PENGAMBILAN DATA ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KPI Nutech Operation - Form Penilaian</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-sm th, .table-sm td { vertical-align: middle; }
    </style>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Form Penilaian Kinerja Karyawan (KPI)</h4>
        </div>

        <div class="card-body">
            <form id="formPenilaian">

                <div class="form-group">
                    <label for="unit_id"><strong>Unit / Project</strong></label>
                    <select name="unit_id" id="unit_id" class="form-control" required>
                        <option value="">-- Pilih Unit / Project --</option>
                        <?php 
                        mysqli_data_seek($unit_result, 0); // Reset pointer
                        while ($u = mysqli_fetch_assoc($unit_result)): ?>
                            <option value="<?= htmlspecialchars($u['id']) ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="karyawan_id"><strong>Karyawan</strong></label>
                    <select name="karyawan_id" id="karyawan_id" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="periode_id"><strong>Periode Penilaian</strong></label>
                    <select name="periode_id" id="periode_id" class="form-control" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php 
                        mysqli_data_seek($periode_result, 0); // Reset pointer
                        while ($p = mysqli_fetch_assoc($periode_result)): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>" selected>
                                <?= htmlspecialchars($p['nama_periode']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <hr>

                <?php foreach ($faktors as $f): ?>
                    <div class="mt-4">
                        <h5 class="text-primary font-weight-bold mb-3"><?= htmlspecialchars($f['nama']) ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Indikator Kompetensi</th>
                                        <th width="10%" class="text-center">Bobot (%)</th>
                                        <th width="10%" class="text-center">Target</th>
                                        <th width="20%" class="text-center">Nilai (1-4)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $current_indikators = $indikators_by_faktor[$f['id']] ?? [];
                                    
                                    if (count($current_indikators) > 0) :
                                        foreach ($current_indikators as $i):
                                            // Hitung Target
                                            $target = ($i['bobot_indikator'] / 100) * 4.00;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($i['nama']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($i['bobot_indikator']) ?>%</td>
                                        <td class="text-center"><?= number_format($target, 2) ?></td>
                                        <td class="text-center">
                                            <select name="nilai[<?= $i['id'] ?>]" 
                                                    class="form-control text-center nilai-selector" 
                                                    data-faktor-id="<?= $f['id'] ?>" 
                                                    data-bobot="<?= $i['bobot_indikator'] ?>"
                                                    required>
                                                <option value="">- Pilih Nilai -</option>
                                                <option value="1">1 - Buruk</option>
                                                <option value="2">2 - Kurang</option>
                                                <option value="3">3 - Baik</option>
                                                <option value="4">4 - Sangat Baik</option>
                                            </select>
                                            
                                            <input type="hidden" 
                                                   name="bobot_indikator[<?= $i['id'] ?>]" 
                                                   value="<?= htmlspecialchars($i['bobot_indikator']) ?>">
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach; 
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="4" class="text-center font-italic">(Belum ada indikator)</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">
                                            SCORE (Total: <?= $f['bobot_faktor'] ?>% | Target: <?= number_format(($f['bobot_faktor']/100)*4, 2) ?>)
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   id="score_faktor_<?= $f['id'] ?>" 
                                                   class="form-control text-center font-weight-bold score-faktor-input" 
                                                   readonly>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group mt-4">
                    <label for="catatan"><strong>Catatan Tambahan</strong></label>
                    <textarea name="catatan" id="catatan" class="form-control" rows="4"
                        placeholder="Tulis catatan penilaian di sini..."></textarea>
                </div>

                <div class="text-right mt-4">
                    <h3 class="font-weight-bold text-primary">
                        TOTAL SCORE: 
                        <input type="text" id="grand_total_score" class="form-control text-center d-inline-block font-weight-bold" 
                               style="width: 120px; font-size: 1.25rem;" readonly>
                        / 4.00
                    </h3>
                </div>

                <div class="text-left">
                    <button type="submit" class="btn btn-primary mt-3 px-4">
                        <i class="fas fa-save"></i> Simpan Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

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

<script>
$(document).ready(function() {
    
    // Fungsi untuk menghitung total score
    function updateScores() {
        let grandTotal = 0;

        // Loop setiap 'faktor'
        $('.score-faktor-input').each(function() {
            const faktorId = $(this).attr('id').split('_')[2];
            let faktorScore = 0;

            // Loop setiap 'indikator' di dalam faktor ini
            $(`.nilai-selector[data-faktor-id="${faktorId}"]`).each(function() {
                const nilai = parseFloat($(this).val()) || 0;       // Nilai 1-4
                const bobot = parseFloat($(this).data('bobot')) || 0; // Bobot Indikator (cth: 25)

                // RUMUS BARU: Hasil = Nilai * (Bobot / 100)
                const hasilIndikator = nilai * (bobot / 100);
                faktorScore += hasilIndikator;
            });

            // Tampilkan score faktor
            $(this).val(faktorScore.toFixed(2));
            grandTotal += faktorScore;
        });

        // Tampilkan grand total
        $('#grand_total_score').val(grandTotal.toFixed(2));
    }

    // Panggil fungsi hitung setiap kali ada nilai berubah
    $('#formPenilaian').on('change', '.nilai-selector', function() {
        updateScores();
    });
    updateScores(); // Panggil saat load
});

// Submit Penilaian (Sama seperti kode Anda, tapi dengan konfirmasi)
document.getElementById('formPenilaian').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    Swal.fire({
        title: 'Simpan Penilaian?',
        text: "Pastikan semua nilai sudah benar.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('simpan_penilaian.php', {
                method: 'POST',
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
                        document.getElementById('formPenilaian').reset();
                        $('#karyawan_id').html('<option value="">-- Pilih Karyawan --</option>');
                        updateScores(); // Reset tampilan score
                    }
                });
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Error Jaringan', text: err.message });
            });
        }
    });
});
</script>

</body>
</html>