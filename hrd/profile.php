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
check_role('hrd');

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

// UBAH: Logika AJAX dipindah ke atas dan dirombak total
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header("Content-Type: application/json");
    
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "Sesi Anda telah berakhir, silakan login kembali."]);
        exit;
    }

    try {
        // Ambil data baru dari POST
        $new_email = trim($_POST['email'] ?? '');
        $new_password = trim($_POST['password'] ?? '');

        // Ambil data email saat ini dari DB untuk perbandingan
        $stmt_current = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt_current->bind_param("i", $user_id);
        $stmt_current->execute();
        $current_data = $stmt_current->get_result()->fetch_assoc();
        $current_email = $current_data['email'];

        $updates = []; // Menyimpan bagian query SET
        $params = [];  // Menyimpan nilai untuk binding
        $types = "";   // Menyimpan tipe data untuk binding

        $email_changed = false;
        $password_changed = false;

        // --- 1. Proses Perubahan Email ---
        if (!empty($new_email) && $new_email !== $current_email) {
            // Validasi format email
            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }
            
            // Cek duplikasi email
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->bind_param("si", $new_email, $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                throw new Exception("Email '$new_email' sudah digunakan oleh akun lain.");
            }
            
            // Siapkan untuk update
            $updates[] = "email = ?";
            $params[] = $new_email;
            $types .= "s";
            $email_changed = true;
        }

        // --- 2. Proses Perubahan Password ---
        if (!empty($new_password)) {
            // Validasi panjang password
            if (strlen($new_password) < 6) {
                throw new Exception("Password minimal 6 karakter.");
            }
            
            // Siapkan untuk update
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updates[] = "password = ?";
            $params[] = $hash;
            $types .= "s";
            $password_changed = true;
        }

        // --- 3. Eksekusi Update ke Database ---
        if (!$email_changed && !$password_changed) {
            throw new Exception("Tidak ada perubahan yang perlu disimpan.");
        }

        // Tambahkan user_id di akhir array params untuk klausa WHERE
        $params[] = $user_id;
        $types .= "i";

        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameter secara dinamis
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Jika email berubah, update juga di session
            if ($email_changed) {
                $_SESSION['email'] = $new_email;
            }
            echo json_encode(["success" => true, "message" => "Data berhasil diperbarui."]);
        } else {
            throw new Exception("Gagal memperbarui data di database.");
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}
// AKHIR UBAHAN BLOK AJAX

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
    <?php include 'layouts/style.php';?>

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

                    <div class="card">
                    <div class="card-header py-3 bg-primary text-white">
                        <h4 class="m-0 font-weight-bold">Informasi Data Diri</h4>
                    </div>
                        <div class="card-group">
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
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>">
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
    <!-- End of Footer -->
    <div>
</div>

<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("formUbahPassword").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const password = formData.get("password").trim();
    const email = formData.get("email").trim();

    // Validasi 1: Cek format email (validasi sederhana di client)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Format email tidak valid.'
        });
        return;
    }

    // Validasi 2: Jika password diisi, pastikan minimal 6 karakter
    if (password.length > 0 && password.length < 6) {
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Password minimal 6 karakter (atau biarkan kosong jika tidak diubah).'
        });
        return;
    }
    
    // Validasi 3: Cek apakah ada yang diisi (opsional, backend sudah handle)
    // Kita bisa lewati ini dan biarkan backend yang memberi pesan "Tidak ada perubahan"

    // Kirim data ke server
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
            }).then(() => {
                // Jika berhasil, reload halaman agar data baru (email) tampil di form
                location.reload(); 
            });
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
            text: err.message || 'Tidak dapat terhubung ke server.'
        });
    });
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