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

$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
include '../db_connection.php';

// Ambil filter dari GET
$periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;
$unit_id    = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

// Ambil daftar periode
$periodeList = [];
$resPeriode = $conn->query("SELECT id, nama_periode FROM periode_penilaian ORDER BY id DESC");
while ($row = $resPeriode->fetch_assoc()) {
    $periodeList[] = $row;
}

// Ambil daftar unit / project
$unitList = [];
$resUnit = $conn->query("SELECT id, name FROM unit_projects ORDER BY name ASC");
while ($row = $resUnit->fetch_assoc()) {
    $unitList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Perhitungan AHP</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

<div class="container-fluid">
    <div class="card-header py-3 bg-primary text-white">
        <h4 class="m-0 font-weight-bold">Data KPI</h4>
        <p class="mb-4">Menampilkan Data perhitungan AHP</p>
    </div>

    <!-- Tabel data -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <!-- Filter -->
            <form method="GET" class="mb-3 row">
                <div class="col-md-4">
                    <label for="periode_id">Filter Periode:</label>
                    <select name="periode_id" id="periode_id" class="form-control" onchange="this.form.submit()">
                        <option value="0" <?= $periode_id === 0 ? 'selected' : '' ?>>-- Semua Periode --</option>
                        <?php foreach ($periodeList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $periode_id === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_periode']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="unit_id">Filter Unit / Project:</label>
                    <select name="unit_id" id="unit_id" class="form-control" onchange="this.form.submit()">
                        <option value="0" <?= $unit_id === 0 ? 'selected' : '' ?>>-- Semua Unit / Project --</option>
                        <?php foreach ($unitList as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $unit_id === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Unit / Project</th>
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

                        // Base query
                        $sql = "
                            SELECT 
                                pkahp.id AS penilaian_id, 
                                u.name AS nama_karyawan, 
                                up.name AS nama_unit,
                                pkahp.total_nilai,
                                pp.nama_periode
                            FROM penilaian_kpi_ahp pkahp
                            JOIN karyawans k ON k.id = pkahp.karyawan_id
                            JOIN users u ON u.id = k.user_id
                            JOIN periode_penilaian pp ON pp.id = pkahp.periode_id
                            JOIN unit_projects up ON k.unit_project_id = up.id
                            WHERE 1=1
                        ";

                        $params = [];
                        $types = "";

                        if ($periode_id > 0) {
                            $sql .= " AND pkahp.periode_id = ? ";
                            $params[] = $periode_id;
                            $types .= "i";
                        }
                        if ($unit_id > 0) {
                            $sql .= " AND k.unit_project_id = ? ";
                            $params[] = $unit_id;
                            $types .= "i";
                        }

                        $sql .= " ORDER BY pkahp.total_nilai DESC, pp.id DESC, u.name";

                        $stmt = $conn->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Ambil detail nilai
                        $detailSql = "SELECT penilaian_id, faktor_id, hasil FROM detail_penilaian_ahp";
                        $detailResult = $conn->query($detailSql);

                        $detailData = [];
                        while ($d = $detailResult->fetch_assoc()) {
                            $detailData[$d['penilaian_id']][$d['faktor_id']] = $d['hasil'];
                        }

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_karyawan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_unit']) . "</td>";
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
</div>

<?php include 'layouts/footer.php'; ?>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="../js/demo/datatables-demo.js"></script>

</body>
</html>
