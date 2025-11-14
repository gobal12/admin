<nav class="navbar navbar-expand navbar-light bg-white topbar shadow">

    <button id="sidebarToggle" class="btn btn-link d-none d-md-inline-block rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <a class="d-flex align-items-center" 
       href="charts.php" 
       style="text-decoration: none; margin-right: 1rem;">
        
        <div class="sidebar-brand-icon"> <img src="../img/Logo-Nutech-ok.png" alt="Nutech Logo" style="height: 32px; width: auto;">
        </div>
        <div class="sidebar-brand-text mx-2 text-gray-800 font-weight-bold d-none d-sm-inline-block">KPI Nutech</div>
    </a>


    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?= htmlspecialchars($logged_in_user); ?>
                </span>
                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
                </a>
            </div>
        </li>
    </ul>
</nav>