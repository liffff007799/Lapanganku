<?php

session_start();
require_once 'koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die('Gagal terhubung ke database.');
}

if(!isset($_SESSION['admin']))
{
    header("Location: admin_login.php");
    exit;
}

$data = mysqli_query(
    $koneksi,
    "SELECT
        pemesanan.*,
        users.nama,
        users.no_hp,
        lapangan.nama_lapangan
    FROM pemesanan
    JOIN users
        ON pemesanan.user_id = users.id
    JOIN lapangan
        ON pemesanan.lapangan_id = lapangan.id"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Pemesanan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.80), rgba(0,0,0,0.80)),
            url('https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
        }

        .glass-table{
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        table{
            color: white !important;
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

    </style>
</head>
<body>

<div class="container py-5">

    <div class="text-center mb-4">

        <h1>📋 Data Pemesanan</h1>

        <p>Kelola semua booking user</p>

    </div>

    <div class="glass-table">

        <table class="table table-bordered table-hover text-center align-middle">

            <tr>
                <th>No</th>
                <th>User</th>
                <th>No HP</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php
            $no = 1;

            while($row = mysqli_fetch_assoc($data))
            {
            ?>

            <tr>

                <td><?php echo $no; ?></td>

                <td><?php echo $row['nama']; ?></td>

                <td><?php echo $row['no_hp']; ?></td>

                <td><?php echo $row['nama_lapangan']; ?></td>

                <td><?php echo $row['tanggal']; ?></td>

                <td>
                    <?php echo $row['jam_mulai']; ?>
                    -
                    <?php echo $row['jam_selesai']; ?>
                </td>

                <td>

                    <?php
                    if($row['status'] == 'Menunggu'){
                        echo "<span class='status menunggu'>Menunggu</span>";
                    }elseif($row['status'] == 'Disetujui'){
                        echo "<span class='status disetujui'>Disetujui</span>";
                    }else{
                        echo "<span class='status ditolak'>Ditolak</span>";
                    }
                    ?>

                </td>

                <td>

                    <a href="ubah_status.php?id=<?php echo $row['id']; ?>&status=Disetujui"
                    class="btn btn-success btn-sm">
                        Setujui
                    </a>

                    <a href="ubah_status.php?id=<?php echo $row['id']; ?>&status=Ditolak"
                    class="btn btn-danger btn-sm">
                        Tolak
                    </a>

                </td>

            </tr>

            <?php
            $no++;
            }
            ?>

        </table>

    </div>

    <div class="text-center mt-4">

        <a href="admin_dashboard.php" class="btn btn-light">
            Kembali ke Dashboard Admin
        </a>

    </div>

</div>

</body>
</html>