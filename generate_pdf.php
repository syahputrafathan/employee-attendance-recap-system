<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ====== KONFIG DB (sesuaikan) ======
$host = 'localhost';
$dbname = 'schema_normalized';   // <— ganti dari 'presensi'
$username = 'root';
$password = '';

// ====== TANGGAL CETAK (tanpa strftime / deprecated) ======
date_default_timezone_set('Asia/Jakarta');
$bulanID = [
  1=>'Januari','Februari','Maret','April','Mei','Juni',
  'Juli','Agustus','September','Oktober','November','Desember'
];
$now = new DateTime();
$currentDateHuman = $now->format('d') . ' ' . $bulanID[(int)$now->format('n')] . ' ' . $now->format('Y'); // 12 Oktober 2025
$fileName = "Presensi_{$currentDateHuman}.pdf";
$today = $now->format('Y-m-d');

// ====== KONEKSI PDO ======
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error saat koneksi database: " . $e->getMessage());
}

// ====== QUERY SKEMA BARU ======

// CUTI: ambil dari detail_cuti + cuti + karyawan, hari ini berada dalam rentang
$sqlCuti = "
SELECT 
  k.nip AS karyawan_nip,
  k.nama,
  k.golongan,
  k.bagian,
  k.jabatan,
  c.jenis_cuti,
  dc.tgl_mulai,
  dc.tgl_selesai,
  dc.lama_cuti,
  dc.alasan,
  YEAR(dc.tgl_mulai) AS tahun
FROM detail_cuti dc
JOIN karyawan k ON k.nip = dc.nip
JOIN cuti c     ON c.id_cuti = dc.id_cuti
WHERE dc.tgl_mulai <= :today AND dc.tgl_selesai >= :today
ORDER BY k.nama
";
$stmtCuti = $pdo->prepare($sqlCuti);
$stmtCuti->execute([':today' => $today]);

// SURAT TUGAS: ambil dari detail_st + st + karyawan, hari ini berada dalam rentang
$sqlST = "
SELECT
  k.nip AS karyawan_nip,
  k.nama,
  k.golongan,
  k.bagian,
  k.jabatan,
  s.jenis_st,
  ds.nomor_st,
  ds.tgl_st,
  ds.perihal,
  ds.tgl_mulai,
  ds.tgl_selesai,
  ds.lokasi,
  ds.spd
FROM detail_st ds
JOIN karyawan k ON k.nip = ds.nip
JOIN st s       ON s.id_st = ds.id_st
WHERE ds.tgl_mulai <= :today AND ds.tgl_selesai >= :today
ORDER BY k.nama
";
$stmtST = $pdo->prepare($sqlST);
$stmtST->execute([':today' => $today]);

// ====== LOGO KE BASE64 ======
$logoPath = __DIR__ . '/assets/djbc.png';
if (!is_file($logoPath)) {
    die("Error saat membuat PDF: Gambar tidak ditemukan di path: {$logoPath}");
}
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

// ====== BANGUN HTML ======
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
  .header { display: flex; align-items: center; }
  .header img { width: 70px; height: 70px; margin-right: 12px; }
  .title h3 { margin: 0; }
  hr { border: 1px solid #000; margin: 8px 0 12px; }
  h2 { text-align: center; margin: 6px 0 14px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #000; padding: 6px 8px; }
  thead th { background: #e6eef8; }
  .section-title { margin: 14px 0 8px; }
</style>
</head>
<body>

<div class="header">
  <img src="<?= $logoBase64 ?>" alt="Logo DJBC">
  <div class="title">
    <h3>KANTOR WILAYAH</h3>
    <h3>DIREKTORAT JENDERAL BEA DAN CUKAI</h3>
    <h3>SUMATERA BAGIAN TIMUR</h3>
  </div>
</div>
<hr>
<h2>Rekapitulasi Data Cuti dan Surat Tugas (<?= htmlspecialchars($currentDateHuman) ?>)</h2>

<h3 class="section-title">Daftar Karyawan yang Cuti</h3>
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>NIP</th>
      <th>Bagian/Bidang</th>
      <th>Jabatan</th>
      <th>Jenis Cuti</th>
      <th>Tanggal Mulai</th>
      <th>Tanggal Selesai</th>
      <th>Tahun</th>
      <th>Alasan</th>
    </tr>
  </thead>
  <tbody>
  <?php $no=1; foreach ($stmtCuti as $row): ?>
    <tr>
      <td><?= $no++ ?></td>
      <td><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['karyawan_nip']) ?></td>
      <td><?= htmlspecialchars($row['bagian']) ?></td>
      <td><?= htmlspecialchars($row['jabatan']) ?></td>
      <td><?= htmlspecialchars($row['jenis_cuti']) ?></td>
      <td><?= $row['tgl_mulai'] ? (new DateTime($row['tgl_mulai']))->format('d-m-Y') : '-' ?></td>
      <td><?= $row['tgl_selesai'] ? (new DateTime($row['tgl_selesai']))->format('d-m-Y') : '-' ?></td>
      <td><?= htmlspecialchars($row['tahun']) ?></td>
      <td><?= htmlspecialchars($row['alasan']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h3 class="section-title">Daftar Karyawan yang Surat Tugas</h3>
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Nama</th>
      <th>NIP</th>
      <th>Bagian/Bidang</th>
      <th>Jabatan</th>
      <th>Jenis ST</th>
      <th>Nomor ST</th>
      <th>Tanggal ST</th>
      <th>Perihal</th>
      <th>Tanggal Mulai</th>
      <th>Tanggal Selesai</th>
      <th>Lokasi</th>
      <th>SPD</th>
    </tr>
  </thead>
  <tbody>
  <?php $no=1; foreach ($stmtST as $row): ?>
    <tr>
      <td><?= $no++ ?></td>
      <td><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['karyawan_nip']) ?></td>
      <td><?= htmlspecialchars($row['bagian']) ?></td>
      <td><?= htmlspecialchars($row['jabatan']) ?></td>
      <td><?= htmlspecialchars($row['jenis_st']) ?></td>
      <td><?= htmlspecialchars($row['nomor_st']) ?></td>
      <td><?= $row['tgl_st'] ? (new DateTime($row['tgl_st']))->format('d-m-Y') : '-' ?></td>
      <td><?= htmlspecialchars($row['perihal']) ?></td>
      <td><?= $row['tgl_mulai'] ? (new DateTime($row['tgl_mulai']))->format('d-m-Y') : '-' ?></td>
      <td><?= $row['tgl_selesai'] ? (new DateTime($row['tgl_selesai']))->format('d-m-Y') : '-' ?></td>
      <td><?= htmlspecialchars($row['lokasi']) ?></td>
      <td><?= htmlspecialchars($row['spd']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

// ====== RENDER PDF ======
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
echo $dompdf->output();
