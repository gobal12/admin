<?php
session_start();

// Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('karyawan');

// Nama user yang login
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

require_once '../db_connection.php';

// Ambil filter dari request
$filter_periode = $_GET['periode'] ?? '';

// Filter utama: hanya data milik user yang login
$logged_in_user_id = $_SESSION['user_id']; 
$where = "WHERE k.user_id = '$logged_in_user_id'";
if ($filter_periode) {
    $where .= " AND pk.periode_id = '".intval($filter_periode)."'";
}

// Ambil data periode
$periodes = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id ASC");

// Statistik umum
$count_periode = $conn->query("
    SELECT COUNT(DISTINCT pk.periode_id) AS total
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['total'];

$count_penilaian = $conn->query("
    SELECT COUNT(*) AS total
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['total'];

$rata2_kpi = $conn->query("
    SELECT AVG(pk.total_nilai) AS rata
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['rata'] ?? 0;

// Data grafik KPI pribadi
$periode_data = $conn->query("
    SELECT p.nama_periode, pk.total_nilai
    FROM penilaian_kpi pk
    JOIN periode_penilaian p ON pk.periode_id = p.id
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
    ORDER BY p.id ASC
");

$labels = [];
$values = [];
while ($row = $periode_data->fetch_assoc()) {
    $labels[] = $row['nama_periode'];
    $values[] = round($row['total_nilai'], 2);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KPI Nutech Operation - Dashboard</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../vendor/chart.js/Chart.min.js"></script>
</head>

<body id="page-top">
<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <main class="container-fluid px-4 py-4">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Dashboard Karyawan</h4>
        </div>
    <div class="row">
        <!-- Total Periode -->
        <div class="col-md-4 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body text-center">
                    <h6 class="text-primary">Total Periode</h6>
                    <h3><?= $count_periode ?></h3>
                </div>
            </div>
        </div>

        <!-- Total Penilaian -->
        <div class="col-md-4 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body text-center">
                    <h6 class="text-success">Total Penilaian</h6>
                    <h3><?= $count_penilaian ?></h3>
                </div>
            </div>
        </div>

        <!-- Rata-rata KPI -->
        <div class="col-md-4 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body text-center">
                    <h6 class="text-info">Rata-rata KPI</h6>
                    <h3><?= number_format($rata2_kpi, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik KPI -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Grafik KPI Pribadi</h6>
        </div>
        <div class="card-body">
            <canvas id="kpiChart"></canvas>
        </div>
    </div>
    </main>
</div>

<?php include 'layouts/footer.php'; ?>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
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
                ticks: { beginAtZero: true, min: 0, max: 4.0, stepSize: 0.5 },
                scaleLabel: { display: true, labelString: 'Rata-rata Nilai KPI' }
            }],
            xAxes: [{
                scaleLabel: { display: true, labelString: 'Periode' }
            }]
        }
    }
});
</script>
</body>
</html>
