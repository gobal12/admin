<?php
session_start();

//Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

check_role('hrd');

// Ambil nama user dari session
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

require_once '../db_connection.php';

// Query baru sesuai struktur database
$sql = "SELECT 
            p.id, 
            p.nama_periode AS periode,
            p.status 
        FROM periode_penilaian p";

$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
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

    <title>KPI Nutech Operation - Data Periode</title>

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

                    <!-- Page Heading -->
                    <div class="card-header py-3 bg-primary text-white">
                        <h4 class="m-0 font-weight-bold">Data Periode</h4>
                        <p class="mb-4">Menampilkan list Periode</p>
                    </div>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
    
                    <div class="card-header py-3">
                        <a href="addperiode.php" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Tambah Periode</a>
                    </div>

                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $nomor = 1;
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $nomor . "</td>";
                                            echo "<td>" . $row["periode"] . "</td>";
                                            echo "<td>" . $row["status"] . "</td>";
                                            echo "<td>";
                                            echo "<a href='editperiode.php?id=" . $row["id"] . "' class='btn btn-primary' title='Edit'><i class='fas fa-edit'></i></a> ";
                                            echo "<button type='button' class='btn btn-danger' onclick='confirmDelete(" . $row["id"] . ", event)' title='Delete'><i class='far fa-trash-alt'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                            $nomor++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>Data tidak ditemukan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'layouts/footer.php'; ?>
    <!-- End of Footer -->
    <div>
</div>

<!-- End Page Wrapper -->
        <?php include 'layouts/page_end.php'; ?>
    
    <!-- Konfirmasi Delete -->
    <script>
    function confirmDelete(id, event) {
        // Prevent the default action of the link
        event.preventDefault();

        // Show the SweetAlert confirmation popup
        Swal.fire({
            title: 'Apakah Kamu Yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus Akun!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the delete URL
                window.location.href = "deleteperiode.php?id=" + id;
            }
        });
    }
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
