<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: index.php'); exit; }

$title   = 'Riwayat Cuti';
$content = 'riwayatcuti_content.php'; // PASTIKAN nama file & path ini benar
if (!file_exists($content)) {
    die("File content tidak ditemukan di path: " . realpath($content));
}

include 'layout.php';
