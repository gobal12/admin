<?php
session_start();
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
// include '../db_connection.php'; // Kita matikan DB dulu biar tidak error koneksi
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Sidebar</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/bootstrap.min.css">

    </head>

<body id="page-top">

    <div id="wrapper">

        <?php include 'layouts/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php include 'layouts/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Halaman Test Sidebar</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Uji Coba</h6>
                        </div>
                        <div class="card-body">
                            <p>Coba klik tombol minimize sidebar sekarang.</p>
                            <p>Jika ini berhasil, berarti masalahnya ada di file <b>style.php</b>.</p>
                        </div>
                    </div>
                </div>
                </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Test 2025</span>
                    </div>
                </div>
            </footer>

        </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

</body>
</html>