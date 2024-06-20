<?php
session_start();

// Misalnya, kita membuat fungsi bernama check_role()
function check_role($required_role) {
    // Cek apakah peran pengguna sesuai dengan peran yang diperlukan
    if ($_SESSION['role'] !== $required_role) {
        // Jika tidak sesuai, arahkan pengguna ke halaman akses ditolak
        header("Location: ../access_denied.php");
        exit();
    }
}

// Periksa akses hanya untuk admin
check_role('admin');

// Fetch the user's first and last names from the session
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

require_once '../db_connection.php';

$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.divisi, u.role_id, r.role_name
        FROM users u 
        INNER JOIN roles r ON u.role_id = r.id";
$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
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

    <title>Port Report Issues - Data User</title>

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

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="charts.php">
                <div class="sidebar-brand-text mx-3">Port Report Issues</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">


            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="charts.php">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Dashboard</span></a>
            </li>

            <!-- Nav Item - Form -->
            <li class="nav-item">
                <a class="nav-link" href="form3.php">
                <i class="fab fa-wpforms"></i>
                <span>Form Report</span></a>
            </li>

            <!-- Nav Item - Data Report -->
            <li class="nav-item">
                    <a class="nav-link" href="datareport.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Data Report</span></a>
             </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">


            <!-- Nav Item - Profile -->
            <li class="nav-item">
                <a class="nav-link" href="datauser.php">
                <i class="fas fa-clipboard-list"></i>
                <span>User</span></a>
            </li>

            <!-- Nav Item - Profile -->
            <li class="nav-item">
            <a class="nav-link" href="user-role.php">
            <i class="fas fa-clipboard-list"></i>
            <span>User Role</span></a>
            </li>

            <!-- Nav Item - Profile -->
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                <i class="fas fa-user-alt"></i>
                <span>Profile</span></a>
            </li>


            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->

                    <!-- Topbar Search -->

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">


                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
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
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">User</h1>
                    <p class="mb-4">Menampilkan list user yang telah terdaftar pada cms Port Report Issues</p>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
    
                    <div class="card-header py-3">
                        <a href="input-user.php" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">ADD USER</a>
                    </div>

                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Nomor</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Divisi</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $nomor = 1; // variabel counter untuk nomor urut

                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $nomor . "</td>"; // menampilkan nomor urut
                                            echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                                            echo "<td>" . $row["email"] . "</td>";
                                            echo "<td>" . $row["divisi"] . "</td>";
                                            echo "<td>" . $row["role_name"] . "</td>";
                                            echo "<td>";
                                            echo "<a href='edit-user.php?id=" . $row["id"] . "' class='btn btn-primary' title='Edit'><i class='fas fa-edit'></i></a>";
                                            echo "<button type='button' class='btn btn-danger' onclick='confirmDelete(" . $row["id"] . ", event)' title='Delete'><i class='far fa-trash-alt'></i></button>";
                                            echo "</td>";
                                            echo "</tr>";
                                            
                                            $nomor++; // increment counter nomor urut
                                        }
                                    } else {
                                        echo "<tr><td colspan='6'>No records found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MI 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
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
    
    <!-- Konfirmasi Delete -->
    <script>
    function confirmDelete(id, event) {
        // Prevent the default action of the link
        event.preventDefault();

        // Show the SweetAlert confirmation popup
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the delete URL
                window.location.href = "delete-user.php?id=" + id;
            }
        });
    }
    </script>
</body>

</html>
