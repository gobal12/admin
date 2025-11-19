<?php
session_start();
require_once '../db_connection.php';
// --- DEFINISI FUNGSI CHECK_ROLE ---
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('hrd');
$logged_in_user = $_SESSION['name'] ?? 'Guest';


// --- PROSES SIMPAN DATA (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $total_bobot = 0;
        $ids_to_keep = [];

        // --- 1. Update data yang ada dan hitung total bobot ---
        if (isset($_POST['bobot'])) {
            foreach ($_POST['bobot'] as $id => $bobot) {
                $id = intval($id);
                $bobot_val = floatval($bobot);
                $nama = trim($_POST['nama'][$id]);
                
                if (empty($nama) || $bobot_val <= 0) {
                    throw new Exception("Nama dan Bobot (harus > 0) tidak boleh kosong.");
                }

                $total_bobot += $bobot_val;
                $ids_to_keep[] = $id;

                $stmt_update = $conn->prepare("UPDATE faktor_kompetensi SET nama = ?, bobot_faktor = ? WHERE id = ?");
                $stmt_update->bind_param("sdi", $nama, $bobot_val, $id);
                $stmt_update->execute();
                $stmt_update->close();
            }
        }

        // --- 2. Insert data baru ---
        if (isset($_POST['new_nama'])) {
            foreach ($_POST['new_nama'] as $index => $nama) {
                $nama = trim($nama);
                $bobot_val = floatval($_POST['new_bobot'][$index]);

                if (!empty($nama) && $bobot_val > 0) {
                    $total_bobot += $bobot_val;
                    
                    $stmt_insert = $conn->prepare("INSERT INTO faktor_kompetensi (nama, bobot_faktor) VALUES (?, ?)");
                    $stmt_insert->bind_param("sd", $nama, $bobot_val);
                    $stmt_insert->execute();
                    $ids_to_keep[] = $stmt_insert->insert_id; // Tambahkan ID baru
                    $stmt_insert->close();
                }
            }
        }

        // --- 3. Validasi Total Bobot ---
        if (abs($total_bobot - 100.00) > 0.001) { // Toleransi floating point
            throw new Exception("Validasi Gagal! Total Bobot Faktor harus tepat 100.00%. Total saat ini: " . number_format($total_bobot, 2) . "%");
        }

        // --- 4. Hapus data yang tidak ada di form (dihapus oleh user) ---
        if (count($ids_to_keep) > 0) {
            $ids_placeholder = implode(',', array_fill(0, count($ids_to_keep), '?'));
            $types = str_repeat('i', count($ids_to_keep));
            $stmt_delete = $conn->prepare("DELETE FROM faktor_kompetensi WHERE id NOT IN ($ids_placeholder)");
            $stmt_delete->bind_param($types, ...$ids_to_keep);
        } else {
            $stmt_delete = $conn->prepare("DELETE FROM faktor_kompetensi");
        }
        $stmt_delete->execute();
        $stmt_delete->close();

        // --- 5. Commit ---
        $conn->commit();
        $_SESSION['success_message'] = "Bobot Faktor berhasil diperbarui! Total: 100.00%";
        header("Location: dataindikator.php"); // Kembali ke file utama
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: kelola_faktor.php"); // Kembali ke form jika error
        exit();
    }
}

// --- AMBIL DATA (GET) ---
$faktors = $conn->query("SELECT * FROM faktor_kompetensi ORDER BY id ASC");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include 'layouts/style.php';?>

    <title>KPI Nutech - Kelola Bobot Faktor</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .is-valid {
            border-color: #1cc88a !important;
            background-image: none !important;
        }
        .is-invalid {
            border-color: #e74a3b !important;
            background-image: none !important;
        }
    </style>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Kelola Bobot Faktor Kompetensi (Holistik)</h6>
            <small>Total bobot dari semua faktor di bawah ini **harus tepat 100.00%**.</small>
        </div>
        <div class="card-body">

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="kelola_faktor.php" method="POST" id="form-kelola">
                <div class="table-responsive">
                    <table class="table table-bordered" id="table-faktor" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nama Faktor</th>
                                <th>Bobot (%)</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_bobot = 0; ?>
                            <?php while($f = $faktors->fetch_assoc()): ?>
                            <?php $total_bobot += $f['bobot_faktor']; ?>
                            <tr>
                                <td>
                                    <input type="text" name="nama[<?= $f['id'] ?>]" class="form-control" value="<?= htmlspecialchars($f['nama']) ?>" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="bobot[<?= $f['id'] ?>]" class="form-control bobot-input" value="<?= $f['bobot_faktor'] ?>" required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-row" title="Hapus baris ini">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td class="text-right"><strong>TOTAL BOBOT</strong></td>
                                <td>
                                    <input type="text" id="total_bobot_display" class="form-control font-weight-bold" value="<?= $total_bobot ?>" readonly>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <button type="button" id="btn-add-row" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Tambah Baris Baru
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <hr>
                <button type="submit" class="btn btn-primary">Simpan Semua Perubahan</button>
                <a href="dataindikator.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>
<template id="template-row">
    <tr>
        <td>
            <input type="text" name="new_nama[]" class="form-control" placeholder="Nama Faktor Baru" required>
        </td>
        <td>
            <input type="number" step="0.01" name="new_bobot[]" class="form-control bobot-input" value="0.00" required>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-remove-row" title="Hapus baris ini">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
    <!-- End of Footer -->
    <div>
</div>

<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>

<script>
// Kalkulasi total bobot
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.bobot-input').forEach(function(input) {
        total += parseFloat(input.value) || 0;
    });
    
    let display = document.getElementById('total_bobot_display');
    display.value = total.toFixed(2);
    
    // Validasi visual
    if (Math.abs(total - 100.00) < 0.001) {
        display.classList.remove('is-invalid');
        display.classList.add('is-valid');
    } else {
        display.classList.remove('is-valid');
        display.classList.add('is-invalid');
    }
}

// Tambah baris baru
document.getElementById('btn-add-row').addEventListener('click', function() {
    const template = document.getElementById('template-row');
    const newRow = template.content.cloneNode(true);
    document.getElementById('table-faktor').querySelector('tbody').appendChild(newRow);
    attachListeners();
});

// Hapus baris (termasuk yang baru/lama)
function attachListeners() {
    // Hapus event listener lama agar tidak duplikat
    document.querySelectorAll('.btn-remove-row').forEach(button => {
        button.onclick = null; 
    });
    document.querySelectorAll('.bobot-input').forEach(input => {
        input.onchange = null;
        input.onkeyup = null;
    });

    // Pasang event listener baru
    document.querySelectorAll('.btn-remove-row').forEach(function(button) {
        button.onclick = function() {
            this.closest('tr').remove();
            calculateTotal();
        }
    });
    
    document.querySelectorAll('.bobot-input').forEach(function(input) {
        input.onchange = calculateTotal;
        input.onkeyup = calculateTotal;
    });
}

// Panggil saat load
attachListeners();
calculateTotal();

// Validasi saat submit
document.getElementById('form-kelola').addEventListener('submit', function(e) {
    let total = parseFloat(document.getElementById('total_bobot_display').value);
    if (Math.abs(total - 100.00) > 0.001) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Validasi Gagal',
            text: 'Total Bobot Faktor harus tepat 100.00%. Total Anda saat ini ' + total.toFixed(2) + '%.',
        });
    }
});
</script>

<script>
$(document).ready(function() {
    // 1. Tangkap klik pada tombol hamburger (di topbar atau sidebar)
    $('#sidebarToggle, #sidebarToggleTop').on('click', function(e) {
        
        // Biarkan script bawaan SB Admin bekerja dulu (menambah class 'toggled')
        // Kita beri sedikit delay 50ms agar class 'toggled' sudah terpasang
        setTimeout(function() {
            
            var sidebar = $('.sidebar');
            var contentWrapper = $('#content-wrapper');
            var body = $('body');

            // Cek apakah sidebar sekarang dalam kondisi 'toggled' (tertutup)?
            var isClosed = sidebar.hasClass('toggled') || body.hasClass('sidebar-toggled');

            if (isClosed) {
                // --- KONDISI TERTUTUP ---
                // 1. Paksa lebar sidebar jadi 0
                sidebar.css('width', '0px !important');
                sidebar.attr('style', 'width: 0px !important'); 
                
                // 2. Sembunyikan overflow biar teks tidak bocor
                sidebar.css('overflow', 'hidden');

                // 3. Paksa konten jadi full width (Margin kiri 0)
                contentWrapper.css('margin-left', '0px');
                contentWrapper.attr('style', 'margin-left: 0px !important'); 

            } else {
                // --- KONDISI TERBUKA ---
                // 1. Kembalikan lebar sidebar (sesuaikan, biasanya 14rem atau 224px)
                sidebar.attr('style', ''); // Hapus inline style biar balik ke CSS asli
                
                // 2. Kembalikan margin konten
                contentWrapper.attr('style', ''); // Hapus inline style biar balik ke CSS asli
                // contentWrapper.css('margin-left', '14rem'); // Jika perlu dipaksa
            }

        }, 50); // Delay 50ms sangat cepat, mata tidak akan melihat
    });

    // 2. Cek status awal saat halaman dimuat (agar tidak loncat)
    if ($('.sidebar').hasClass('toggled')) {
        $('#content-wrapper').css('margin-left', '0px');
    }
});
</script>
</body>
</html>