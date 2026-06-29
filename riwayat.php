<?php

session_start();
require_once __DIR__ . '/koneksi.php';

if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];

$data = mysqli_query(
    $koneksi,
    "SELECT
        pemesanan.*,
        lapangan.nama_lapangan
    FROM pemesanan
    JOIN lapangan
    ON pemesanan.lapangan_id = lapangan.id
    WHERE user_id='$user_id'
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pemesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
            url('https://images.unsplash.com/photo-1547347298-4074fc3086f0?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
        }

        .history-card{
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 25px;
            transition: 0.3s;
        }

        .history-card:hover{
            transform: translateY(-5px);
        }

        .status{
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .menunggu{
            background: orange;
            color: black;
        }

        .disetujui{
            background: green;
            color: white;
        }

        .ditolak{
            background: red;
            color: white;
        }

        .dibatalkan{
            background: gray;
            color: white;
        }

    </style>
</head>
<body>

<div class="container py-5">

    <div class="text-center mb-5">

        <h1>📜 Riwayat Booking</h1>
        <p>Lihat semua aktivitas booking kamu</p>

    </div>

    <div class="row g-4">

        <?php while($row = mysqli_fetch_assoc($data)){ ?>

        <div class="col-md-6">

            <div class="history-card">

                <h3>
                    ⚽ <?php echo $row['nama_lapangan']; ?>
                </h3>

                <hr>

                <p>
                    <strong>Tanggal:</strong>
                    <?php echo $row['tanggal']; ?>
                </p>

                <p>
                    <strong>Jam:</strong>
                    <?php echo $row['jam_mulai']; ?>
                    -
                    <?php echo $row['jam_selesai']; ?>
                </p>

                <p>
                    <strong>Status:</strong>

                    <br><br>

                    <a href="hapus_booking.php?id=<?php echo $row['id']; ?>"
                    onclick="return confirm('Yakin ingin membatalkan booking ini?')"
                    class="btn btn-danger">
                        Batal Booking
                    </a>

                    <?php
                    if($row['status'] == 'Menunggu'){
                        echo "<span class='status menunggu'>Menunggu</span>";
                    }elseif($row['status'] == 'Disetujui'){
                        echo "<span class='status disetujui'>Disetujui</span>";
                    }elseif($row['status'] == 'Ditolak'){
                        echo "<span class='status ditolak'>Ditolak</span>";
                    }else{
                        echo "<span class='status dibatalkan'>Dibatalkan</span>";
                    }
                    ?>
                </p>

            </div>

        </div>

        <?php } ?>

    </div>

    <div class="text-center mt-5">

        <a href="dashboard.php" class="btn btn-light">
            Kembali ke Dashboard
        </a>

    </div>

</div>

</body>
</html>