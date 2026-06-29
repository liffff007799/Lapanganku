<?php
session_start();

if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - LapanganKu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:
            linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
            url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
        }

        .glass{
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .menu-card{
            transition: 0.3s;
        }

        .menu-card:hover{
            transform: translateY(-8px);
        }

        .title-big{
            font-size: 50px;
            font-weight: bold;
        }

        .subtitle{
            color: #dcdcdc;
        }
    </style>
</head>
<body>

<div class="container py-5">

    <div class="text-center mb-5">

        <h1 class="title-big">⚽ LapanganKu</h1>

        <h4>Selamat Datang, <?php echo $_SESSION['nama']; ?></h4>

        <p class="subtitle">
            Booking lapangan futsal lebih cepat dan lebih mudah
        </p>

    </div>

    <div class="row g-4 justify-content-center">

        <div class="col-md-3">
            <div class="glass p-4 text-center menu-card">

                <h3>🏟</h3>
                <h4>Lihat Lapangan</h4>

                <p>Lihat semua lapangan futsal tersedia</p>

                <a href="lapangan.php" class="btn btn-success w-100">
                    Masuk
                </a>

            </div>
        </div>

        <div class="col-md-3">
            <div class="glass p-4 text-center menu-card">

                <h3>📜</h3>
                <h4>Riwayat Booking</h4>

                <p>Cek semua booking yang pernah dibuat</p>

                <a href="riwayat.php" class="btn btn-warning w-100">
                    Lihat
                </a>

            </div>
        </div>

        <div class="col-md-3">
            <div class="glass p-4 text-center menu-card">

                <h3>🚪</h3>
                <h4>Logout</h4>

                <p>Keluar dari akun saat ini</p>

                <a href="logout.php" class="btn btn-danger w-100">
                    Logout
                </a>

            </div>
        </div>

    </div>

</div>

</body>
</html>