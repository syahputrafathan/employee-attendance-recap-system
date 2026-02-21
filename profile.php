<?php
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika pengguna tidak login, redirect ke halaman login
    header('Location: index.php');
    exit;
}

// Hapus sesi dan logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();  // Hapus semua data sesi
    header('Location: index.php');  // Redirect ke halaman login
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include 'layout.php'; ?>  <!-- Menggunakan layout.php untuk navbar -->
    <div class="container mt-5">
        <h1>Profil Pengguna</h1>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Pengaturan</h5>
                <p>Untuk keluar, klik tombol logout di bawah ini.</p>
                <a href="profile.php?logout=true" class="btn btn-danger">Logout</a>
                <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
