<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Ambil daftar karyawan
$karyawanQuery = $conn->query("
    SELECT nip, nama 
    FROM karyawan 
    WHERE status_aktif = 1
    ORDER BY nama
");

$karyawanList = [];
while ($row = $karyawanQuery->fetch_assoc()) {
    $karyawanList[] = $row;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_karyawan = trim($_POST['nama_karyawan']);
    $status = trim($_POST['status']);
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? null;
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? null;
    $jenis_cuti = $_POST['jenis_cuti'] ?? null;
    $tahun = $_POST['tahun'] ?? null;
    $lama_cuti = $_POST['lama_cuti'] ?? null;
    $cuti_setengah = $_POST['cuti_setengah'] ?? 0;
    $alasan = $_POST['alasan'] ?? null;
    $nomor_st = $_POST['nomor_st'] ?? null;
    $perihal = $_POST['perihal'] ?? null;
    $tanggal_st = $_POST['tanggal_st'] ?? null;
    $lokasi = $_POST['lokasi'] ?? null;
    $spd = $_POST['spd'] ?? null;

    $timestamp = date('Y-m-d H:i:s');

    // Validasi
    if (empty($nama_karyawan)) $errors[] = "Nama karyawan wajib dipilih.";
    if (empty($status)) $errors[] = "Status wajib dipilih.";

    if (empty($errors)) {
        // Ambil NIP dari nama
        $stmt = $conn->prepare("
            SELECT nip 
            FROM karyawan 
            WHERE nama = ? AND status_aktif = 1
        ");
        $stmt->bind_param("s", $nama_karyawan);
        $stmt->execute();
        $nipResult = $stmt->get_result()->fetch_assoc();

        if (!$nipResult) {
            $errors[] = "Karyawan tidak aktif atau tidak ditemukan.";
        }

        if ($nipResult) {
            $nip = $nipResult['nip'];

            // Hapus data presensi hari ini jika ada
            $delete = $conn->prepare("DELETE FROM presensi WHERE nip = ? AND DATE(`timestamp`) = CURDATE()");
            $delete->bind_param("s", $nip);
            $delete->execute();

            // 1️⃣ STATUS: CUTI
            if ($status === 'Cuti') {
                if (empty($tanggal_mulai) || empty($tanggal_selesai) || empty($jenis_cuti)) {
                    $errors[] = "Semua field untuk cuti harus diisi.";
                } else {
                    // Pastikan tabel cuti punya data
                    $cekCuti = $conn->query("SELECT COUNT(*) AS jml FROM cuti")->fetch_assoc();
                    if ($cekCuti['jml'] == 0) {
                        // Isi data default jenis cuti kalau belum ada
                        $conn->query("
                            INSERT INTO cuti (jenis_cuti) VALUES
                            ('Cuti Tahunan'), ('Cuti Bersama'), ('Cuti Tahunan Pengganti Cuti Bersama'),
                            ('Cuti Besar'), ('Cuti Melahirkan'), ('Cuti Alasan Penting'),
                            ('Cuti Sakit'), ('Cuti Tambahan'), ('Cuti di Luar Tanggungan Negara')
                        ");
                    }

                    // Ambil id_cuti berdasarkan jenis_cuti
                    $stmtCuti = $conn->prepare("SELECT id_cuti FROM cuti WHERE jenis_cuti = ?");
                    $stmtCuti->bind_param("s", $jenis_cuti);
                    $stmtCuti->execute();
                    $cutiRow = $stmtCuti->get_result()->fetch_assoc();
                    $id_cuti = $cutiRow ? $cutiRow['id_cuti'] : null;

                    if (!$id_cuti) {
                        $errors[] = "Jenis cuti tidak ditemukan di tabel cuti.";
                    } else {
                        // Insert ke detail_cuti
                        $insertCuti = $conn->prepare("
                            INSERT INTO detail_cuti (nip, id_cuti, alasan, lama_cuti, stgh_hari, tgl_mulai, tgl_selesai)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insertCuti->bind_param("sssssss", $nip, $id_cuti, $alasan, $lama_cuti, $cuti_setengah, $tanggal_mulai, $tanggal_selesai);
                        $insertCuti->execute();

                        // Insert ke presensi
                        $insertPresensi = $conn->prepare("
    INSERT INTO presensi (nip, `timestamp`, status, id_cuti)
    VALUES (?, ?, 'CUTI', ?)
");
$insertPresensi->bind_param("ssi", $nip, $timestamp, $id_cuti);
$insertPresensi->execute();

                    }
                }
            }

            // 2️⃣ STATUS: SURAT TUGAS
// 2️⃣ STATUS: SURAT TUGAS
elseif ($status === 'Surat Tugas') {

    if (empty($tanggal_mulai) || empty($tanggal_selesai) || empty($nomor_st) || empty($perihal)) {
        $errors[] = "Semua field untuk surat tugas harus diisi.";
    } else {

        // 1) Pastikan master ST id=1 ada (jenis_st tidak perlu input)
        $cek = $conn->query("SELECT id_st FROM st WHERE id_st = 1")->fetch_assoc();
        if (!$cek) {
            // buat record master id=1 sekali saja
            $conn->query("INSERT INTO st (id_st, jenis_st) VALUES (1, 'Surat Tugas')");
        }
        $id_st = 1;

        // 2) Insert ke detail_st
        $insertST = $conn->prepare("
            INSERT INTO detail_st (nip, id_st, nomor_st, tgl_st, tgl_mulai, tgl_selesai, perihal, lokasi, spd)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertST->bind_param(
            "sisssssss",
            $nip,
            $id_st,
            $nomor_st,
            $tanggal_st,
            $tanggal_mulai,
            $tanggal_selesai,
            $perihal,
            $lokasi,
            $spd
        );
        $insertST->execute();

        // 3) Insert ke presensi (FK harus pakai $id_st, BUKAN LAST_INSERT_ID)
        $insertPresensi = $conn->prepare("
            INSERT INTO presensi (nip, `timestamp`, status, id_st)
            VALUES (?, ?, 'ST', ?)
        ");
        $insertPresensi->bind_param("ssi", $nip, $timestamp, $id_st);
        $insertPresensi->execute();
    }
}




            // 3️⃣ STATUS: WORK FROM OFFICE
            elseif ($status === 'Work From Office') {
                $insertPresensi = $conn->prepare("
                    INSERT INTO presensi (nip, `timestamp`, status)
                    VALUES (?, ?, 'HADIR')
                ");
                $insertPresensi->bind_param("ss", $nip, $timestamp);
                $insertPresensi->execute();
            }

            // Status tidak valid
            else {
                $errors[] = "Status tidak valid.";
            }

            // Kalau tidak ada error, kembali ke dashboard
            if (empty($errors)) {
                header('Location: dashboard.php');
                exit();
            }
        } else {
            $errors[] = "Karyawan tidak ditemukan.";
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Input Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function toggleTanggalFields() {
            const status = document.getElementById('status').value;
            const dateFields = document.getElementById('tanggalFields');
            const cutiFields = document.getElementById('cutiFields');
            const tugasFields = document.getElementById('tugasFields');

            dateFields.style.display = (status === 'Cuti' || status === 'Surat Tugas') ? 'block' : 'none';
            cutiFields.style.display = (status === 'Cuti') ? 'block' : 'none';
            tugasFields.style.display = (status === 'Surat Tugas') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Input Presensi</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="row g-3">
            <div class="col-12">
                <label for="nama_karyawan" class="form-label">Nama Karyawan</label>
                <select name="nama_karyawan" id="nama_karyawan" class="form-select" required>
                    <option value="">-- Pilih Nama Karyawan --</option>
                    <?php foreach ($karyawanList as $karyawan): ?>
                        <option value="<?= htmlspecialchars($karyawan['nama']); ?>">
                            <?= htmlspecialchars($karyawan['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" onchange="toggleTanggalFields()" required>
                    <option value="">-- Pilih Status --</option>
                    <!-- <option value="Work From Office">Work From Office</option> -->
                    <option value="Cuti">Cuti</option>
                    <option value="Surat Tugas">Surat Tugas</option>
                </select>
            </div>
            <div id="tanggalFields" class="col-12" style="display:none;">
                <div class="mb-3">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>
            </div>
            <div id="cutiFields" class="col-12" style="display:none;">
                <div class="mb-3">
                    <label for="jenis_cuti" class="form-label">Jenis Cuti</label>
                    <select name="jenis_cuti" class="form-select">
                        <option value="">-- Pilih Jenis Cuti --</option>
                        <option value="Cuti Tahunan">Cuti Tahunan</option>
                        <option value="Cuti Bersama">Cuti Bersama</option>
                        <option value="Cuti Tahunan Pengganti Cuti Bersama">Cuti Tahunan Pengganti Cuti Bersama</option>
                        <option value="Cuti Besar">Cuti Besar</option>
                        <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                        <option value="Cuti Alasan Penting">Cuti Alasan Penting</option>
                        <option value="Cuti Sakit">Cuti Sakit</option>
                        <option value="Cuti Tambahan">Cuti Tambahan</option>
                        <option value="Cuti di Luar Tanggungan Negara">Cuti di Luar Tanggungan Negara</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tahun" class="form-label">Tahun Cuti</label>
                    <input type="text" name="tahun" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="lama_cuti" class="form-label">Lama Cuti (Hari)</label>
                    <input type="text" name="lama_cuti" class="form-control">
                </div>
                <div class="mb-3">
  <label for="cuti_setengah" class="form-label">Cuti Setengah Hari</label>
  <select name="cuti_setengah" class="form-select">
      <option value="0">Tidak</option>
      <option value="1">Ya (½ Hari)</option>
  </select>
</div>

                <div class="mb-3">
                    <label for="alasan" class="form-label">Alasan</label>
                    <input type="text" name="alasan" class="form-control">
                </div>
            </div>
            <div id="tugasFields" class="col-12" style="display:none;">
                <div class="mb-3">
                    <label for="nomor_st" class="form-label">Nomor ST</label>
                    <input type="text" name="nomor_st" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="tanggal_st" class="form-label">Tanggal ST</label>
                    <input type="date" name="tanggal_st" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal</label>
                    <input type="text" name="perihal" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="lokasi" class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="spd" class="form-label">SPD/Tidak</label>
                    <input type="text" name="spd" class="form-control">
                </div>
            </div>
            <div class="col-12 d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</body>
</html>
