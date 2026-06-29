<?php

session_start();
// include database connection; if it doesn't set $koneksi, create a default connection
include 'koneksi.php';
if (!isset($koneksi) || !$koneksi) {
    // fallback default connection (adjust credentials if needed)
    $koneksi = mysqli_connect('127.0.0.1', 'root', '', 'lapanganku');
    if (!$koneksi) {
        die('Database connection not available.');
    }
}

if(!isset($_SESSION['admin']))
{
    header("Location: admin_login.php");
    exit;
}

$id = $_GET['id'];
$status = $_GET['status'];

mysqli_query(
    $koneksi,
    "UPDATE pemesanan
    SET status='$status'
    WHERE id='$id'"
);

header("Location: data_pemesanan.php");
