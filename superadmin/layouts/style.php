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

<style>
        /* --- JAMINAN PERBAIKAN STICKY FOOTER --- */
        
        /* 1. Pastikan body dan wrapper utama mengisi tinggi layar */
        html, body {
            height: 100%;
        }
        #wrapper {
            min-height: 100%;
        }
        
        /* 2. Paksa #content-wrapper untuk menjadi flex-column */
        /* (Ini adalah pengganti/penjamin class "d-flex flex-column") */
        #content-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100%; /* Pastikan ia mengisi tinggi #wrapper */
        }
        
        /* 3. INI ATURAN PALING PENTING */
        /* Paksa #content untuk "tumbuh" dan mendorong footer ke bawah */
        #content {
            flex-grow: 1 !important; /* !important untuk memaksa override */
        }
    </style>