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

// Ambil data faktor (Id dan Nama)
// **PENTING: Kita perlu ID dan NAMA di sini**
$faktor = [];
$res = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $faktor[] = $row; // Simpan array asosiatif (ID dan Nama)
}
$n = count($faktor);

// AMBIL DATA MATRIX YANG SUDAH ADA (jika ada)
$saved_matrix = [];
$resMx = $conn->query("SELECT faktor_id_1, faktor_id_2, nilai FROM ahp_matrix");
while ($row = $resMx->fetch_assoc()) {
    // Kita simpan dengan format key: "ID_BARIS-ID_KOLOM"
    $key = $row['faktor_id_1'] . '-' . $row['faktor_id_2'];
    $saved_matrix[$key] = $row['nilai'];
}

// **CATATAN: Hapus blok kode lama yang hanya mengambil nama faktor saja, karena sudah digabung di atas**
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include 'layouts/style.php';?>

    <title>KPI Nutech Operation - Input AHP</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

                <div class="container-fluid">
                    <div class="card-header py-3 bg-primary text-white">
                        <h4 class="m-0 font-weight-bold">Input Perbandingan Faktor AHP</h4>
                    </div>
<div class="alert alert-info">
    <strong>Panduan Pengisian:</strong>
    <ul class="mb-0">
        <li>Isi angka <strong>di atas diagonal</strong> (sebelah kanan dari 1), sistem akan otomatis menghitung kebalikannya di bawah diagonal.</li>
        <li>Gunakan skala <strong>1 – 9</strong> untuk menilai tingkat kepentingan:
            <ul>
                <li>1 = Sama penting</li>
                <li>3 = Sedikit lebih penting</li>
                <li>5 = Lebih penting</li>
                <li>7 = Sangat lebih penting</li>
                <li>9 = Mutlak lebih penting</li>
                <li>Gunakan nilai desimal atau pecahan jika di antara skala (misal: 1.5 atau 0.5 untuk kebalikannya).</li>
            </ul>
        </li>
        <li>
            Contoh: Jika 
            <strong><?php echo htmlspecialchars($faktor[0]['nama'] ?? ''); ?></strong> 
            lebih penting <strong>3 kali</strong> dibanding 
            <strong><?php echo htmlspecialchars($faktor[1]['nama'] ?? ''); ?></strong>, 
            maka isi angka <strong>3</strong> pada baris 
            <strong><?php echo htmlspecialchars($faktor[0]['nama'] ?? ''); ?></strong> kolom 
            <strong><?php echo htmlspecialchars($faktor[1]['nama'] ?? ''); ?></strong>. 
            Sistem akan otomatis mengisi <strong>0.333</strong> pada baris 
            <strong><?php echo htmlspecialchars($faktor[1]['nama'] ?? ''); ?></strong> kolom 
            <strong><?php echo htmlspecialchars($faktor[0]['nama'] ?? ''); ?></strong>.
        </li>
        <li>Kolom diagonal (nilai perbandingan dengan dirinya sendiri) selalu <strong>1</strong>.</li>
    </ul>
</div>
                        <form method="post" action="ahp_process.php" id="ahpForm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Faktor</th>
                                        <?php foreach ($faktor as $fk): ?>
                                            <th><?= htmlspecialchars($fk['nama']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php for ($i = 0; $i < $n; $i++): ?>
                                    <tr>
                                        <th><?= htmlspecialchars($faktor[$i]['nama']) ?></th>
                                        <?php for ($j = 0; $j < $n; $j++): ?>
                                            
                                            <?php 
                                                // Key untuk mencari data lama
                                                $key_db = $faktor[$i]['id'] . '-' . $faktor[$j]['id'];
                                                $saved_val = isset($saved_matrix[$key_db]) ? $saved_matrix[$key_db] : '';
                                            ?>

                                            <?php if ($i == $j): ?>
                                                <td><input type="number" value="1" readonly class="form-control"></td>
                                            <?php elseif ($i < $j): ?>
                                                <td>
                                                    <input type="number" step="any" min="0.1111" max="9"
                                                        name="matrix[<?= $i ?>][<?= $j ?>]"
                                                        value="<?= $saved_val ?>" 
                                                        class="form-control" required />
                                                </td>
                                            <?php else: ?>
                                                <td>
                                                    <input type="number" readonly class="form-control bg-light" 
                                                        id="mirror_<?= $i ?>_<?= $j ?>" 
                                                        value="<?= $saved_val ?>" />
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
                    document.querySelectorAll('input[name^="matrix"]').forEach(function(input) {
                        input.addEventListener('input', function() {
                            let name = this.name;
                            let match = name.match(/matrix\[(\d+)\]\[(\d+)\]/);
                            if (match) {
                                let i = parseInt(match[1]), j = parseInt(match[2]);
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
                <?php include 'layouts/footer.php'; ?>
    <div>
</div>

<?php include 'layouts/page_end.php'; ?>
    
    <script>
        document.getElementById('ahpForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah submit form standar

            const formData = new FormData(this);
            
            // Mengirim request ke ahp_process.php
            fetch('ahp_process.php', { // Pastikan URL benar
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!', 
                        html: `Bobot Berhasil Diupdate.<br>Status Konsistensi: <b>${data.consistency}</b>`, 
                        icon: 'success'
                    })
                    .then(() => window.location.href = 'ahp_result.php');
                } else {
                    Swal.fire('Gagal', data.message || 'Terjadi kesalahan saat menyimpan data.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Gagal mengirim data ke server. Cek koneksi.', 'error');
            });
        });
    </script>
</body>

</html>