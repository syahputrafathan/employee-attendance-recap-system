<?php
include 'db.php';

// ambil filter
$mulai   = isset($_GET['mulai'])   && $_GET['mulai']   !== '' ? $_GET['mulai']   : null;
$selesai = isset($_GET['selesai']) && $_GET['selesai'] !== '' ? $_GET['selesai'] : null;

$sql = "
  SELECT 
    dc.id_dc,
    k.nip,
    k.nama,
    c.jenis_cuti,
    dc.tgl_mulai,
    dc.tgl_selesai,
    dc.lama_cuti,
    dc.stgh_hari,
    dc.alasan
  FROM detail_cuti dc
  JOIN karyawan k ON k.nip = dc.nip
  JOIN cuti c     ON c.id_cuti = dc.id_cuti
  WHERE 1=1
";
$params = [];
$types  = '';

if ($mulai)   { $sql .= " AND dc.tgl_mulai   >= ?"; $params[] = $mulai;   $types .= 's'; }
if ($selesai) { $sql .= " AND dc.tgl_selesai <= ?"; $params[] = $selesai; $types .= 's'; }

$sql .= " ORDER BY dc.tgl_mulai DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
  <h3 class="mb-3">Riwayat Cuti Pegawai</h3>
  <style>
  /* gaya tabel ala versi lama */
  .old-table.table { border-collapse: collapse; }
  .old-table thead th{
    background:#0d6efd;      /* biru bootstrap klasik */
    color:#fff;
    border:1px solid #0b5ed7;
    text-align:center;
    vertical-align:middle;
  }
  .old-table tbody td{
    border:1px solid #dee2e6;
    vertical-align:middle;
  }
  /* zebra striping seperti lama */
  .old-table tbody tr:nth-child(even){ background:#f7f9ff; }
  .old-table tbody tr:hover{ background:#eef5ff; }
  /* ukuran & jarak rapat */
  .old-table th, .old-table td{ padding:.55rem .65rem; font-size:.95rem; }
</style>


  <!-- Filter -->
  <form method="get" class="row g-3 mb-3">
    <div class="col-md-3">
      <label class="form-label">Tanggal Mulai</label>
      <input type="date" name="mulai" class="form-control" value="<?= htmlspecialchars($mulai ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Tanggal Selesai</label>
      <input type="date" name="selesai" class="form-control" value="<?= htmlspecialchars($selesai ?? '') ?>">
    </div>
    <div class="col-md-6 d-flex align-items-end gap-2">
      <button class="btn btn-primary">Filter</button>
      <a href="riwayatcuti.php" class="btn btn-secondary">Reset</a>
      <a class="btn btn-success"
         href="export_cuti.php?mulai=<?= urlencode($mulai ?? '') ?>&selesai=<?= urlencode($selesai ?? '') ?>">
        Ekspor ke Excel
      </a>
    </div>
  </form>

  <!-- Tabel -->
  <div class="table-responsive">
  <table class="table old-table align-middle text-center">
    <thead>
      <tr>
        <th>No</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>Jenis Cuti</th>
        <th>Tanggal Mulai</th>
        <th>Tanggal Selesai</th>
        <th>Lama (Hari)</th>
        <th>½ Hari</th>
        <th>Alasan</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows): $no=1; while($row=$result->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['nip']) ?></td>
          <td><?= htmlspecialchars($row['nama']) ?></td>
          <td><?= htmlspecialchars($row['jenis_cuti']) ?></td>
          <td><?= date('d-m-Y', strtotime($row['tgl_mulai'])) ?></td>
          <td><?= date('d-m-Y', strtotime($row['tgl_selesai'])) ?></td>
          <td><?= (int)$row['lama_cuti'] ?></td>
          <td><?= ((int)$row['stgh_hari'] === 1 ? 'Ya' : 'Tidak') ?></td>
          <td><?= htmlspecialchars($row['alasan']) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="9" class="text-muted">Tidak ada data.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
