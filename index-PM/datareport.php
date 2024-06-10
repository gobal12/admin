<?php
session_start();
require_once '../db_connection.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Function to check user role
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}

// Check access for admin
check_role('manager');

// Fetch the user's first and last names from the session
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    export_to_excel($conn);
}

function export_to_excel($conn) {
    $where = [];
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start_date = $_GET['start_date'];
        $where[] = "tanggal_open >= '$start_date'";
    }

    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $end_date = $_GET['end_date'];
        $where[] = "tanggal_close <= '$end_date'";
    }

    if (isset($_GET['pelabuhan']) && !empty($_GET['pelabuhan'])) {
        $pelabuhan = $_GET['pelabuhan'];
        $where[] = "pelabuhan LIKE '%$pelabuhan%'";
    }

    if (isset($_GET['tanggal_open']) && !empty($_GET['tanggal_open'])) {
        $tanggal_open = $_GET['tanggal_open'];
        $where[] = "DATE(tanggal_open) = '$tanggal_open'";
    }

    $where_clause = '';
    if (!empty($where)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "SELECT * FROM report $where_clause";
    $result = $conn->query($sql);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report Data');

    $header = ["Nomor", "Nama Pelabuhan", "Nomor Tiket", "Tanggal Open", "Tanggal Close", "Downtime (Minutes)", "Jenis Perangkat", "Lokasi Perangkat", "Layanan Terdampak", "Keterangan"];
    $sheet->fromArray($header, NULL, 'A1');

    if ($result->num_rows > 0) {
        $rowIndex = 2;
        while($row = $result->fetch_assoc()) {
            $tanggalOpen = new DateTime($row["tanggal_open"]);
            $tanggalClose = new DateTime($row["tanggal_close"]);
            $downtime = $tanggalOpen->diff($tanggalClose);
            $downtimeMinutes = ($downtime->days * 24 * 60) + ($downtime->h * 60) + $downtime->i;

            $sheet->fromArray([
                $row["id_report"],
                $row["pelabuhan"],
                $row["nomor_tiket"],
                $row["tanggal_open"],
                $row["tanggal_close"],
                $downtimeMinutes,
                $row["jenis_perangkat"],
                $row["lokasi_perangkat"],
                $row["layanan_terdampak"],
                $row["keterangan"]
            ], NULL, 'A' . $rowIndex);

            $rowIndex++;
        }
    }

    $writer = new Xlsx($spreadsheet);
    $filename = 'data_report.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
    $writer->save('php://output');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Port Report Issues - Data Report</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="charts.php">
                <div class="sidebar-brand-text mx-3"> <b>Port Report Issues</b></div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="charts.php">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="datareport.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Report</span>
                </a>
            </li>

            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="datauser.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span>User</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user-alt"></i>
                    <span>Profile</span>
                </a>
            </li>
            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Data Report</h1>
                    <p class="mb-4">Data Report menampilkan list report yang telah anda submit.</p>

                    <form method="GET" action="">
                        <div class="form-row align-items-center">
                            <div class="col-sm-2">
                                <label for="pelabuhan" class="col-form-label">Pelabuhan</label>
                            </div>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="pelabuhan" name="pelabuhan" value="<?php echo isset($_GET['pelabuhan']) ? $_GET['pelabuhan'] : ''; ?>">
                            </div>
                            <div class="col-sm-2">
                                <label for="tanggal_open" class="col-form-label">Tanggal Open</label>
                            </div>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" id="tanggal_open" name="tanggal_open" value="<?php echo isset($_GET['tanggal_open']) ? $_GET['tanggal_open'] : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group row mt-3">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="datareport.php?export=excel&start_date=<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>&end_date=<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>&pelabuhan=<?php echo isset($_GET['pelabuhan']) ? $_GET['pelabuhan'] : ''; ?>&tanggal_open=<?php echo isset($_GET['tanggal_open']) ? $_GET['tanggal_open'] : ''; ?>" class="btn btn-success">Export to Excel</a>
                            </div>
                        </div>
                    </form>


                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Report</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Nomor</th>
                                            <th>Nama Pelabuhan</th>
                                            <th>Nomor Tiket</th>
                                            <th>Tanggal Open</th>
                                            <th>Tanggal Close</th>
                                            <th>Downtime (Minutes)</th>
                                            <th>Jenis Perangkat</th>
                                            <th>Lokasi Perangkat</th>
                                            <th>Layanan Terdampak</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Filter date
                                        $where = [];
                                        if (isset($_GET['pelabuhan']) && !empty($_GET['pelabuhan'])) {
                                            $pelabuhan = $_GET['pelabuhan'];
                                            $where[] = "pelabuhan LIKE '%$pelabuhan%'";
                                        }

                                        if (isset($_GET['tanggal_open']) && !empty($_GET['tanggal_open'])) {
                                            $tanggal_open = $_GET['tanggal_open'];
                                            $where[] = "DATE(tanggal_open) = '$tanggal_open'";
                                        }

                                        $where_clause = '';
                                        if (!empty($where)) {
                                            $where_clause = 'WHERE ' . implode(' AND ', $where);
                                        }

                                        $sql = "SELECT * FROM report $where_clause";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                $tanggalOpen = new DateTime($row["tanggal_open"]);
                                                $tanggalClose = new DateTime($row["tanggal_close"]);
                                                $downtime = $tanggalOpen->diff($tanggalClose);
                                                $downtimeMinutes = ($downtime->days * 24 * 60) + ($downtime->h * 60) + $downtime->i;

                                                echo "<tr>
                                                        <td>" . $row["id_report"]. "</td>
                                                        <td>" . $row["pelabuhan"]. "</td>
                                                        <td>" . $row["nomor_tiket"]. "</td>
                                                        <td>" . $row["tanggal_open"]. "</td>
                                                        <td>" . $row["tanggal_close"]. "</td>
                                                        <td>" . $downtimeMinutes. "</td>
                                                        <td>" . $row["jenis_perangkat"]. "</td>
                                                        <td>" . $row["lokasi_perangkat"]. "</td>
                                                        <td>" . $row["layanan_terdampak"]. "</td>
                                                        <td>" . $row["keterangan"]. "</td>
                                                    </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='10'>No results found</td></tr>";
                                        }
                                        $conn->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MI 2024</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../js/demo/datatables-demo.js"></script>
</body>
</html>
