<?php
session_start();
echo "Peran Pengguna: ".$_SESSION['role'];

// Misalnya, kita membuat fungsi bernama check_role()
function check_role($required_role) {
    // Cek apakah peran pengguna sesuai dengan peran yang diperlukan
    if ($_SESSION['role'] !== $required_role) {
        // Jika tidak sesuai, arahkan pengguna ke halaman akses ditolak
        header("Location: ../access_denied.html");
        exit();
    }
}

// Periksa akses hanya untuk admin
check_role('manager');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Port Report Issues - Form Petugas</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="form3.php">
                <div class="sidebar-brand-text mx-3"><b>Port Report Issues</b></div>
            </a>
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
                <a class="nav-link" href="profile.php">
                <i class="fas fa-user-alt"></i>
                <span>Profile</span></a>
            </li>


            <hr class="sidebar-divider d-none d-md-block">
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Form Report</h1>
                    <p class="mb-4"> Pastikan anda menginputkan data dan waktu kejadian dengan benar </p>
                    <div class="card">
                        <div class="card-body">
                            <form id="reportForm">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="pelabuhan">Pelabuhan</label>
                                        <input type="text" class="form-control" id="pelabuhan" name="pelabuhan" placeholder="Pelabuhan">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="inputnomortiket">Nomor Tiket</label>
                                        <input type="text" class="form-control" id="inputnomortiket" name="inputnomortiket" placeholder="Nomor Tiket">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="waktuopentiket">Date Open Ticket</label>
                                        <input type="datetime-local" class="form-control" id="waktuopentiket" name="waktuopentiket">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="waktuclosetiket">Date Close Ticket</label>
                                        <input type="datetime-local" class="form-control" id="waktuclosetiket" name="waktuclosetiket">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="jenisperangkat">Jenis Perangkat</label>
                                        <select id="jenisperangkat" name="jenisperangkat" class="form-control" onchange="loadLocations(this.value)">
                                            <option selected>Choose...</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="lokasiperangkat">Lokasi Perangkat</label>
                                        <select id="lokasiperangkat" name="lokasiperangkat" class="form-control">
                                            <option selected>Choose...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Layanan Terdampak:</label>
                                    <div id="layananCheckboxes"></div>
                                </div>

                                <div class="form-group">
                                    <label for="keterangan">Keterangan</label>
                                    <textarea class="form-control" rows="5" id="keterangan" name="keterangan"></textarea>
                                 </div>


                                <button type="submit" class="btn btn-success">Submit</button>
                                <button type="button" class="btn btn-danger">Cancel</button>
                            </form>
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
    <script>
        window.onload = function() {
            loadDeviceTypes();
            loadLayananCheckboxes();
        };
    
        function loadDeviceTypes() {
            fetch('get_device_types.php')
                .then(response => response.json())
                .then(data => {
                    const deviceDropdown = document.getElementById('jenisperangkat');
                    data.forEach(device => {
                        const option = document.createElement('option');
                        option.value = device.id_perangkat;
                        option.textContent = device.nama_perangkat;
                        deviceDropdown.appendChild(option);
                    });
                });
        }
    
        function loadLocations(deviceTypeId) {
            const lokasiPerangkatDropdown = document.getElementById('lokasiperangkat');
            lokasiPerangkatDropdown.innerHTML = '<option selected>Choose...</option>';
    
            fetch(`get_locations.php?device_type_id=${deviceTypeId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.id;
                        option.textContent = location.name;
                        lokasiPerangkatDropdown.appendChild(option);
                    });
                });
        }
    
        function loadLayananCheckboxes() {
            fetch('get_layanan.php')
                .then(response => response.json())
                .then(data => {
                    const layananCheckboxes = document.getElementById('layananCheckboxes');
                    data.forEach(layanan => {
                        const checkbox = document.createElement('div');
                        checkbox.className = 'form-check';
                        checkbox.innerHTML = `
                            <input class="form-check-input" type="checkbox" value="${layanan.id_layanan}" data-name="${layanan.nama_layanan}" id="layanan${layanan.id_layanan}" name="layanan[]">
                            <label class="form-check-label" for="layanan${layanan.id_layanan}">
                                ${layanan.nama_layanan}
                            </label>
                        `;
                        layananCheckboxes.appendChild(checkbox);
                    });
                });
        }
    
            document.getElementById('reportForm').addEventListener('submit', function(event) {
            event.preventDefault();
            submitForm();
        });

        function submitForm() {
        const formData = new FormData(document.getElementById('reportForm'));
        const layananTerdampak = [];
        document.querySelectorAll('#layananCheckboxes input:checked').forEach(checkbox => {
            layananTerdampak.push(checkbox.getAttribute('data-name'));
        });

        const selectedDeviceType = document.querySelector('#jenisperangkat option:checked').textContent;
        const selectedLocation = document.querySelector('#lokasiperangkat option:checked').textContent;

        const data = {
            nomor_tiket: formData.get('inputnomortiket'),
            tanggal_open: formData.get('waktuopentiket'),
            tanggal_close: formData.get('waktuclosetiket'),
            pelabuhan: formData.get('pelabuhan'),
            jenis_perangkat: selectedDeviceType,
            lokasi_perangkat: selectedLocation,
            layanan_terdampak: layananTerdampak.join(', '),
            keterangan: formData.get('keterangan') || 'tidak ada keterangan'
        };

        console.log('Submitting data:', data);

        fetch('submit_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire(
                    'Success',
                    'Report submitted successfully',
                    'success'
                ).then(() => {
                    window.location.href = 'form3.php';
                });
            } else {
                Swal.fire(
                    'Failed',
                    'Failed to submit report: ' + (data.message || 'Unknown error'),
                    'error'
                );
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            Swal.fire(
                'Error',
                'Failed to submit report: ' + error.message,
                'error'
            );
        });
    }

        
    </script>
    
</body>
</html>
