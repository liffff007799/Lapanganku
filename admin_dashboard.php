<?php

session_start();

$koneksi = null;
require_once 'koneksi.php';

if (!isset($koneksi) || $koneksi === null) {
    die('Database connection not established.');
}

if(!isset($_SESSION['admin']))
{
    header("Location: admin_login.php");
    exit;
}

$total_booking = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) as total FROM pemesanan"
);

$total = mysqli_fetch_assoc($total_booking);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.80), rgba(0,0,0,0.80)),
            url('https://images.unsplash.com/photo-1508098682722-e99c643e7485?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
        }

        .glass{
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .menu-card{
            transition: 0.3s;
        }

        .menu-card:hover{
            transform: translateY(-8px);
        }

        .big-number{
            font-size: 45px;
            font-weight: bold;
            color: #ffc107;
        }

    </style>
</head>
<body>

<div class="container py-5">

    <div class="text-center mb-5">

        <h1>🛡 Dashboard Admin</h1>

        <h4>
            Selamat datang,
            <?php echo $_SESSION['admin']; ?>
        </h4>

        <p>Kelola semua pemesanan lapangan futsal</p>

    </div>

    <div class="row g-4 justify-content-center">

        <div class="col-md-3">

            <div class="glass p-4 text-center menu-card">

                <h3>📊 Total Booking</h3>

                <div class="big-number">
                    <?php echo $total['total']; ?>
                </div>

                <p>Total semua booking user</p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="glass p-4 text-center menu-card">

                <h3>📋 Data Pemesanan</h3>

                <p>Lihat semua data booking user</p>

                <a href="data_pemesanan.php"
                class="btn btn-warning w-100">
                    Kelola
                </a>

            </div>

        </div>

        <div class="col-md-3">

            <div class="glass p-4 text-center menu-card">

                <h3>🚪 Logout</h3>

                <p>Keluar dari panel admin</p>

                <a href="logout.php"
                class="btn btn-danger w-100">
                    Logout
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>