<!-- Page Wrapper -->
<div id="wrapper">

    <?php include 'sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <?php include 'topbar.php'; ?>

<script>
function adjustSidebar() {
    if (window.innerWidth <= 1024) {
        document.body.classList.add("sidebar-toggled");
    } else {
        document.body.classList.remove("sidebar-toggled");
    }
}

// Jalankan pertama kali
adjustSidebar();

// Jalankan setiap resize
window.addEventListener("resize", adjustSidebar);
</script>


