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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jabatan = trim($_POST['nama_jabatan']);

    if (!empty($nama_jabatan)) {
        $stmt = $conn->prepare("INSERT INTO jabatans (name) VALUES (?)");
        $stmt->bind_param("s", $nama_jabatan);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nama jabatan tidak boleh kosong']);
        exit();
    }
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

    <?php include 'layouts/style.php'; ?>
     
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <?php
    // --- LOGIKA DINAMIS DIMULAI DI SINI ---
    $current_page = basename($_SERVER['SCRIPT_NAME']);

    $laporan_pages = [
        'datakpi.php', 
        'hasil_ahp.php',
        'detailkpi.php',
        'cetak_kpi.php',
        'cetak_all_kpi.php',
        'detail_ahp.php',
        'cetak_ahp.php',
        'cetak_all_ahp.php'
    ];

    $setup_pages = [
        'ahp_result.php', 'addjabatan.php', 'addkaryawan.php', 'addkaryawanexcel.php',
        'addunit_projects.php', 'addperiode.php', 'ahp_input.php', 'ahp_result.php',
        'adduser.php', 'ahp_process.php', 'ahp.php', 'editperiode.php',
        'editkaryawan.php', 'kelola_faktor.php', 'kelola_indikator.php', 'dataindikator.php',
        'periodepenilaian.php', 'datakaryawan.php', 'datajabatan.php', 'dataunit_projects.php'
    ];

    $profile_pages = ['profile.php'];

    $is_laporan_active = in_array($current_page, $laporan_pages);
    $is_setup_active   = in_array($current_page, $setup_pages);
    $is_profile_active = in_array($current_page, $profile_pages);
    // --- LOGIKA DINAMIS SELESAI ---
    ?>
    <!-- Isi Side Bar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        
        <hr class="sidebar-divider my-0">

        <li class="nav-item <?php echo ($current_page == '') ? 'active' : ''; ?>">
            <a class="nav-link" href="#" title="">
                <i class=""></i>
                <span></span>
            </a>
        </li>

        <li class="nav-item <?php echo ($current_page == 'charts.php') ? 'active' : ''; ?>">
            <a class="nav-link" href="charts.php" title="Dashboard">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">
            Penilaian
        </div>

        <li class="nav-item <?php echo ($current_page == 'form-kpi.php') ? 'active' : ''; ?>">
            <a class="nav-link" href="form-kpi.php" title="Input Penilaian Kinerja">
                <i class="fas fa-fw fa-edit"></i>
                <span>Input Penilaian</span>
            </a>
        </li>

        <li class="nav-item <?php echo $is_laporan_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_laporan_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseLaporan" title="Laporan Kinerja">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Laporan Kinerja</span>
            </a>
            <div id="collapseLaporan" class="collapse <?php echo $is_laporan_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Lihat Hasil:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'datakpi.php') ? 'active' : ''; ?>" href="datakpi.php">Hasil (Metode Eksisting)</a>
                    <a class="collapse-item <?php echo ($current_page == 'hasil_ahp.php') ? 'active' : ''; ?>" href="hasil_ahp.php">Hasil (Metode AHP)</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">
            Admin & Setup
        </div>

        <li class="nav-item <?php echo $is_setup_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_setup_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseSetup" title="Setup Sistem">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Setup Sistem</span>
            </a>
            <div id="collapseSetup" class="collapse <?php echo $is_setup_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Setup Metodologi (AHP):</h6>
                    <a class="collapse-item <?php echo ($current_page == 'ahp_result.php') ? 'active' : ''; ?>" href="ahp_result.php">Setup Bobot Kriteria</a>
                    <a class="collapse-item <?php echo ($current_page == 'ahp.php') ? 'active' : ''; ?>" href="ahp.php">Proses Hasil AHP</a>
                    
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Setup Master KPI:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'dataindikator.php') ? 'active' : ''; ?>" href="dataindikator.php">Faktor & Indikator</a>
                    <a class="collapse-item <?php echo ($current_page == 'periodepenilaian.php') ? 'active' : ''; ?>" href="periodepenilaian.php">Periode Penilaian</a>

                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Setup Master Data:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'datakaryawan.php') ? 'active' : ''; ?>" href="datakaryawan.php">Data Karyawan</a>
                    <a class="collapse-item <?php echo ($current_page == 'datajabatan.php') ? 'active' : ''; ?>" href="datajabatan.php">Data Jabatan</a>
                    <a class="collapse-item <?php echo ($current_page == 'dataunit_projects.php') ? 'active' : ''; ?>" href="dataunit_projects.php">Data Unit</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

        <li class="nav-item <?php echo $is_profile_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_profile_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseProfile">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <div id="collapseProfile" class="collapse <?php echo $is_profile_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <a class="collapse-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">Edit Profile</a>
                    <a class="collapse-item" href="../logout.php">Logout</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

    </ul>
    <!-- End of Sidebar -->


    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar shadow">

                <button id="sidebarToggle" class="btn btn-link d-none d-md-inline-block rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <a class="d-flex align-items-center" 
                href="charts.php" 
                style="text-decoration: none; margin-right: 1rem;">
                    
                    <div class="sidebar-brand-icon"> <img src="../img/Logo-Nutech-ok.png" alt="Nutech Logo" style="height: 32px; width: auto;">
                    </div>
                    <div class="sidebar-brand-text mx-2 text-gray-800 font-weight-bold d-none d-sm-inline-block">KPI Nutech</div>
                </a>


                <ul class="navbar-nav ml-auto">
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                <?= htmlspecialchars($logged_in_user); ?>
                            </span>
                            <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                            aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <div class="card-header py-3 bg-primary text-white">
                    <h4 class="m-0 font-weight-bold">Tambah Jabatan</h4>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form id="jabatanForm">
                            <div class="form-group">
                                <label for="nama_jabatan">Nama Jabatan</label>
                                <input type="text" class="form-control" id="nama_jabatan" name="nama_jabatan" required>
                            </div>
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <a href="datajabatan.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>

            </div>
            <!-- End Page Content -->

        </div>
        <!-- End Main Content -->


        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; Nutech 2025</span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->

    </div>
    <!-- End Content Wrapper -->

</div>
<!-- End Page Wrapper -->


<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Siap Untuk Keluar</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        <div class="modal-body">Pilih "Logout" Jika kamu siap meninggalkan sesi.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="../logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="../js/demo/datatables-demo.js"></script>
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
