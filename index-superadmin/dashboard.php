
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Port Report Issues - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="form3.html">
                <div class="sidebar-brand-text mx-3">Port Report Issues</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">


            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="charts.php">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Dashboard</span></a>
            </li>

            <!-- Nav Item - Form -->
            <li class="nav-item">
                <a class="nav-link" href="form3.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Form Report</span></a>
            </li>

            <!-- Nav Item - Data Report -->
            <li class="nav-item">
                    <a class="nav-link" href="datareport.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Report</span></a>
             </li>


            <!-- Nav Item - Profile -->
            <li class="nav-item">
                <a class="nav-link" href="profile.html">
                <i class="fas fa-fw fa-table"></i>
                <span>Profile</span></a>
             </li>


            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>


                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Welcome Back !!!</span>
                                <img class="img-profile rounded-circle"
                                    src="../img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile

                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Dashboard</h1>
    <p class="mb-4">Here are some charts representing various data</p>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Jenis Perangkat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="jenisPerangkatChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lokasi Perangkat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="lokasiPerangkatChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Layanan Terdampak</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="layananTerdampakChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MI 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

<!-- Bootstrap core JavaScript-->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="../js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="../vendor/chart.js/Chart.min.js"></script>

<!-- Additional chart scripts -->
<script>
    fetch('getData.php')
        .then(response => response.json())
        .then(data => {
            const jenisPerangkatData = {};
            const lokasiPerangkatData = {};
            const layananTerdampakData = {};

            data.forEach(item => {
                // Jenis Perangkat
                if (item.jenis_perangkat && item.jenis_perangkat !== 'null') {
                    if (jenisPerangkatData[item.jenis_perangkat]) {
                        jenisPerangkatData[item.jenis_perangkat]++;
                    } else {
                        jenisPerangkatData[item.jenis_perangkat] = 1;
                    }
                }

                // Lokasi Perangkat
                if (item.lokasi_perangkat && item.lokasi_perangkat !== 'null') {
                    if (lokasiPerangkatData[item.lokasi_perangkat]) {
                        lokasiPerangkatData[item.lokasi_perangkat]++;
                    } else {
                        lokasiPerangkatData[item.lokasi_perangkat] = 1;
                    }
                }

                // Layanan Terdampak
                if (item.layanan_terdampak && item.layanan_terdampak !== 'null') {
                    const layananArray = item.layanan_terdampak.split(',');
                    layananArray.forEach(layanan => {
                        if (layanan && layanan !== 'null') {
                            if (layananTerdampakData[layanan]) {
                                layananTerdampakData[layanan]++;
                            } else {
                                layananTerdampakData[layanan] = 1;
                            }
                        }
                    });
                }
            });

            // Urutkan data berdasarkan kunci (0, 1, 2, 3, ...)
            const sortData = (data) => {
                const sortedKeys = Object.keys(data).sort((a, b) => a - b);
                const sortedData = {};
                sortedKeys.forEach(key => {
                    sortedData[key] = data[key];
                });
                return sortedData;
            };

            const sortedJenisPerangkatData = sortData(jenisPerangkatData);
            const sortedLokasiPerangkatData = sortData(lokasiPerangkatData);
            const sortedLayananTerdampakData = sortData(layananTerdampakData);

            // Data untuk chart Jenis Perangkat
            const jenisPerangkatLabels = Object.keys(sortedJenisPerangkatData);
            const jenisPerangkatValues = Object.values(sortedJenisPerangkatData).map(value => parseInt(value));

            // Data untuk chart Lokasi Perangkat
            const lokasiPerangkatLabels = Object.keys(sortedLokasiPerangkatData);
            const lokasiPerangkatValues = Object.values(sortedLokasiPerangkatData).map(value => parseInt(value));

            // Data untuk chart Layanan Terdampak
            const layananTerdampakLabels = Object.keys(sortedLayananTerdampakData);
            const layananTerdampakValues = Object.values(sortedLayananTerdampakData).map(value => parseInt(value));

            // Buat chart Jenis Perangkat (Bar)
            const ctxJenisPerangkat = document.getElementById('jenisPerangkatChart').getContext('2d');
            new Chart(ctxJenisPerangkat, {
                type: 'bar',
                data: {
                    labels: jenisPerangkatLabels,
                    datasets: [{
                        label: 'Jenis Perangkat',
                        data: jenisPerangkatValues,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Buat chart Lokasi Perangkat (Area)
            const ctxLokasiPerangkat = document.getElementById('lokasiPerangkatChart').getContext('2d');
            new Chart(ctxLokasiPerangkat, {
                type: 'line',
                data: {
                    labels: lokasiPerangkatLabels,
                    datasets: [{
                        label: 'Lokasi Perangkat',
                        data: lokasiPerangkatValues,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Buat chart Layanan Terdampak (Pie)
            const ctxLayananTerdampak = document.getElementById('layananTerdampakChart').getContext('2d');
            new Chart(ctxLayananTerdampak, {
                type: 'pie',
                data: {
                    labels: layananTerdampakLabels,
                    datasets: [{
                        label: 'Layanan Terdampak',
                        data: layananTerdampakValues,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                }
            });
        });
</script>

</body>

</html>
