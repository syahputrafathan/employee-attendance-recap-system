<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: index.php'); exit; }
$activePage = 'riwayatcuti.php';
$title   = 'Riwayat Surat Tugas';
$content = 'riwayatst_content.php'; // PASTIKAN nama file & path ini benar
include 'layout.php';
