<?php
include 'db.php';

$status = isset($_GET['status']) ? strtolower($_GET['status']) : null;

if (!$status || !in_array($status, ['work from office', 'cuti', 'surat tugas'])) {
    header('Location: dashboard.php');
    exit();
}

$today = date('Y-m-d');

// Query data berdasarkan status
if ($status == 'work from office') {
    // Pegawai yang hadir (status presensi = HADIR) hari ini
    $query = "
    SELECT k.nip, k.nama, k.golongan, k.bagian, k.jabatan
    FROM karyawan k
    WHERE k.status_aktif = 1
      AND k.nip NOT IN (
        SELECT dc.nip
        FROM detail_cuti dc
        WHERE dc.tgl_mulai <= ? AND dc.tgl_selesai >= ?
      )
      AND k.nip NOT IN (
        SELECT ds.nip
        FROM detail_st ds
        WHERE ds.tgl_mulai <= ? AND ds.tgl_selesai >= ?
      )
    ORDER BY k.nama
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ssss', $today, $today, $today, $today);



} elseif ($status == 'cuti') {
    // Pegawai yang cuti berdasarkan rentang tanggal di detail_cuti
    $query = "
    SELECT k.nip, k.nama, k.golongan, k.bagian, k.jabatan,
           c.jenis_cuti, dc.tgl_mulai, dc.tgl_selesai, dc.lama_cuti, dc.alasan
    FROM detail_cuti dc
    INNER JOIN karyawan k ON k.nip = dc.nip
    INNER JOIN cuti c ON c.id_cuti = dc.id_cuti
    WHERE k.status_aktif = 1
      AND dc.tgl_mulai <= ? AND dc.tgl_selesai >= ?
    ORDER BY k.nama
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $today, $today);


} else { // surat tugas
    $query = "
    SELECT k.nip, k.nama, k.golongan, k.bagian, k.jabatan,
           s.jenis_st, ds.nomor_st, ds.perihal, ds.lokasi, ds.spd,
           ds.tgl_mulai, ds.tgl_selesai
    FROM detail_st ds
    INNER JOIN karyawan k ON k.nip = ds.nip
    INNER JOIN st s ON s.id_st = ds.id_st
    WHERE k.status_aktif = 1
      AND ds.tgl_mulai <= ? AND ds.tgl_selesai >= ?
    ORDER BY k.nama
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $today, $today);

}

$stmt->execute();
$result = $stmt->get_result();

$title = ucfirst($status) . " Hari Ini";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        h2 { font-size: 1.5rem; }
        @media (max-width: 768px) {
            h2 { font-size: 1.2rem; }
            .table { font-size: 0.85rem; }
            .btn { font-size: 0.9rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mb-4"><?= htmlspecialchars($title); ?></h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Golongan</th>
                        <th>Bagian/Bidang</th>
                        <th>Jabatan</th>
                        <?php if ($status == 'cuti'): ?>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Jenis Cuti</th>
                            <th>Lama Cuti</th>
                            <th>Alasan</th>
                        <?php elseif ($status == 'surat tugas'): ?>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Nomor ST</th>
                            <th>Perihal</th>
                            <th>Lokasi</th>
                            <th>SPD</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['nip']); ?></td>
                            <td><?= htmlspecialchars($row['golongan']); ?></td>
                            <td><?= htmlspecialchars($row['bagian']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan']); ?></td>
                            <?php if ($status == 'cuti'): ?>
                                <td><?= htmlspecialchars($row['tgl_mulai']); ?></td>
                                <td><?= htmlspecialchars($row['tgl_selesai']); ?></td>
                                <td><?= htmlspecialchars($row['jenis_cuti']); ?></td>
                                <td><?= htmlspecialchars($row['lama_cuti']); ?></td>
                                <td><?= htmlspecialchars($row['alasan']); ?></td>
                            <?php elseif ($status == 'surat tugas'): ?>
                                <td><?= htmlspecialchars($row['tgl_mulai']); ?></td>
                                <td><?= htmlspecialchars($row['tgl_selesai']); ?></td>
                                <td><?= htmlspecialchars($row['nomor_st']); ?></td>
                                <td><?= htmlspecialchars($row['perihal']); ?></td>
                                <td><?= htmlspecialchars($row['lokasi']); ?></td>
                                <td><?= htmlspecialchars($row['spd']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            Tidak ada data <?= htmlspecialchars($status); ?> untuk hari ini.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
