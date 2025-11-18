<?php
session_start();
require_once '../db_connection.php'; // Pindahkan ke atas

// Cek role user
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
check_role('karyawan'); 

// Nama user yang login
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

// Dapatkan ID user dan karyawan yang login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];

// Dapatkan karyawan_id dari user_id
$stmt_karyawan = $conn->prepare("SELECT id FROM karyawans WHERE user_id = ? AND status = 'Aktif'");
$stmt_karyawan->bind_param("i", $logged_in_user_id);
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();

if ($result_karyawan->num_rows === 0) {
    die("Data karyawan tidak ditemukan atau tidak aktif. Hubungi administrator.");
}
$karyawan_data = $result_karyawan->fetch_assoc();
$logged_in_karyawan_id = $karyawan_data['id']; 
$stmt_karyawan->close();


// ===== KUERI DATA GRAFIK & HISTORI =====
$stmt_graph = $conn->prepare("
    SELECT 
        p.id, 
        p.nama_periode,
        pk.total_nilai AS nilai_kpi,
        pkahp.total_nilai AS nilai_ahp,
        pk.id AS kpi_penilaian_id,    -- Ditambahkan untuk link detail
        pkahp.id AS ahp_penilaian_id  -- Ditambahkan untuk link detail
    FROM 
        periode_penilaian p
    LEFT JOIN 
        penilaian_kpi pk ON p.id = pk.periode_id AND pk.karyawan_id = ?
    LEFT JOIN 
        penilaian_kpi_ahp pkahp ON p.id = pkahp.periode_id AND pkahp.karyawan_id = ?
    ORDER BY 
        p.id ASC
");
$stmt_graph->bind_param("ii", $logged_in_karyawan_id, $logged_in_karyawan_id);
$stmt_graph->execute();
$graph_data_query = $stmt_graph->get_result();

$labels_graph = [];
$values_nilai_kpi = [];
$values_nilai_ahp = [];
$history_data = []; 

// === PERBAIKAN LOGIKA VARIABEL ===
$nilai_kpi_terakhir = null;
$nilai_ahp_terakhir = null;
$periode_kpi_terakhir = "N/A"; // <-- Ditambahkan
$periode_ahp_terakhir = "N/A"; // <-- Ditambahkan

while ($row = $graph_data_query->fetch_assoc()) {
    $labels_graph[] = $row['nama_periode'];
    
    $current_kpi = $row['nilai_kpi'] ?? null;
    $current_ahp = $row['nilai_ahp'] ?? null;
    
    // Data untuk Grafik
    $values_nilai_kpi[] = $current_kpi; 
    $values_nilai_ahp[] = $current_ahp;
    
    // Simpan nilai non-null terakhir
    // Karena query di-ORDER BY id ASC, nilai terakhir yang di-loop adalah yang terbaru
    if ($current_kpi !== null) {
        $nilai_kpi_terakhir = $current_kpi;
        $periode_kpi_terakhir = $row['nama_periode']; // <-- Ditambahkan
    }
    if ($current_ahp !== null) {
        $nilai_ahp_terakhir = $current_ahp;
        $periode_ahp_terakhir = $row['nama_periode']; // <-- Ditambahkan
    }
    
    // Data untuk Tabel Histori
    if ($current_kpi !== null || $current_ahp !== null) {
        $history_data[] = $row;
    }
}
$stmt_graph->close();
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KPI Nutech Operation - Dashboard Anda</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <script src="../vendor/chart.js/Chart.min.js"></script>
    <?php include 'layouts/style.php';?>

    <style>
        .card-stats .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 120px; /* Samakan tinggi kotak */
        }
    </style>
</head>

<body id="page-top">
<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <main class="container-fluid px-4 py-4">
        <div class="card-header py-3 bg-primary text-white">
            <h4 class="m-0 font-weight-bold">Dashboard Kinerja Anda</h4>
        </div>
        <hr>
      
        <div class="row text-center mb-4">
            <div class="col-md-6 mb-4"> 
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Nilai KPI Terakhir</h5>
                        <p class="fs-4 font-weight-bold text-primary">
                            <?= $nilai_kpi_terakhir !== null ? number_format($nilai_kpi_terakhir, 2) : 'N/A' ?>
                        </p>
                        <small class="text-muted">
                            (<?= htmlspecialchars($periode_kpi_terakhir) ?>)
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4"> 
                <div class="card shadow card-stats">
                    <div class="card-body">
                        <h5>Nilai AHP Terakhir</h5>
                        <p class="fs-4 font-weight-bold text-success">
                            <?= $nilai_ahp_terakhir !== null ? number_format($nilai_ahp_terakhir, 3) : 'N/A' ?>
                        </p>
                        <small class="text-muted">
                            (<?= htmlspecialchars($periode_ahp_terakhir) ?>)
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5>Grafik Tren Kinerja Anda per Periode</h5>
                        <canvas id="penilaianChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Histori Nilai Anda</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTableHistory" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Periode Penilaian</th>
                                        <th>Skor Akhir KPI</th>
                                        <th>Skor Akhir AHP</th>
                                        <th>Aksi</th> </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($history_data)):
                                        $no = 1;
                                        // array_reverse untuk menampilkan data terbaru (ID terbesar) di atas
                                        foreach (array_reverse($history_data) as $row): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_periode']); ?></td>
                                        <td><?php echo $row['nilai_kpi'] !== null ? number_format($row['nilai_kpi'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $row['nilai_ahp'] !== null ? number_format($row['nilai_ahp'], 3) : 'N/A'; ?></td>
                                        <td>
                                            <?php if($row['kpi_penilaian_id']): ?>
                                                <a href="detailkpi.php?penilaian_id=<?= $row['kpi_penilaian_id'] ?>" 
                                                class="btn btn-outline-info btn-sm" 
                                                title="Lihat Detail KPI">
                                                <i class="fas fa-eye"></i> KPI
                                                </a>
                                            <?php endif; ?>
                                            <?php if($row['ahp_penilaian_id']): ?>
                                                <a href="detail_ahp.php?penilaian_id=<?= $row['ahp_penilaian_id'] ?>" 
                                                class="btn btn-outline-success btn-sm" 
                                                title="Lihat Detail AHP">
                                                <i class="fas fa-eye"></i> AHP
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada data histori penilaian.</td>
                                    </tr>
                                    <?php 
                                    endif; 
                                    ?>
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
    
<?php include 'layouts/page_end.php'; ?>

<script>
const ctxPenilaian = document.getElementById('penilaianChart').getContext('2d');
new Chart(ctxPenilaian, {
    type: 'bar', // Tipe 'bar' sudah benar
    data: { 
        labels: <?= json_encode($labels_graph) ?>,
        datasets: [
            { 
                label: 'Nilai KPI Anda', 
                // 'null' akan membuat bar kosong, 'spanGaps: true' (untuk line) tidak berlaku di bar
                data: <?= json_encode($values_nilai_kpi) ?>, 
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)', 
                borderWidth: 1
            },
            { 
                label: 'Nilai AHP Anda', 
                data: <?= json_encode($values_nilai_ahp) ?>, 
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)', 
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
                ticks: { 
                    beginAtZero: true, 
                }, 
                scaleLabel: { display: true, labelString: 'Total Nilai' } 
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