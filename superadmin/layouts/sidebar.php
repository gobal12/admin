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
    'periodepenilaian.php', 'datakaryawan.php', 'datajabatan.php', 'dataunit_projects.php'
];

$profile_pages = ['profile.php'];

$is_laporan_active = in_array($current_page, $laporan_pages);
$is_setup_active   = in_array($current_page, $setup_pages);
$is_profile_active = in_array($current_page, $profile_pages);
// --- LOGIKA DINAMIS SELESAI ---
?>

<style>
/* 1. Buat Sidebar "nempel" (fixed) di kiri */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0; /* Membentang setinggi layar */
    z-index: 1001; /* Di atas topbar */
    
    overflow-y: auto; /* Scroll HANYA jika menu sidebar panjang */
    overflow-x: hidden;
    
    /* Tentukan lebar normal di sini */
    width: 14rem; /* 224px */
    transition: width 0.3s ease;
}

/* 2. SAAT DI-MINIMIZE (TOGGLED), HILANGKAN TOTAL */
.sidebar.toggled {
    width: 0 !important;
    overflow: hidden; /* Sembunyikan konten saat mengecil */
}
/* CSS default template akan otomatis menyembunyikan .nav-link span, dll. */


/* 3. Atur Content Wrapper agar tidak tertutup sidebar */
#content-wrapper {
    margin-left: 14rem; /* Lebar sidebar normal */
    transition: margin-left 0.3s ease;
}
/* Saat sidebar hilang */
.sidebar.toggled ~ #content-wrapper {
    margin-left: 0; /* Content wrapper jadi full-width */
}

/* 4. Buat Topbar "nempel" (fixed) di atas */
/* (Ini mengasumsikan topbar.php ada di dalam #content) */
#content .topbar {
    position: fixed;
    top: 0;
    left: 14rem; /* 224px (mengikuti content wrapper) */
    right: 0;
    z-index: 1000;
    transition: left 0.3s ease; 
}
/* Saat sidebar hilang */
.sidebar.toggled ~ #content-wrapper #content .topbar {
    left: 0; /* Topbar jadi full-width */
}

/* 5. Beri padding di atas konten utama */
#content {
    padding-top: 4.375rem; /* 70px (tinggi topbar) */
}

/* ================================================================= */

/* --- Style Submenu (Tidak Berubah) --- */
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
.sidebar .collapse-inner .collapse-item.active {
    font-weight: bold !important;
}

/* --- Style Sidebar Normal (Expanded) (Tidak Berubah) --- */
.sidebar .nav-link {
    padding-left: 1rem; /* Teks rata kiri */
    justify-content: flex-start;
    height: auto;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}
.sidebar .nav-link i {
    font-size: 1.1rem;
    margin-right: 0.5rem; /* Jarak ikon ke teks */
}

/* --- Perilaku Submenu (Tidak Berubah) --- */
.sidebar:not(.toggled) .collapse.show {
    display: block !important;
}
@media (max-width: 767px) {
    .sidebar .collapse {
        position: static !important;
        background-color: #4e73df !important;
    }
    .sidebar .collapse.show {
        display: block !important;
    }
}
</style>

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

    <div class="sidebar-heading">
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

    <hr class="sidebar-divider d-none d-md-block">

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

</ul>