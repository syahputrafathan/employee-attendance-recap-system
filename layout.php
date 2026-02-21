<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }
}

$activePage = $activePage ?? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (!function_exists('active')) {
    function active($page) {
        global $activePage;
        return $activePage === $page ? 'active' : '';
    }
}


/* === Deteksi halaman aktif yang stabil === */
$current = basename($_SERVER['SCRIPT_NAME']); // paling akurat

// fallback jika ada routing / query string / rewrite
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$fallback = basename($path);
if ($fallback !== '') $current = $fallback;

// function active($page) {
//     global $current;
//     return $current === $page ? 'active' : '';
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SIKAT'; ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
    body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
    #sidebar{
        position: fixed; top: 0; left: 0;
        width: 250px; height: 100vh; overflow-y: auto;
        background-color: rgba(0, 0, 139, 0.93);
        color: white; padding: 20px;
        z-index: 1050; transition: all 0.3s ease;
    }
    #overlay{
        display: none; position: fixed; top: 0; left: 0;
        width: 100%; height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }
    #overlay.active { display: block; }

    .nav-link{
        display: block; padding: 10px 15px;
        background-color: #0056b3;
        color: white; text-decoration: none;
        border-radius: 5px; text-align: left;
        margin-bottom: 10px;
        transition: background-color 0.3s ease;
    }
    .nav-link:hover { background-color: #004080; }

    /* Highlight menu aktif */
    .nav-link.active{
        background-color: #0b5ed7 !important;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
    }

    .content{
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }
    #sidebarToggle{
        display: none;
        position: absolute; top: 15px; left: 15px;
        font-size: 24px; cursor: pointer;
        z-index: 1051; color: #007bff;
    }
    footer{ margin-top: 20px; text-align: center; }

    @media (max-width: 768px){
        #sidebar{ left: -250px; }
        #sidebar.active{ left: 0; }
        #sidebarToggle{ display: block; }
        .content{ margin-left: 0; }
    }
    </style>
</head>
<body>
    <i id="sidebarToggle" class="bi bi-list"></i>
    <div id="overlay"></div>

    <div>
        <div id="sidebar">
            <div style="display:flex; align-items:center; margin-bottom:20px;">
                <img src="assets/djbc.png" alt="DJBC Logo" style="width:40px; height:40px; margin-right:10px;">
                <h5 class="mb-0" style="line-height:1.0;">SIKAT <br> KANWIL DJBC <br> SUMBAGTIM</h5>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="dashboard.php"
                       class="nav-link text-white text-start mb-2 <?= active('dashboard.php') ?>">
                       <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>

                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item mb-2">
                        <a href="kelola_karyawan.php"
                           class="nav-link text-white text-start mb-2 <?= active('kelola_karyawan.php') ?>">
                           <i class="bi bi-person-workspace"></i> Kelola Pegawai
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="input_presensi.php"
                           class="nav-link text-white text-start mb-2 <?= active('input_presensi.php') ?>">
                           <i class="bi bi-calendar-check"></i> Input Presensi
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="import.php"
                           class="nav-link text-white text-start mb-2 <?= active('import.php') ?>">
                           <i class="fas fa-file-import"></i> Impor Data
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="riwayatcuti.php"
                           class="nav-link text-white text-start mb-2 <?= active('riwayatcuti.php') ?>">
                           <i class="bi bi-clock-history"></i> Riwayat Cuti
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="riwayatst.php"
                           class="nav-link text-white text-start mb-2 <?= active('riwayatst.php') ?>">
                           <i class="bi bi-clock-history"></i> Riwayat Surat Tugas
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="content">
            <div class="container my-4">
                <?php
                if (isset($content) && file_exists($content)) {
                    include $content;
                } else {
                    echo "<div class='alert alert-warning mb-0'>Halaman tidak ditemukan.</div>";
                }
                ?>
            </div>

            <footer class="bg-light py-3">
                <span class="text-muted">&copy; 2024 Sistem Kehadiran Aktif Terpadu</span>
            </footer>
        </div>
    </div>

    <!-- JS -->
    <script>
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');

      sidebarToggle.addEventListener('click', function () {
          sidebar.classList.toggle('active');
          overlay.classList.toggle('active');
      });

      overlay.addEventListener('click', function () {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
      });

    //   Paksa hanya 1 menu yang aktif berdasarkan URL (anti dobel aktif)
    //   document.addEventListener('DOMContentLoaded', function () {
    //       const current = window.location.pathname.split('/').pop();
    //       const links = document.querySelectorAll('#sidebar a.nav-link');

    //       links.forEach(a => a.classList.remove('active'));
    //       links.forEach(a => {
    //           const href = (a.getAttribute('href') || '').split('?')[0];
    //           if (href === current) a.classList.add('active');
    //       });
    //   });
    </script>

    <script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>
