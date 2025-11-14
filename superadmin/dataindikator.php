<?php
session_start();
require_once '../db_connection.php';

// --- DEFINISI FUNGSI CHECK_ROLE ---
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
// --- PANGGIL FUNGSI SETELAH DIDEFINISIKAN ---
check_role('admin');

$logged_in_user = $_SESSION['name'] ?? 'Guest';

// --- LOGIKA PENGAMBILAN DATA BARU (HIERARKIS) ---
$sql_faktor = "SELECT id, nama, bobot_faktor FROM faktor_kompetensi ORDER BY id";
$result_faktor = $conn->query($sql_faktor);
if (!$result_faktor) {
    die("Error executing query faktor: " . $conn->error);
}
$faktors = $result_faktor->fetch_all(MYSQLI_ASSOC);

$sql_indikator = "SELECT id, faktor_id, nama, bobot_indikator FROM indikator_kompetensi ORDER BY faktor_id, id";
$result_indikator = $conn->query($sql_indikator);
if (!$result_indikator) {
    die("Error executing query indikator: " . $conn->error);
}
$indikators_raw = $result_indikator->fetch_all(MYSQLI_ASSOC);

$indikators_by_faktor = [];
foreach ($indikators_raw as $ind) {
    if (!isset($indikators_by_faktor[$ind['faktor_id']])) {
        $indikators_by_faktor[$ind['faktor_id']] = [];
    }
    $indikators_by_faktor[$ind['faktor_id']][] = $ind;
}

// Variabel untuk validasi total
$grand_total_bobot_faktor = 0;
foreach ($faktors as $faktor) {
    $grand_total_bobot_faktor += $faktor['bobot_faktor'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KPI Nutech - Kelola Faktor Kompetensi</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

<?php include 'layouts/page_start.php'; ?>

        <div class="container-fluid">

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message']; ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="m-0 font-weight-bold">Data Faktor & Indikator Kompetensi</h4>
                    <p class="mb-0">Kelola bobot untuk faktor dan indikator KPI</p>
                </div>
                
                <div>
                    <?php if ($grand_total_bobot_faktor == 100.00): ?>
                        <button class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-check"></i> Bobot Faktor Tepat 100%
                        </button>
                    <?php elseif ($grand_total_bobot_faktor > 100.00): ?>
                        <button class="btn btn-danger btn-sm" disabled>
                            <i class="fas fa-exclamation-triangle"></i> Bobot Faktor > 100% (<?= $grand_total_bobot_faktor ?>%)
                        </button>
                    <?php else: ?>
                        <button class="btn btn-warning btn-sm" disabled>
                            <i class="fas fa-exclamation-triangle"></i> Bobot Faktor < 100% (<?= $grand_total_bobot_faktor ?>%)
                        </button>
                    <?php endif; ?>

                    <a href="kelola_faktor.php" class="btn btn-light btn-sm ml-2">
                        <i class="fas fa-edit"></i> Kelola Bobot Faktor
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Faktor Kompetensi</th>
                                <th style="width: 10%;">Bobot Faktor</th>
                                <th>Indikator Kompetensi</th>
                                <th style="width: 10%;">Bobot Indikator</th>
                                <th style="width: 8%;">Target</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            $nomor = 1;
                            if (count($faktors) > 0) :
                                foreach ($faktors as $faktor) :
                                    $current_indikators = $indikators_by_faktor[$faktor['id']] ?? [];
                                    $row_span_count = count($current_indikators) > 0 ? count($current_indikators) : 1;
                                    
                                    $total_bobot_indikator_grup = 0;
                                    foreach($current_indikators as $ind) {
                                        $total_bobot_indikator_grup += $ind['bobot_indikator'];
                                    }
                                    
                                    // Validasi grup
                                    $is_group_valid = ($total_bobot_indikator_grup == $faktor['bobot_faktor']);
                                    $group_class = $is_group_valid ? '' : 'table-danger'; // Tandai jika grup tidak valid

                                    if ($row_span_count > 1) :
                                        foreach ($current_indikators as $index => $ind) : 
                                            $target = ($ind['bobot_indikator'] / 100) * 4.00;
                                        ?>
                                    <tr class="<?= $group_class ?>">
                                        <?php if ($index == 0) : // Baris pertama grup ?>
                                        <td rowspan="<?php echo $row_span_count; ?>"><?php echo $nomor; ?></td>
                                        <td rowspan="<?php echo $row_span_count; ?>">
                                            <strong><?php echo htmlspecialchars($faktor['nama']); ?></strong>
                                        </td>
                                        <td rowspan="<?php echo $row_span_count; ?>" class="text-center">
                                            <strong><?php echo htmlspecialchars($faktor['bobot_faktor']); ?>%</strong>
                                        </td>
                                        <?php endif; ?>

                                        <td><?php echo htmlspecialchars($ind['nama']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($ind['bobot_indikator']); ?>%</td>
                                        <td class="text-center"><?php echo number_format($target, 2); ?></td>
                                    </tr>
                                        <?php endforeach; // End 'foreach indikator'
                                    else : // Jika tidak ada indikator ?>
                                    <tr class="<?= $group_class ?>">
                                        <td><?php echo $nomor; ?></td>
                                        <td><strong><?php echo htmlspecialchars($faktor['nama']); ?></strong></td>
                                        <td class="text-center"><strong><?php echo htmlspecialchars($faktor['bobot_faktor']); ?>%</strong></td>
                                        <td colspan="3" class="text-center font-italic">(Belum ada indikator)</td>
                                    </tr>
                                    <?php endif; // End 'if row_span_count > 0' ?>

                                    <tr class="table-secondary">
                                        <td colspan="4" class="text-right">
                                            <strong>Total Bobot Indikator (Target: <?= $faktor['bobot_faktor'] ?>%)</strong>
                                        </td>
                                        <td class="text-center">
                                            <strong><?php echo $total_bobot_indikator_grup; ?>%</strong>
                                            
                                            <?php if (!$is_group_valid) : ?>
                                                <i class="fas fa-exclamation-triangle text-danger" title="Error! Total bobot indikator (<?= $total_bobot_indikator_grup ?>%) tidak sama dengan bobot faktor (<?= $faktor['bobot_faktor'] ?>%)"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <strong>
                                                <?php
                                                $target_faktor = ($faktor['bobot_faktor'] / 100) * 4.00;
                                                echo number_format($target_faktor, 2);
                                                ?>
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-right">
                                            <a href="kelola_indikator.php?faktor_id=<?php echo $faktor['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Kelola Indikator
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                $nomor++;
                                endforeach; // End 'foreach faktor'
                            else : ?>
                                <tr><td colspan="6" class="text-center">Belum ada data faktor kompetensi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary" style="font-size: 1.1rem;">
                                <td colspan="2" class="text-right"><strong>GRAND TOTAL</strong></td>
                                <td class="text-center">
                                    <strong><?php echo $grand_total_bobot_faktor; ?>%</strong>
                                    
                                    <?php if ($grand_total_bobot_faktor != 100.00) : ?>
                                        <i class="fas fa-exclamation-triangle text-danger" title="Error! Grand Total Bobot Faktor (<?= $grand_total_bobot_faktor ?>%) harus 100%"></i>
                                    <?php endif; ?>
                                </td>
                                <td colspan="2"></td>
                                <td class="text-center"><strong>4.00</strong></td>
                            </tr>
                        </tfoot>
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
    <script>
    // Inisialisasi Tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    </script>
</body>
</html>