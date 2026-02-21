<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';
$title = "Dashboard";
$content = 'dashboard_content.php';
include 'layout.php';

// Ambil tanggal hari ini
$today = date('Y-m-d');

/**
 * 1️⃣ Update status karyawan jika cuti/surat tugas sudah selesai
 *    (tidak perlu ubah data di presensi karena status CUTI/ST disimpan di tabel detail terpisah)
 *    Jadi bagian ini cukup dilewati atau dijadikan komentar.
 */
// Tidak perlu ada query UPDATE lagi, karena sistem baru tidak pakai tanggal_selesai di presensi.
// Jika ingin menghapus cuti/tugas lama bisa dilakukan manual di modulnya.
// $conn->query("UPDATE presensi SET status='HADIR' WHERE ... ");

/**
 * 2️⃣ Tambahkan otomatis presensi "HADIR" untuk karyawan
 *    yang belum tercatat di presensi hari ini dan tidak sedang CUTI/ST.
 */
$query = "
    SELECT k.nip
    FROM karyawan k
    WHERE k.nip NOT IN (
        SELECT p.nip
        FROM presensi p
        WHERE DATE(p.timestamp) = ?
    )
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
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $today, $today, $today, $today, $today);
$stmt->execute();
$result = $stmt->get_result();

// Masukkan presensi HADIR untuk yang belum tercatat
$insertQuery = "INSERT INTO presensi (nip, `timestamp`, status) VALUES (?, ?, 'HADIR')";
$insertStmt = $conn->prepare($insertQuery);

while ($row = $result->fetch_assoc()) {
    $nip = $row['nip'];
    $timestamp = date('Y-m-d H:i:s');
    $insertStmt->bind_param("ss", $nip, $timestamp);
    $insertStmt->execute();
}
?>
