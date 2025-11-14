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

// ===== BARU: Ambil Daftar Faktor untuk Header Tabel =====
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
    $ahp_faktor_selects .= ", MAX(CASE WHEN dpa.faktor_id = $fid THEN dpa.hasil END) AS F_AHP_$fid \n";
    $kpi_faktor_selects .= ", SUM(CASE WHEN ik.faktor_id = $fid THEN dp.hasil END) AS F_KPI_$fid \n";
}


// ===== Filter WHERE (Direvisi Total untuk STATUS) =====

// Filter Karyawan (hanya berdasarkan unit)
$where_karyawan = "WHERE 1=1";
if ($filter_unit) $where_karyawan .= " AND unit_project_id = ".intval($filter_unit);

// Filter Karyawan AKTIF (hanya berdasarkan unit)
$where_karyawan_aktif = $where_karyawan . " AND status = 'Aktif'";

// Filter Penilaian (berdasarkan unit, periode, DAN STATUS AKTIF)
$where_kpi = "WHERE k.status = 'Aktif'"; // <-- FILTER UTAMA BARU
if ($filter_unit) $where_kpi .= " AND k.unit_project_id = ".intval($filter_unit);
if ($filter_periode) $where_kpi .= " AND pk.periode_id = ".intval($filter_periode);

// Filter Penilaian AHP (berdasarkan unit, periode, DAN STATUS AKTIF)
$where_ahp = "WHERE k.status = 'Aktif'"; // <-- FILTER UTAMA BARU
if ($filter_unit) $where_ahp .= " AND k.unit_project_id = ".intval($filter_unit);
if ($filter_periode) $where_ahp .= " AND pkahp.periode_id = ".intval($filter_periode);


// ===== 1. STATISTIK (DIRUBAH TOTAL UNTUK STATUS AKTIF) =====

// Box 1: Total Karyawan (Semua Status)
$total_karyawan_count = $conn->query("SELECT COUNT(id) as total FROM karyawans $where_karyawan")->fetch_assoc()['total'];

// Box 2: Total Karyawan Aktif
$total_karyawan_aktif_count = $conn->query("SELECT COUNT(id) as total FROM karyawans $where_karyawan_aktif")->fetch_assoc()['total'];

// Box 3: Karyawan Aktif Sudah Dinilai
$sudah_dinilai_count = $conn->query("SELECT COUNT(DISTINCT pk.karyawan_id) as total FROM penilaian_kpi pk JOIN karyawans k ON pk.karyawan_id = k.id $where_kpi")->fetch_assoc()['total'];

// Box 4: Karyawan Aktif Belum Dinilai
$belum_dinilai_count = $total_karyawan_aktif_count - $sudah_dinilai_count;


// ===== 2. DATA GRAFIK (PERBAIKAN LOGIKA BELUM DINILAI) =====
$where_graph_unit_filter = ""; 
if ($filter_unit) {
    $graph_join_unit_condition = "AND k.unit_project_id = ".intval($filter_unit);
} else {
    $graph_join_unit_condition = "";
}

$graph_where_periode_condition = "WHERE 1=1";
if ($filter_periode) {
    $graph_where_periode_condition .= " AND p.id = ".intval($filter_periode);
}

// Kueri mengambil 'sudah dinilai' per periode (hanya karyawan aktif)
$sql_graph = "
    SELECT 
        p.id, 
        p.nama_periode,
        COUNT(DISTINCT pk.karyawan_id) AS sudah_dinilai_in_periode
    FROM 
        periode_penilaian p
    LEFT JOIN 
        penilaian_kpi pk ON p.id = pk.periode_id
    LEFT JOIN
        karyawans k ON pk.karyawan_id = k.id AND k.status = 'Aktif' $graph_join_unit_condition
    $graph_where_periode_condition
    GROUP BY 
        p.id, p.nama_periode
    ORDER BY 
        p.id ASC
";

$graph_data_query = $conn->query($sql_graph);

$labels_graph = [];
$values_sudah_dinilai = [];
$values_belum_dinilai = [];

while ($row = $graph_data_query->fetch_assoc()) {
    $labels_graph[] = $row['nama_periode'];
    
    $sudah = intval($row['sudah_dinilai_in_periode']);
    // PERBAIKAN: 'Belum' dihitung dari 'Total Aktif'
    $belum = $total_karyawan_aktif_count - $sudah; 
    
    $values_sudah_dinilai[] = $sudah;
    $values_belum_dinilai[] = $belum;
}


// ===== 3. TOP/BOTTOM 5 (FILTER STATUS AKTIF DITERAPKAN DARI $where_kpi / $where_ahp) =====

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
    $where_kpi -- Filter status aktif sudah ada di sini
    GROUP BY pk.id, u.name, pk.total_nilai, p.nama_periode, up.name
";
$top_karyawan_kpi = $conn->query($sql_kpi_base . " ORDER BY pk.total_nilai DESC LIMIT 5");
$low_karyawan_kpi = $conn->query($sql_kpi_base . " ORDER BY pk.total_nilai ASC LIMIT 5");


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
    $where_ahp -- Filter status aktif sudah ada di sini
    GROUP BY pkahp.id, u.name, pkahp.total_nilai, p.nama_periode, up.name
";
$top_karyawan_ahp = $conn->query($sql_ahp_base . " ORDER BY pkahp.total_nilai DESC LIMIT 5");
$low_karyawan_ahp = $conn->query($sql_ahp_base . " ORDER BY pkahp.total_nilai ASC LIMIT 5");

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
        
        .table-ranking {
            font-size: 0.8rem;
        }
        
        .card-stats .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 120px;
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
            <div class="col-md-3 mb-4">
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Total Karyawan</h5>
                        <p class="fs-4 font-weight-bold text-dark"><?= $total_karyawan_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Total Karyawan Aktif</h5>
                        <p class="fs-4 font-weight-bold text-primary"><?= $total_karyawan_aktif_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Aktif Sudah Dinilai</h5>
                        <p class="fs-4 font-weight-bold text-success"><?= $sudah_dinilai_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Aktif Belum Dinilai</h5>
                        <p class="fs-4 font-weight-bold text-danger"><?= $belum_dinilai_count ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5>Grafik Penilaian Karyawan per Periode (Hanya Karyawan Aktif)</h5>
                        <canvas id="penilaianChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6"> 
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5>Top 5 Karyawan Aktif Berdasarkan KPI (Tertinggi)</h5>
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
                        <h5>Bottom 5 Karyawan Aktif Berdasarkan KPI (Terendah)</h5>
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
                        <h5>Top 5 Karyawan Aktif Berdasarkan AHP (Tertinggi)</h5>
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
                        <h5>Bottom 5 Karyawan Aktif Berdasarkan AHP (Terendah)</h5>
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
// 1. Chart Penilaian (Stacked)
const ctxPenilaian = document.getElementById('penilaianChart').getContext('2d');
new Chart(ctxPenilaian, {
    type: 'bar', 
    data: { 
        labels: <?= json_encode($labels_graph) ?>,
        datasets: [
            { 
                label: 'Sudah Dinilai', 
                data: <?= json_encode($values_sudah_dinilai) ?>, 
                backgroundColor: 'rgba(75, 192, 192, 0.7)', // Hijau
                borderColor: 'rgba(75, 192, 192, 1)', 
                borderWidth: 1 
            },
            { 
                label: 'Belum Dinilai', 
                data: <?= json_encode($values_belum_dinilai) ?>, 
                backgroundColor: 'rgba(255, 99, 132, 0.7)', // Merah
                borderColor: 'rgba(255, 99, 132, 1)', 
                borderWidth: 1 
            }
        ]
    },
    options: { 
        responsive: true, 
        tooltips: {
            mode: 'index',
            intersect: false
        },
        scales: { 
            yAxes: [{ 
                stacked: true, // Membuat bar bertumpuk
                ticks: { 
                    beginAtZero: true, 
                }, 
                scaleLabel: { display: true, labelString: 'Jumlah Karyawan' } 
            }], 
            xAxes: [{ 
                stacked: true, // Membuat bar bertumpuk
                scaleLabel: { display: true, labelString: 'Periode' } 
            }] 
        } 
    }
});
</script>
</body>
</html>