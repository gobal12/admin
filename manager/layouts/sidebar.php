<style>
/* --- Warna dan tampilan submenu --- */
.sidebar .collapse-inner {
    background-color: #4e73df !important;
    color: white;
}

.sidebar .collapse-inner .collapse-item {
    color: white !important;
    background-color: transparent !important;
    font-weight: normal;
    transition: font-weight 0.2s ease-in-out;
}

.sidebar .collapse-inner .collapse-item:hover {
    background-color: transparent !important;
    font-weight: bold;
    color: white !important;
}

/* --- Struktur Sidebar --- */
.sidebar {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
}

.sidebar .nav-item {
    width: 100%;
}

.sidebar .nav-link {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.sidebar .nav-link i {
    font-size: 20px;
    line-height: 1;
}

/* --- Sembunyikan teks saat sidebar ditoggle --- */
@media (min-width: 768px) {
    .sidebar.toggled .nav-link span {
        display: none;
    }
}

/* --- Hilangkan efek hover popout --- */
.sidebar.toggled .collapse {
    display: none !important;
    position: static !important;
    box-shadow: none !important;
    background: none !important;
}

/* Pastikan submenu muncul saat diklik (default Bootstrap) */
.sidebar .collapse.show {
    display: block !important;
}

/* --- Mode kecil (tablet/mobile) --- */
@media (max-width: 1024px) {
    body.sidebar-toggled .sidebar {
        width: 70px !important;
        overflow: visible !important;
    }

    body.sidebar-toggled .sidebar .nav-link {
        text-align: center !important;
        padding: 0.75rem 0 !important;
        position: relative;
    }

    body.sidebar-toggled .sidebar .nav-link i {
        font-size: 1.3rem !important;
        display: block !important;
        margin-bottom: 4px;
    }

    /* Submenu tampil di dalam sidebar, bukan popout */
    body.sidebar-toggled .sidebar .collapse {
        position: static !important;
        background-color: #4e73df !important;
        border-radius: 0 !important;
        padding: 0.5rem;
        display: none;
    }

    /* Saat diklik, baru muncul */
    body.sidebar-toggled .sidebar .collapse.show {
        display: block !important;
    }

    /* Hilangkan teks & heading di mode kecil */
    body.sidebar-toggled .sidebar .sidebar-brand-text,
    body.sidebar-toggled .sidebar .nav-link span,
    body.sidebar-toggled .sidebar-heading {
        display: none !important;
    }

    body.sidebar-toggled #content-wrapper {
        margin-left: 70px !important;
    }
}
</style>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="charts.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="sidebar-brand-text mx-3">KPI Nutech</div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="charts.php" title="Dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Interface</div>

    <!-- KPI Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseKPI" title="KPI">
            <i class="fas fa-chart-bar"></i>
            <span>KPI</span>
        </a>
        <div id="collapseKPI" class="collapse" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item" href="form-kpi.php">Form Input KPI</a>
                <a class="collapse-item" href="datakpi.php">Data KPI</a>
                <a class="collapse-item" href="dataindikator.php">Data Faktor Kompetensi</a>
                <!-- <a class="collapse-item" href="periodepenilaian.php">Data Periode</a> -->
            </div>
        </div>
    </li>

    <!-- AHP Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAHP" title="AHP">
            <i class="fas fa-balance-scale"></i>
            <span>KPI Alternatif</span>
        </a>
        <div id="collapseAHP" class="collapse" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item" href="ahp_result.php">Penghitungan Bobot</a>
                <a class="collapse-item" href="ahp.php">Menghitung Hasil</a>
                <a class="collapse-item" href="hasil_ahp.php">Hasil Penghitungan</a>
            </div>
        </div>
    </li>

    <!-- Data Master -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseData" title="Manajemen Data">
            <i class="fas fa-database"></i>
            <span>Manajemen Data</span>
        </a>
        <div id="collapseData" class="collapse" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item" href="datakaryawan.php">Karyawan</a>
                <a class="collapse-item" href="dataunit_projects.php">Unit</a>
                <a class="collapse-item" href="datajabatan.php">Jabatan</a>
            </div>
        </div>
    </li>

    <!-- Profile -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseProfile">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <div id="collapseProfile" class="collapse" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item" href="profile.php">Edit Profile</a>
                <a class="collapse-item" href="../logout.php">Logout</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" title="Collapse Sidebar"></button>
    </div> -->
</ul>

<script>
    // pastikan toggle hanya ubah class 'toggled'
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
    });
</script>

