<?php
include 'db.php';

// filter tanggal (opsional)
$mulai   = $_GET['mulai']   ?? '';
$selesai = $_GET['selesai'] ?? '';

// query sesuai skema normalized: detail_st + st + karyawan
$sql = "
  SELECT 
    ds.id_dst,
    k.nip,
    k.nama,
    s.jenis_st,
    ds.nomor_st,
    ds.tgl_st,
    ds.tgl_mulai,
    ds.tgl_selesai,
    ds.lokasi,
    ds.perihal,
    ds.spd
  FROM detail_st ds
  JOIN karyawan k ON k.nip = ds.nip
  JOIN st s       ON s.id_st = ds.id_st
  WHERE 1=1
";
$params = [];
$types  = '';

if ($mulai !== '')   { $sql .= " AND ds.tgl_mulai >= ?";   $params[] = $mulai;   $types .= 's'; }
if ($selesai !== '') { $sql .= " AND ds.tgl_selesai <= ?"; $params[] = $selesai; $types .= 's'; }

$sql .= " ORDER BY ds.tgl_mulai DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
?>

<h3 class="fw-bold mb-3">Riwayat Surat Tugas Pegawai</h3>

<form method="GET" class="row g-3 mb-3">
  <div class="col-md-3">
    <label class="form-label">Tanggal Mulai</label>
    <input type="date" name="mulai" class="form-control" value="<?= htmlspecialchars($mulai) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Tanggal Selesai</label>
    <input type="date" name="selesai" class="form-control" value="<?= htmlspecialchars($selesai) ?>">
  </div>
  <div class="col-md-6 d-flex align-items-end gap-2">
    <button class="btn btn-primary">Filter</button>
    <a href="riwayatst.php" class="btn btn-secondary">Reset</a>
    <!-- tombol ekspor; siapkan export_st.php bila belum ada -->
    <a class="btn btn-success"
       href="export_st.php?mulai=<?= urlencode($mulai) ?>&selesai=<?= urlencode($selesai) ?>">
       Ekspor ke Excel
    </a>
  </div>
</form>

<style>
  /* gaya tabel ala versi lama */
  .old-table.table { border-collapse: collapse; }
  .old-table thead th{
    background:#0d6efd; color:#fff; border:1px solid #0b5ed7;
    text-align:center; vertical-align:middle;
  }
  .old-table tbody td{
    border:1px solid #dee2e6; vertical-align:middle;
  }
  .old-table tbody tr:nth-child(even){ background:#f7f9ff; }
  .old-table tbody tr:hover{ background:#eef5ff; }
  .old-table th, .old-table td{ padding:.55rem .65rem; font-size:.95rem; }
</style>

<div class="table-responsive">
  <table class="table old-table text-center align-middle">
    <thead>
      <tr>
        <th>No</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>Jenis ST</th>
        <th>Nomor ST</th>
        <th>Tanggal ST</th>
        <th>Tanggal Mulai</th>
        <th>Tanggal Selesai</th>
        <th>Lokasi</th>
        <th>Perihal</th>
        <th>SPD</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows): $no=1; while($row=$result->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($row['nip']) ?></td>
          <td><?= htmlspecialchars($row['nama']) ?></td>
          <td><?= htmlspecialchars($row['jenis_st']) ?></td>
          <td><?= htmlspecialchars($row['nomor_st']) ?></td>
          <td><?= $row['tgl_st'] ? date('d-m-Y', strtotime($row['tgl_st'])) : '-' ?></td>
          <td><?= date('d-m-Y', strtotime($row['tgl_mulai'])) ?></td>
          <td><?= date('d-m-Y', strtotime($row['tgl_selesai'])) ?></td>
          <td><?= htmlspecialchars($row['lokasi']) ?></td>
          <td><?= htmlspecialchars($row['perihal']) ?></td>
          <td><?= htmlspecialchars($row['spd']) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="11" class="text-muted">Tidak ada data.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
