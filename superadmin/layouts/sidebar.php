<?php
// --- LOGIKA DINAMIS DIMULAI DI SINI ---

// 1. Dapatkan nama file halaman saat ini
$current_page = basename($_SERVER['SCRIPT_NAME']);

// 2. Tentukan grup halaman untuk setiap menu dropdown
$kpi_pages   = ['form-kpi.php', 'datakpi.php', 'dataindikator.php', 'periodepenilaian.php'];
$ahp_pages   = ['ahp_result.php', 'ahp.php', 'hasil_ahp.php'];
$data_pages  = ['datakaryawan.php', 'dataunit_projects.php', 'datajabatan.php'];
$profile_pages = ['profile.php'];

// 3. Cek apakah menu induk (parent) harus aktif
$is_kpi_active = in_array($current_page, $kpi_pages);
$is_ahp_active = in_array($current_page, $ahp_pages);
$is_data_active = in_array($current_page, $data_pages);
$is_profile_active = in_array($current_page, $profile_pages);

// --- LOGIKA DINAMIS SELESAI ---
?>

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

/* ============================================
TAMBAHAN: Buat link yang aktif menjadi BOLD
============================================
*/
.sidebar .collapse-inner .collapse-item.active {
    font-weight: bold !important;
}

/* --- Struktur Sidebar --- */
.sidebar {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
}
/* ... (sisa style Anda tetap sama) ... */
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

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="charts.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="sidebar-brand-text mx-3">KPI Nutech</div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item <?php echo ($current_page == 'charts.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="charts.php" title="Dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Interface</div>

    <li class="nav-item <?php echo $is_kpi_active ? 'active' : ''; ?>">
        <a class="nav-link <?php echo !$is_kpi_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseKPI" title="KPI">
            <i class="fas fa-chart-bar"></i>
            <span>KPI</span>
        </a>
        <div id="collapseKPI" class="collapse <?php echo $is_kpi_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'form-kpi.php') ? 'active' : ''; ?>" href="form-kpi.php">Form Input KPI</a>
                <a class="collapse-item <?php echo ($current_page == 'datakpi.php') ? 'active' : ''; ?>" href="datakpi.php">Data KPI</a>
                <a class="collapse-item <?php echo ($current_page == 'dataindikator.php') ? 'active' : ''; ?>" href="dataindikator.php">Data Faktor Kompetensi</a>
                <a class="collapse-item <?php echo ($current_page == 'periodepenilaian.php') ? 'active' : ''; ?>" href="periodepenilaian.php">Data Periode</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $is_ahp_active ? 'active' : ''; ?>">
        <a class="nav-link <?php echo !$is_ahp_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseAHP" title="AHP">
            <i class="fas fa-balance-scale"></i>
            <span>KPI Alternatif</span>
        </a>
        <div id="collapseAHP" class="collapse <?php echo $is_ahp_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'ahp_result.php') ? 'active' : ''; ?>" href="ahp_result.php">Penghitungan Bobot</a>
                <a class="collapse-item <?php echo ($current_page == 'ahp.php') ? 'active' : ''; ?>" href="ahp.php">Menghitung Hasil</a>
                <a class="collapse-item <?php echo ($current_page == 'hasil_ahp.php') ? 'active' : ''; ?>" href="hasil_ahp.php">Hasil Penghitungan</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $is_data_active ? 'active' : ''; ?>">
        <a class="nav-link <?php echo !$is_data_active ? 'collapsed' : ''; ?>" href="#" data-toggle="collapse" data-target="#collapseData" title="Manajemen Data">
            <i class="fas fa-database"></i>
            <span>Manajemen Data</span>
        </a>
        <div id="collapseData" class="collapse <?php echo $is_data_active ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <a class="collapse-item <?php echo ($current_page == 'datakaryawan.php') ? 'active' : ''; ?>" href="datakaryawan.php">Karyawan</a>
                <a class="collapse-item <?php echo ($current_page == 'dataunit_projects.php') ? 'active' : ''; ?>" href="dataunit_projects.php">Unit</a>
                <a class="collapse-item <?php echo ($current_page == 'datajabatan.php') ? 'active' : ''; ?>" href="datajabatan.php">Jabatan</a>
            </div>
        </div>
    </li>

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

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle" title="Collapse Sidebar"></button>
        </div>
    </ul>

