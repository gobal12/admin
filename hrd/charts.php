<?php
session_start();

// Cek role user
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('hrd');

// Nama user yang login
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

require_once '../db_connection.php';

// Ambil filter dari request
$filter_unit    = $_GET['unit'] ?? '';
$filter_periode = $_GET['periode'] ?? '';

// Ambil data unit
$units = $conn->query("SELECT id, name FROM unit_projects ORDER BY name ASC");

// Ambil data periode
$periodes = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id ASC");

// Tambahkan kondisi filter ke query
$where = "WHERE 1=1";
if ($filter_unit) {
    $where .= " AND k.unit_project_id = '".intval($filter_unit)."'";
}
if ($filter_periode) {
    $where .= " AND pk.periode_id = '".intval($filter_periode)."'";
}

// Statistik umum
$count_karyawan = $conn->query("
    SELECT COUNT(DISTINCT k.id) as total
    FROM karyawans k
    JOIN penilaian_kpi pk ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['total'];

$count_periode = $conn->query("
    SELECT COUNT(DISTINCT p.id) as total
    FROM periode_penilaian p
    JOIN penilaian_kpi pk ON pk.periode_id = p.id
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['total'];

$count_penilaian = $conn->query("
    SELECT COUNT(*) as total
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['total'];

$rata2_kpi = $conn->query("
    SELECT AVG(pk.total_nilai) as rata
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
")->fetch_assoc()['rata'] ?? 0;

// Data grafik: rata-rata nilai per periode
$periode_data = $conn->query("
    SELECT p.id, p.nama_periode, AVG(pk.total_nilai) AS rata_nilai
    FROM penilaian_kpi pk
    JOIN periode_penilaian p ON pk.periode_id = p.id
    JOIN karyawans k ON pk.karyawan_id = k.id
    $where
    GROUP BY p.id, p.nama_periode
    ORDER BY p.id ASC
");

$labels = [];
$values = [];
while ($row = $periode_data->fetch_assoc()) {
    $labels[] = $row['nama_periode'];
    $values[] = isset($row['rata_nilai']) ? round($row['rata_nilai'], 2) : 0;
}

// Top 3 Karyawan terbaik
$top_karyawan = $conn->query("
    SELECT u.name, pk.total_nilai, p.nama_periode, up.name AS nama_unit
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    JOIN users u ON k.user_id = u.id
    JOIN periode_penilaian p ON pk.periode_id = p.id
    JOIN unit_projects up ON k.unit_project_id = up.id
    $where
    ORDER BY pk.total_nilai DESC
    LIMIT 3
");

// Bottom 3 Karyawan terendah
$low_karyawan = $conn->query("
    SELECT u.name, pk.total_nilai, p.nama_periode, up.name AS nama_unit
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    JOIN users u ON k.user_id = u.id
    JOIN periode_penilaian p ON pk.periode_id = p.id
    JOIN unit_projects up ON k.unit_project_id = up.id
    $where
    ORDER BY pk.total_nilai ASC
    LIMIT 3
");
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
            <h4 class="m-0 font-weight-bold">Dashboard</h4>
        </div>
        <hr>

        <!-- Filter -->
        <form method="get" class="row mb-4">
            <div class="col-md-4">
                <label>Filter Unit/Project</label>
                <select name="unit" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Unit --</option>
                    <?php while($u = $units->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_unit==$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Filter Periode</label>
                <select name="periode" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Periode --</option>
                    <?php while($p = $periodes->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_periode==$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_periode']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <!-- Statistik -->
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card shadow"><div class="card-body">
                    <h5>Total Karyawan</h5>
                    <p class="fs-4"><?= $count_karyawan ?></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow"><div class="card-body">
                    <h5>Total Periode</h5>
                    <p class="fs-4"><?= $count_periode ?></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow"><div class="card-body">
                    <h5>Total Penilaian</h5>
                    <p class="fs-4"><?= $count_penilaian ?></p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow"><div class="card-body">
                    <h5>Rata-rata Nilai</h5>
                    <p class="fs-4"><?= number_format($rata2_kpi, 2) ?></p>
                </div></div>
            </div>
        </div>

        <!-- Grafik -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5>Grafik Rata-rata Nilai KPI per Periode</h5>
                <canvas id="kpiChart"></canvas>
            </div>
        </div>

        <!-- Top 3 -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5>Top 3 Karyawan Berdasarkan KPI (Tertinggi)</h5>
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Unit</th>
                            <th>Nilai</th>
                            <th>Periode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row=$top_karyawan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                            <td><?= number_format($row['total_nilai'],2) ?></td>
                            <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom 3 -->
        <div class="card shadow mb-5">
            <div class="card-body">
                <h5>Bottom 3 Karyawan Berdasarkan KPI (Terendah)</h5>
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Unit</th>
                            <th>Nilai</th>
                            <th>Periode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row=$low_karyawan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                            <td><?= number_format($row['total_nilai'],2) ?></td>
                            <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
