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

// Tambahkan ini untuk inisialisasi $periode_id
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;

// Ambil daftar periode
$periodeList = [];
$resPeriode = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id DESC");
while ($row = $resPeriode->fetch_assoc()) {
    $periodeList[] = $row;
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

    <title>Perhitungan AHP</title>

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
                <h1 class="h3 mb-2 text-gray-800">Data KPI</h1>
                <p class="mb-4">Menampilkan Data perhitungan AHP</p>
    
<!-- Filter periode -->
<form method="GET" class="mb-3">
    <label for="periode_id">Filter Periode:</label>
    <select name="periode_id" id="periode_id" onchange="this.form.submit()" class="form-control" style="max-width:300px; display:inline-block;">
        <option value="0" <?= $periode_id === 0 ? 'selected' : '' ?>>-- Semua Periode --</option>
        <?php foreach ($periodeList as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $periode_id === (int)$p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nama_periode']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<!-- Tabel data -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Periode</th>
                        <?php
                        // Ambil daftar faktor untuk header kolom
                        $faktorList = [];
                        $resFaktor = $conn->query("SELECT id, nama FROM faktor_kompetensi ORDER BY id");
                        while ($row = $resFaktor->fetch_assoc()) {
                            $faktorList[] = $row;
                            echo "<th>" . htmlspecialchars($row['nama']) . "</th>";
                        }
                        ?>
                        <th>Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;

                    if ($periode_id > 0) {
                        // Query filter berdasarkan periode terpilih
                        $sql = "
                            SELECT 
                                pkahp.id AS penilaian_id, 
                                u.name AS nama_karyawan, 
                                pkahp.total_nilai,
                                pp.nama_periode
                            FROM penilaian_kpi_ahp pkahp
                            JOIN karyawans k ON k.id = pkahp.karyawan_id
                            JOIN users u ON u.id = k.user_id
                            JOIN periode_penilaian pp ON pp.id = pkahp.periode_id
                            WHERE pkahp.periode_id = ?
                            ORDER BY pkahp.total_nilai DESC, u.name
                        ";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $periode_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $detailSql = "SELECT penilaian_id, faktor_id, hasil FROM detail_penilaian_ahp WHERE periode_id = ?";
                        $detailStmt = $conn->prepare($detailSql);
                        $detailStmt->bind_param("i", $periode_id);
                        $detailStmt->execute();
                        $detailResult = $detailStmt->get_result();
                    } else {
                        // Semua data tanpa filter
                        $sql = "
                            SELECT 
                                pkahp.id AS penilaian_id, 
                                u.name AS nama_karyawan, 
                                pkahp.total_nilai,
                                pp.nama_periode
                            FROM penilaian_kpi_ahp pkahp
                            JOIN karyawans k ON k.id = pkahp.karyawan_id
                            JOIN users u ON u.id = k.user_id
                            JOIN periode_penilaian pp ON pp.id = pkahp.periode_id
                            ORDER BY pkahp.total_nilai DESC, pp.id DESC, u.name
                        ";

                        $result = $conn->query($sql);

                        $detailSql = "SELECT penilaian_id, faktor_id, hasil FROM detail_penilaian_ahp";
                        $detailResult = $conn->query($detailSql);
                    }

                    // Ambil detail nilai dalam array agar akses cepat
                    $detailData = [];
                    while ($d = $detailResult->fetch_assoc()) {
                        $detailData[$d['penilaian_id']][$d['faktor_id']] = $d['hasil'];
                    }
                    if (isset($stmt)) $stmt->close();
                    if (isset($detailStmt)) $detailStmt->close();

                    // Loop tampilkan data utama
                    while ($periode_id > 0 ? ($row = $result->fetch_assoc()) : ($row = $result->fetch_assoc())) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_karyawan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_periode']) . "</td>";

                        foreach ($faktorList as $f) {
                            $fid = $f['id'];
                            $nilai = isset($detailData[$row['penilaian_id']][$fid]) ? number_format($detailData[$row['penilaian_id']][$fid], 4) : '0.0000';
                            echo "<td>$nilai</td>";
                        }

                        echo "<td>" . number_format($row['total_nilai'], 4) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
            <!-- End of Main Content -->

            <!-- Footer -->

            <?php include 'layouts/footer.php'; ?>

            <!-- End of Footer -->

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="../js/demo/datatables-demo.js"></script>
    
</body>
</html>
