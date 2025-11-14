<?php
session_start();

// Cek role user
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

// Ambil filter dari request
$filter_unit    = $_GET['unit'] ?? '';
$filter_periode = $_GET['periode'] ?? '';

// Ambil data unit
$units = $conn->query("SELECT id, name FROM unit_projects ORDER BY name ASC");

// Ambil data periode
$periodes = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id ASC");

// ===== BARU: Ambil Daftar Faktor untuk Header & Kueri =====
$faktorList = [];
$resFaktor = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
while ($row = $resFaktor->fetch_assoc()) {
    $faktorList[] = $row;
}

// ===== BARU: Buat klausa SELECT dinamis untuk PIVOT =====
$kpi_faktor_selects = ""; // Untuk KPI
$ahp_faktor_selects = ""; // Untuk AHP

foreach ($faktorList as $f) {
    $fid = $f['id'];
    
    // Klausa AHP (lebih mudah, dari detail_penilaian_ahp)
    $ahp_faktor_selects .= ", MAX(CASE WHEN dpa.faktor_id = $fid THEN dpa.hasil END) AS F_AHP_$fid \n";
    
    // Klausa KPI (lebih kompleks, harus SUM(hasil) dari indikator)
    $kpi_faktor_selects .= ", SUM(CASE WHEN ik.faktor_id = $fid THEN dp.hasil END) AS F_KPI_$fid \n";
}


// ===== Filter WHERE (Tidak Berubah) =====
// Filter untuk KPI (tabel pk)
$where_kpi = "WHERE 1=1";
if ($filter_unit) $where_kpi .= " AND k.unit_project_id = ".intval($filter_unit);
if ($filter_periode) $where_kpi .= " AND pk.periode_id = ".intval($filter_periode);

// Filter untuk AHP (tabel pkahp)
$where_ahp = "WHERE 1=1";
if ($filter_unit) $where_ahp .= " AND k.unit_project_id = ".intval($filter_unit);
if ($filter_periode) $where_ahp .= " AND pkahp.periode_id = ".intval($filter_periode);


// ===== 1. STATISTIK (Tidak Berubah) =====
// (Statistik umum tidak perlu diubah, kita biarkan)
$count_penilaian_kpi = $conn->query("SELECT COUNT(*) as total FROM penilaian_kpi pk JOIN karyawans k ON pk.karyawan_id = k.id $where_kpi")->fetch_assoc()['total'];
$rata2_kpi = $conn->query("SELECT AVG(pk.total_nilai) as rata FROM penilaian_kpi pk JOIN karyawans k ON pk.karyawan_id = k.id $where_kpi")->fetch_assoc()['rata'] ?? 0;
$count_penilaian_ahp = $conn->query("SELECT COUNT(*) as total FROM penilaian_kpi_ahp pkahp JOIN karyawans k ON pkahp.karyawan_id = k.id $where_ahp")->fetch_assoc()['total'];
$rata2_ahp = $conn->query("SELECT AVG(pkahp.total_nilai) as rata FROM penilaian_kpi_ahp pkahp JOIN karyawans k ON pkahp.karyawan_id = k.id $where_ahp")->fetch_assoc()['rata'] ?? 0;


// ===== 2. DATA GRAFIK (Tidak Berubah) =====
// (Grafik utama tidak perlu diubah)
// Data Grafik KPI
$kpi_periode_data = $conn->query("SELECT p.id, p.nama_periode, AVG(pk.total_nilai) AS rata_nilai FROM penilaian_kpi pk JOIN periode_penilaian p ON pk.periode_id = p.id JOIN karyawans k ON pk.karyawan_id = k.id $where_kpi GROUP BY p.id, p.nama_periode ORDER BY p.id ASC");
$labels_kpi = []; $values_kpi = [];
while ($row = $kpi_periode_data->fetch_assoc()) { $labels_kpi[] = $row['nama_periode']; $values_kpi[] = round($row['rata_nilai'], 2); }
// Data Grafik AHP
$ahp_periode_data = $conn->query("SELECT p.id, p.nama_periode, AVG(pkahp.total_nilai) AS rata_nilai FROM penilaian_kpi_ahp pkahp JOIN periode_penilaian p ON pkahp.periode_id = p.id JOIN karyawans k ON pkahp.karyawan_id = k.id $where_ahp GROUP BY p.id, p.nama_periode ORDER BY p.id ASC");
$labels_ahp = []; $values_ahp = [];
while ($row = $ahp_periode_data->fetch_assoc()) { $labels_ahp[] = $row['nama_periode']; $values_ahp[] = round($row['rata_nilai'], 4); }


// ===== 3. TOP/BOTTOM 3 (KUERI DIUBAH TOTAL) =====

// --- KUERI KPI DENGAN PIVOT FAKTOR ---
$sql_kpi_base = "
    SELECT 
        u.name, pk.total_nilai, p.nama_periode, up.name AS nama_unit, pk.id
        $kpi_faktor_selects
    FROM penilaian_kpi pk
    JOIN karyawans k ON pk.karyawan_id = k.id
    JOIN users u ON k.user_id = u.id
    JOIN periode_penilaian p ON pk.periode_id = p.id
    JOIN unit_projects up ON k.unit_project_id = up.id
    LEFT JOIN detail_penilaian dp ON dp.penilaian_id = pk.id
    LEFT JOIN indikator_kompetensi ik ON dp.indikator_id = ik.id
    $where_kpi
    GROUP BY pk.id, u.name, pk.total_nilai, p.nama_periode, up.name
";

// Top 3 KPI
$top_karyawan_kpi = $conn->query($sql_kpi_base . " ORDER BY pk.total_nilai DESC LIMIT 3");
// Bottom 3 KPI
$low_karyawan_kpi = $conn->query($sql_kpi_base . " ORDER BY pk.total_nilai ASC LIMIT 3");


// --- KUERI AHP DENGAN PIVOT FAKTOR ---
$sql_ahp_base = "
    SELECT 
        u.name, pkahp.total_nilai, p.nama_periode, up.name AS nama_unit, pkahp.id
        $ahp_faktor_selects
    FROM penilaian_kpi_ahp pkahp
    JOIN karyawans k ON pkahp.karyawan_id = k.id
    JOIN users u ON k.user_id = u.id
    JOIN periode_penilaian p ON pkahp.periode_id = p.id
    JOIN unit_projects up ON k.unit_project_id = up.id
    LEFT JOIN detail_penilaian_ahp dpa ON dpa.penilaian_id = pkahp.id
    $where_ahp
    GROUP BY pkahp.id, u.name, pkahp.total_nilai, p.nama_periode, up.name
";

// Top 3 AHP
$top_karyawan_ahp = $conn->query($sql_ahp_base . " ORDER BY pkahp.total_nilai DESC LIMIT 3");
// Bottom 3 AHP
$low_karyawan_ahp = $conn->query($sql_ahp_base . " ORDER BY pkahp.total_nilai ASC LIMIT 3");

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

    <style>
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* ===== CSS BARU UNTUK MENGECILKAN FONT TABEL ===== */
        .table-ranking {
            font-size: 0.8rem; /* Anda bisa sesuaikan nilainya, misal 0.85rem atau 13px */
        }
    </style>
</head>

<body id="page-top">
<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <main class="container-fluid px-4 py-4">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Dashboard KPI</h4>
        </div>
        <hr>

        <form method="get" class="row mb-4">
            <div class="col-md-4">
                <label>Filter Unit/Project</label>
                <select name="unit" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Unit --</option>
                    <?php mysqli_data_seek($units, 0); // Reset pointer
                    while($u = $units->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_unit==$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Filter Periode</label>
                <select name="periode" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Periode --</option>
                    <?php mysqli_data_seek($periodes, 0); // Reset pointer
                    while($p = $periodes->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_periode==$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_periode']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <div class="row text-center mb-4">
            <div class="col-md-3"><div class="card shadow"><div class="card-body"><h5>Total Penilaian (KPI)</h5><p class="fs-4"><?= $count_penilaian_kpi ?></p></div></div></div>
            <div class="col-md-3"><div class="card shadow"><div class="card-body"><h5>Rata-rata Nilai (KPI)</h5><p class="fs-4"><?= number_format($rata2_kpi, 2) ?></p></div></div></div>
            <div class="col-md-3"><div class="card shadow"><div class="card-body bg-light"><h5>Total Penilaian (AHP)</h5><p class="fs-4"><?= $count_penilaian_ahp ?></p></div></div></div>
            <div class="col-md-3"><div class="card shadow"><div class="card-body bg-light"><h5>Rata-rata Nilai (AHP)</h5><p class="fs-4"><?= number_format($rata2_ahp, 3) ?></p></div></div></div>
        </div>

        <div class="row">
            <div class="col-lg-6"><div class="card shadow mb-4"><div class="card-body"><h5>Grafik Rata-rata Nilai KPI per Periode (Skala 1-4)</h5><canvas id="kpiChart"></canvas></div></div></div>
            <div class="col-lg-6"><div class="card shadow mb-4"><div class="card-body"><h5>Grafik Rata-rata Nilai AHP per Periode (Skala 0-1)</h5><canvas id="ahpChart"></canvas></div></div></div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5>Top 3 Karyawan Berdasarkan KPI (Tertinggi)</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm table-ranking mt-3">
                                <thead>
                                    <tr>
                                        <th>No</th><th>Nama</th><th>Unit</th>
                                        <?php foreach ($faktorList as $f): ?>
                                            <th><?= htmlspecialchars($f['nama']) ?></th>
                                        <?php endforeach; ?>
                                        <th>Total KPI</th><th>Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row=$top_karyawan_kpi->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                                        <?php foreach ($faktorList as $f):
                                            $alias = "F_KPI_" . $f['id'];
                                            $nilai_faktor = $row[$alias] ?? 0;
                                        ?>
                                            <td><?= number_format($nilai_faktor, 2) ?></td>
                                        <?php endforeach; ?>
                                        <td class="font-weight-bold"><?= number_format($row['total_nilai'], 2) ?></td>
                                        <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card shadow mb-5">
                    <div class="card-body">
                        <h5>Bottom 3 Karyawan Berdasarkan KPI (Terendah)</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm table-ranking mt-3">
                                <thead>
                                    <tr>
                                        <th>No</th><th>Nama</th><th>Unit</th>
                                        <?php foreach ($faktorList as $f): ?>
                                            <th><?= htmlspecialchars($f['nama']) ?></th>
                                        <?php endforeach; ?>
                                        <th>Total KPI</th><th>Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row=$low_karyawan_kpi->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                                        <?php foreach ($faktorList as $f):
                                            $alias = "F_KPI_" . $f['id'];
                                            $nilai_faktor = $row[$alias] ?? 0;
                                        ?>
                                            <td><?= number_format($nilai_faktor, 2) ?></td>
                                        <?php endforeach; ?>
                                        <td class="font-weight-bold"><?= number_format($row['total_nilai'], 2) ?></td>
                                        <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-body bg-light">
                        <h5>Top 3 Karyawan Berdasarkan AHP (Tertinggi)</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm table-ranking mt-3">
                                <thead>
                                    <tr>
                                        <th>No</th><th>Nama</th><th>Unit</th>
                                        <?php foreach ($faktorList as $f): ?>
                                            <th><?= htmlspecialchars($f['nama']) ?></th>
                                        <?php endforeach; ?>
                                        <th>Total AHP</th><th>Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row=$top_karyawan_ahp->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                                        <?php foreach ($faktorList as $f):
                                            $alias = "F_AHP_" . $f['id'];
                                            $nilai_faktor = $row[$alias] ?? 0;
                                        ?>
                                            <td><?= number_format($nilai_faktor, 3) ?></td>
                                        <?php endforeach; ?>
                                        <td class="font-weight-bold"><?= number_format($row['total_nilai'], 3) ?></td>
                                        <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card shadow mb-5">
                    <div class="card-body bg-light">
                        <h5>Bottom 3 Karyawan Berdasarkan AHP (Terendah)</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm table-ranking mt-3">
                                <thead>
                                    <tr>
                                        <th>No</th><th>Nama</th><th>Unit</th>
                                        <?php foreach ($faktorList as $f): ?>
                                            <th><?= htmlspecialchars($f['nama']) ?></th>
                                        <?php endforeach; ?>
                                        <th>Total AHP</th><th>Periode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row=$low_karyawan_ahp->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_unit']) ?></td>
                                        <?php foreach ($faktorList as $f):
                                            $alias = "F_AHP_" . $f['id'];
                                            $nilai_faktor = $row[$alias] ?? 0;
                                        ?>
                                            <td><?= number_format($nilai_faktor, 3) ?></td>
                                        <?php endforeach; ?>
                                        <td class="font-weight-bold"><?= number_format($row['total_nilai'],3) ?></td>
                                        <td><?= htmlspecialchars($row['nama_periode']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
// 1. Chart KPI
const ctxKpi = document.getElementById('kpiChart').getContext('2d');
new Chart(ctxKpi, {
    type: 'bar', data: { labels: <?= json_encode($labels_kpi) ?>,
        datasets: [{ label: 'Rata-rata KPI', data: <?= json_encode($values_kpi) ?>, backgroundColor: 'rgba(54, 162, 235, 0.7)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }]
    },
    options: { responsive: true, scales: { yAxes: [{ ticks: { beginAtZero: true, min: 0, max: 4.0, stepSize: 0.5 }, scaleLabel: { display: true, labelString: 'Rata-rata Nilai KPI (1-4)' } }], xAxes: [{ scaleLabel: { display: true, labelString: 'Periode' } }] } }
});
// 2. Chart AHP
const ctxAhp = document.getElementById('ahpChart').getContext('2d');
new Chart(ctxAhp, {
    type: 'bar', data: { labels: <?= json_encode($labels_ahp) ?>,
        datasets: [{ label: 'Rata-rata Nilai AHP', data: <?= json_encode($values_ahp) ?>, backgroundColor: 'rgba(75, 192, 192, 0.7)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 }]
    },
    options: { responsive: true, scales: { yAxes: [{ ticks: { beginAtZero: true, min: 0, max: 1.0, stepSize: 0.1 }, scaleLabel: { display: true, labelString: 'Rata-rata Nilai AHP (0-1)' } }], xAxes: [{ scaleLabel: { display: true, labelString: 'Periode' } }] } }
});
</script>
</body>
</html>
