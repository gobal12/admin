<style>
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
</style>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="charts.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="sidebar-brand-text mx-3">KPI Nutech</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="charts.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Interface</div>

    <!-- KPI Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseKPI">
            <i class="fas fa-chart-bar"></i>
            <span>KPI</span>
        </a>
        <div id="collapseKPI" class="collapse" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item" href="form-KPI.php">Form Input KPI</a>
                <a class="collapse-item" href="datakpi.php">Data KPI</a>
                <a class="collapse-item" href="dataindikator.php">Data Faktor Kompetensi</a>
                <a class="collapse-item" href="periodepenilaian.php">Data Periode</a>
            </div>
        </div>
    </li>

    <!-- Data Master -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseData">
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

    <!-- Sidebar Toggle (Minimize Button) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" title="Collapse Sidebar"></button>
    </div>
</ul>
