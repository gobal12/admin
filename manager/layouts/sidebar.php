    <!-- Sidebar -->
    <?php
    // --- LOGIKA DINAMIS DIMULAI DI SINI ---
    $current_page = basename($_SERVER['SCRIPT_NAME']);

    $laporan_pages = [
        'datakpi.php', 
        'hasil_ahp.php',
        'detailkpi.php',
        'cetak_kpi.php',
        'cetak_all_kpi.php',
        'detail_ahp.php',
        'cetak_ahp.php',
        'cetak_all_ahp.php'
    ];

    $setup_pages = [
        'ahp_result.php', 'addjabatan.php', 'addkaryawan.php', 'addkaryawanexcel.php',
        'addunit_projects.php', 'addperiode.php', 'ahp_input.php', 'ahp_result.php',
        'adduser.php', 'ahp_process.php', 'ahp.php', 'editperiode.php',
        'editkaryawan.php', 'kelola_faktor.php', 'kelola_indikator.php', 'dataindikator.php',
        'periodepenilaian.php', 'datakaryawan.php', 'datajabatan.php', 'dataunit_projects.php','adduser.php'
    ];

    $profile_pages = ['profile.php'];

    $is_laporan_active = in_array($current_page, $laporan_pages);
    $is_setup_active   = in_array($current_page, $setup_pages);
    $is_profile_active = in_array($current_page, $profile_pages);
    ?>

    <!-- Isi Side Bar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        
        <hr class="sidebar-divider my-0">

        <li class="nav-item <?php echo ($current_page == '') ? 'active' : ''; ?>">
            <a class="nav-link" href="#" title="">
                <i class=""></i>
                <span></span>
            </a>
        </li>

        <li class="nav-item <?php echo ($current_page == 'charts.php') ? 'active' : ''; ?>">
            <a class="nav-link" href="charts.php" title="Dashboard">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">
            Penilaian
        </div>

        <li class="nav-item <?php echo ($current_page == 'form-kpi.php') ? 'active' : ''; ?>">
            <a class="nav-link" href="form-kpi.php" title="Input Penilaian Kinerja">
                <i class="fas fa-fw fa-edit"></i>
                <span>Input Penilaian</span>
            </a>
        </li>

        <li class="nav-item <?php echo $is_laporan_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_laporan_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseLaporan" title="Laporan Kinerja">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Laporan Kinerja</span>
            </a>
            <div id="collapseLaporan" class="collapse <?php echo $is_laporan_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Lihat Hasil:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'datakpi.php') ? 'active' : ''; ?>" href="datakpi.php">Hasil (Metode Eksisting)</a>
                    <a class="collapse-item <?php echo ($current_page == 'hasil_ahp.php') ? 'active' : ''; ?>" href="hasil_ahp.php">Hasil (Metode AHP)</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">

        <!-- <div class="sidebar-heading">
            Admin & Setup
        </div>

        <li class="nav-item <?php echo $is_setup_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_setup_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseSetup" title="Setup Sistem">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Setup Sistem</span>
            </a>
            <div id="collapseSetup" class="collapse <?php echo $is_setup_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <h6 class="collapse-header">Setup Metodologi (AHP):</h6>
                    <a class="collapse-item <?php echo ($current_page == 'ahp_result.php') ? 'active' : ''; ?>" href="ahp_result.php">Setup Bobot Kriteria</a>
                    <a class="collapse-item <?php echo ($current_page == 'ahp.php') ? 'active' : ''; ?>" href="ahp.php">Proses Hasil AHP</a>
                    
                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Setup Master KPI:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'dataindikator.php') ? 'active' : ''; ?>" href="dataindikator.php">Faktor & Indikator</a>
                    <a class="collapse-item <?php echo ($current_page == 'periodepenilaian.php') ? 'active' : ''; ?>" href="periodepenilaian.php">Periode Penilaian</a>

                    <div class="collapse-divider"></div>
                    <h6 class="collapse-header">Setup Master Data:</h6>
                    <a class="collapse-item <?php echo ($current_page == 'datakaryawan.php') ? 'active' : ''; ?>" href="datakaryawan.php">Data Karyawan</a>
                    <a class="collapse-item <?php echo ($current_page == 'datajabatan.php') ? 'active' : ''; ?>" href="datajabatan.php">Data Jabatan</a>
                    <a class="collapse-item <?php echo ($current_page == 'dataunit_projects.php') ? 'active' : ''; ?>" href="dataunit_projects.php">Data Unit</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider d-none d-md-block"> -->

        <li class="nav-item <?php echo $is_profile_active ? 'active' : ''; ?>">
            <a class="nav-link <?php echo !$is_profile_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseProfile">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <div id="collapseProfile" class="collapse <?php echo $is_profile_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                <div class="py-2 collapse-inner rounded">
                    <a class="collapse-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">Edit Profile</a>
                    <a class="collapse-item" href="../logout.php">Logout</a>
                </div>
            </div>
        </li>

        <!-- <hr class="sidebar-divider d-none d-md-block">

        <li class="nav-item <?php echo ($current_page == 'adduser.php') ? 'active' : ''; ?>">
            <a class="nav-link" href="adduser.php" title="Adduser">
                <i class="fas fa-user-plus"></i>
                <span>Tambah User</span>
            </a>
        </li> -->

    </ul>
    <!-- End of Sidebar -->