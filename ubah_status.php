<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id     = intval($_GET['id']);
    $status = strtolower(trim($_GET['status']));

    $allowed = ['menunggu', 'disetujui', 'ditolak'];
    if (in_array($status, $allowed)) {
        /** @var mysqli $koneksi */
        $stmt = mysqli_prepare($koneksi, "UPDATE pemesanan SET status = ? WHERE id_pemesanan = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

header("Location: data_pemesanan.php");
exit;
?>