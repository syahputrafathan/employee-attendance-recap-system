<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$title = "Input Presensi";
$content = 'input_presensi_content.php';
include 'layout.php';
?>
