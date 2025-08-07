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

// Nama user yang login
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

require_once '../db_connection.php';

// Statistik umum
$count_karyawan = $conn->query("SELECT COUNT(*) as total FROM karyawans")->fetch_assoc()['total'];
$count_periode = $conn->query("SELECT COUNT(*) as total FROM periode_penilaian")->fetch_assoc()['total'];
$count_penilaian = $conn->query("SELECT COUNT(*) as total FROM penilaian_kpi")->fetch_assoc()['total'];
$rata2_kpi = $conn->query("SELECT AVG(total_nilai) as rata FROM penilaian_kpi")->fetch_assoc()['rata'] ?? 0;

// Data grafik: rata-rata nilai per periode
$periode_data = $conn->query("
    SELECT p.id, p.nama_periode, AVG(pk.total_nilai) AS rata_nilai
    FROM penilaian_kpi pk
    JOIN periode_penilaian p ON pk.periode_id = p.id
    GROUP BY p.id, p.nama_periode
    ORDER BY p.id ASC
");

$labels = [];
$values = [];
while ($row = $periode_data->fetch_assoc()) {
    $labels[] = $row['nama_periode'];
    $values[] = isset($row['rata_nilai']) ? round($row['rata_nilai'], 2) : 0;
}

// 5 Karyawan terbaik berdasarkan nilai
$top_karyawan = $conn->query("
    SELECT u.name, pk.total_nilai, p.nama_periode
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    JOIN users u ON k.user_id = u.id
    JOIN periode_penilaian p ON pk.periode_id = p.id
    ORDER BY pk.total_nilai DESC
    LIMIT 5
");
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>KPI Nutech Operation - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../vendor/chart.js/Chart.min.js"></script>

</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <main class="container-fluid px-4 py-4">
                    <h1 class="h3 mb-2 text-gray-800">Dashboard</h1>
                    <!-- Content Row -->
                    <hr>

                    <!-- Statistik -->
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Total Karyawan</h5>
                                    <p class="card-text fs-4"><?= $count_karyawan ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Total Periode</h5>
                                    <p class="card-text fs-4"><?= $count_periode ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Total Penilaian</h5>
                                    <p class="card-text fs-4"><?= $count_penilaian ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Rata-rata Nilai</h5>
                                    <p class="card-text fs-4"><?= number_format($rata2_kpi, 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <h5>Grafik Rata-rata Nilai KPI per Periode</h5>
                            <canvas id="kpiChart"></canvas>
                        </div>
                    </div>

                    <!-- Tabel 5 Terbaik -->
                    <div class="card shadow mb-5">
                        <div class="card-body">
                            <h5>Top 5 Karyawan Berdasarkan KPI</h5>
                            <table class="table table-striped mt-3">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Nilai</th>
                                        <th>Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    while ($row = $top_karyawan->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= number_format($row['total_nilai'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </main>
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
    <script src="../vendor/chart.js/Chart.min.js"></script>

<script>
    const ctx = document.getElementById('kpiChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Rata-rata KPI',
                data: <?= json_encode($values) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        min: 0,
                        max: 4.0,
                        stepSize: 0.5
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Rata-rata Nilai KPI'
                    }
                }],
                xAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Periode'
                    }
                }]
            }
        }
    });
</script>

</body>

</html>