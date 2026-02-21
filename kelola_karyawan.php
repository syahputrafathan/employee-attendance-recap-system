<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$title = "Kelola Karyawan";
$content = 'kelola_karyawan_content.php';
include 'layout.php';
?>
