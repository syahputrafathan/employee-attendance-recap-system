<?php
include 'db.php';

$queryWFO = "
  SELECT COUNT(*) AS jumlah
  FROM karyawan k
  WHERE k.status_aktif = 1
    AND k.nip NOT IN (
      SELECT dc.nip
      FROM detail_cuti dc
      WHERE dc.tgl_mulai <= CURDATE()
        AND dc.tgl_selesai >= CURDATE()
    )
    AND k.nip NOT IN (
      SELECT ds.nip
      FROM detail_st ds
      WHERE ds.tgl_mulai <= CURDATE()
        AND ds.tgl_selesai >= CURDATE()
    )
";


$queryCuti = "
  SELECT COUNT(DISTINCT dc.nip) AS jumlah
  FROM detail_cuti dc
  JOIN karyawan k ON k.nip = dc.nip
  WHERE k.status_aktif = 1
    AND dc.tgl_mulai <= CURDATE()
    AND dc.tgl_selesai >= CURDATE()
";

$queryTugas = "
  SELECT COUNT(DISTINCT ds.nip) AS jumlah
  FROM detail_st ds
  JOIN karyawan k ON k.nip = ds.nip
  WHERE k.status_aktif = 1
    AND ds.tgl_mulai <= CURDATE()
    AND ds.tgl_selesai >= CURDATE()
";


$jumlahWFO   = (int)$conn->query($queryWFO)->fetch_assoc()['jumlah'];
$jumlahCuti  = (int)$conn->query($queryCuti)->fetch_assoc()['jumlah'];
$jumlahTugas = (int)$conn->query($queryTugas)->fetch_assoc()['jumlah'];

$tanggal_hari_ini = date('d F Y');
$status = ['Work From Office', 'Cuti', 'Surat Tugas'];
$jumlah_status = [$jumlahWFO, $jumlahCuti, $jumlahTugas];

$status_json = json_encode($status);
$jumlah_status_json = json_encode($jumlah_status);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Tambahkan Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
        .btn-space {
            margin-bottom: 10px; /* Tambahkan jarak antar tombol */
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <span class="navbar-brand">Selamat Datang, <?= htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="dropdown">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                        </a>
                    </div>
                </div>
            </nav>
<div class="container mt-4">
    <!-- Tampilkan Jumlah Presensi -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <a href="detail_presensi.php?status=work from office" class="text-decoration-none">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Work From Office</h5>
                        <p class="card-text h4"><?= $jumlahWFO; ?> Orang</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="detail_presensi.php?status=cuti" class="text-decoration-none">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-success">Cuti</h5>
                        <p class="card-text h4"><?= $jumlahCuti; ?> Orang</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="detail_presensi.php?status=surat tugas" class="text-decoration-none">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Surat Tugas</h5>
                        <p class="card-text h4"><?= $jumlahTugas; ?> Orang</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- <div class="col-md-3 mb-3">
            <a href="detail_presensi.php?status=klc" class="text-decoration-none">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-warning">KLC</h5>
                        <p class="card-text h4"><?= $jumlahTugas; ?> Orang</p>
                    </div>
                </div>
            </a>
        </div> -->
    </div>

    <!-- Tampilkan Grafik Kehadiran -->
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            Grafik Kehadiran Pegawai
        </div>
        <div class="card-body">
        <canvas id="grafikKehadiran" style="max-width: 300px; max-height: 300px; margin: auto;"></canvas>
        
    </div>
</div>
<?php if ($_SESSION['role'] == 'admin'): ?>
<div class="container mt-4">
    <!-- Menggunakan row untuk membuat tombol sejajar secara horizontal -->
    <div class="row justify-content-center">
        <div class="col-auto">
            <a href="generate_pdf.php" target="_blank" class="btn btn-danger btn-space">
                <i class="bi bi-file-earmark-text me-2"></i>Export to PDF
            </a>
        </div>
    </div>
</div>
<?php endif; ?>


    <!-- Informasi Layanan -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            Informasi Layanan
        </div>
        <div class="card-body">
            <p>Status: <span class="text-success fw-bold"><?= $_SESSION['role'] == 'admin' ? 'Admin' : 'User'; ?></span></p>
            <p>Data presensi di atas bersifat real-time berdasarkan input hari ini, <?= $tanggal_hari_ini; ?>.</p>
        </div>
    </div>
</div>

<!-- Script untuk Grafik -->
<script>
    // Ambil data dari PHP
    const labels = <?= $status_json; ?>;
    const data = <?= $jumlah_status_json; ?>;

    // Inisialisasi Chart.js
    const ctx = document.getElementById('grafikKehadiran').getContext('2d');
    const grafikKehadiran = new Chart(ctx, {
        type: 'pie', // Jenis grafik (bar chart)
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Kehadiran',
                data: data,
                // backgroundColor: ['#0d6efd', '#198754', '#ffc107'], // Warna grafik
                backgroundColor: ['#0d6efd', '#28a745', '#f39c12'], // Warna grafik 
                borderColor: ['#4a90e2', '#146c43', '#ffca2c'], // Warna border
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom', // Posisi legenda di bawah grafik
                }
            }
        }
    });
</script>
</body>
</html>
