<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();  // Hanya panggil session_start() jika sesi belum dimulai
}
include 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Hapus Data Karyawan
if (isset($_GET['hapus'])) {
    $nip = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "UPDATE karyawan SET status_aktif = 0 WHERE nip = '$nip'");
    echo "<script>alert('Pegawai berhasil dihapus.'); window.location='kelola_karyawan.php';</script>";
    exit();
}

ob_end_flush();

// Tambah Data Karyawan
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $golongan = $_POST['golongan'];
    $jabatan = $_POST['jabatan'];
    $bagian = $_POST['bagian'];

    // Cek apakah NIP sudah ada di database
    $cekNipQuery = "SELECT * FROM karyawan WHERE nip = '$nip'";
    $result = mysqli_query($conn, $cekNipQuery);

    if (mysqli_num_rows($result) > 0) {
        $error = "NIP sudah terdaftar. Silakan gunakan NIP lain.";
    } else {
        $query = "INSERT INTO karyawan (nama, nip, golongan, jabatan, bagian) 
                  VALUES ('$nama', '$nip', '$golongan', '$jabatan', '$bagian')";
        mysqli_query($conn, $query);
        header('Location: kelola_karyawan.php');
        exit();
    }
}

// Proses Edit Data Karyawan
if (isset($_POST['edit'])) {
    $nip = $_POST['nip_edit'];
    $nama = $_POST['nama_edit'];
    $golongan = $_POST['golongan_edit'];
    $bagian = $_POST['bagian_edit'];
    $jabatan = $_POST['jabatan_edit'];

    $query = "UPDATE karyawan SET nama='$nama', golongan='$golongan', bagian='$bagian', jabatan='$jabatan' WHERE nip='$nip'";
    mysqli_query($conn, $query);
    header('Location: kelola_karyawan.php');
    exit();
}

// Ambil Semua Data Karyawan
$result = mysqli_query($conn, "SELECT * FROM karyawan WHERE status_aktif = 1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kelola Karyawan</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        #formTambahKaryawan {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out, padding 0.5s ease-out;
            padding: 0;
        }
        #formTambahKaryawan.show {
            max-height: 800px;
            padding: 15px;
        }
        @media (max-width: 576px) {
            .form-group {
                padding: 5px 0;
            }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h3 class="text-center">Kelola Data Pegawai</h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= $error; ?>
        </div>
    <?php endif; ?>

    <button id="btnTambahKaryawan" class="btn btn-primary mb-3">Tambah Pegawai</button>

    <div id="formTambahKaryawan" class="border border-primary rounded">
        <form method="POST" class="row">
            <div class="form-group col-md-6 col-12">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="form-group col-md-6 col-12">
                <label>NIP</label>
                <input type="text" name="nip" class="form-control" required>
            </div>
            <div class="form-group col-md-4 col-12">
                <label>Golongan</label>
                <input type="text" name="golongan" class="form-control" required>
            </div>
            <div class="form-group col-md-4 col-12">
                <label>Bagian/Bidang</label>
                <select name="bagian" class="form-control" required>
                    <option value="">-- Pilih Bagian/Bidang --</option>
                    <option value="Umum">Umum</option>
                    <option value="Kepabeanan dan Cukai">Kepabeanan dan Cukai</option>
                    <option value="Fasilitas Kepabeanan dan Cukai">Fasilitas Kepabeanan dan Cukai</option>
                    <option value="Penindakan dan Penyidikan">Penindakan dan Penyidikan</option>
                    <option value="Kepatuhan Internal">Kepatuhan Internal</option>
                </select>
            </div>
            <div class="form-group col-md-4 col-12">
                <label>Jabatan</label>
                <input type="text" name="jabatan" class="form-control" required>
            </div>
            <div class="form-group col-12 text-center">
                <button type="submit" name="tambah" class="btn btn-success mt-3">Simpan</button>
            </div>
        </form>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Golongan</th>
                    <th>Bagian/Bidang</th>
                    <th>Jabatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $row['nama']; ?></td>
                    <td><?= $row['nip']; ?></td>
                    <td><?= $row['golongan']; ?></td>
                    <td><?= $row['bagian']; ?></td>
                    <td><?= $row['jabatan']; ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="#" class="btn btn-warning btn-sm btnEdit" data-id="<?= $row['nip']; ?>"
                               data-nama="<?= $row['nama']; ?>" data-golongan="<?= $row['golongan']; ?>"
                               data-bagian="<?= $row['bagian']; ?>" data-jabatan="<?= $row['jabatan']; ?>">Edit</a>
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm btnHapus" 
                               data-url="kelola_karyawan.php?hapus=<?= $row['nip']; ?>">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Karyawan</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nip_edit" id="nipEdit">
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama_edit" id="namaEdit" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Golongan</label>
                        <input type="text" name="golongan_edit" id="golonganEdit" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Bagian/Bidang</label>
                        <select name="bagian_edit" id="bagianEdit" class="form-control" required>
                            <option value="">-- Pilih Bagian/Bidang --</option>
                            <option value="Umum">Umum</option>
                            <option value="Kepabeanan dan Cukai">Kepabeanan dan Cukai</option>
                            <option value="Fasilitas Kepabeanan dan Cukai">Fasilitas Kepabeanan dan Cukai</option>
                            <option value="Penindakan dan Penyidikan">Penindakan dan Penyidikan</option>
                            <option value="Kepatuhan Internal">Kepatuhan Internal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan_edit" id="jabatanEdit" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle form tambah karyawan
    document.getElementById('btnTambahKaryawan').addEventListener('click', function () {
        const form = document.getElementById('formTambahKaryawan');
        form.classList.toggle('show');
    });

    // Isi modal edit data karyawan
    document.querySelectorAll('.btnEdit').forEach(function (button) {
        button.addEventListener('click', function () {
            const nip = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const golongan = this.getAttribute('data-golongan');
            const bagian = this.getAttribute('data-bagian');
            const jabatan = this.getAttribute('data-jabatan');

            document.getElementById('nipEdit').value = nip;
            document.getElementById('namaEdit').value = nama;
            document.getElementById('golonganEdit').value = golongan;
            document.getElementById('bagianEdit').value = bagian;
            document.getElementById('jabatanEdit').value = jabatan;

            $('#modalEdit').modal('show');
        });
    });

    // Konfirmasi sebelum hapus
    document.querySelectorAll('.btnHapus').forEach(function (button) {
        button.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const confirmDelete = confirm('Apakah Anda yakin ingin menghapus data karyawan ini?');
            if (confirmDelete) {
                window.location.href = url;
            }
        });
    });
</script>
<script src="assets/jquery.min.js"></script>
<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>
