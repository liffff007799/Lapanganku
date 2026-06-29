<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

/** @var mysqli $koneksi */
$data = mysqli_query($koneksi, "SELECT * FROM lapangan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Lapangan — LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-900: #0a2e1a; --green-800: #0f3d22; --green-700: #155230;
            --green-600: #1a6b3f; --green-500: #22874f; --green-400: #2da863;
            --green-300: #4dc47e; --green-200: #86dba8; --green-100: #c0f0d4;
            --green-50: #e8faf0; --white: #ffffff;
            --gray-50: #f7f9f8; --gray-100: #eef2f0; --gray-200: #d8e2dd;
            --gray-300: #b5c8bf; --gray-400: #88a097; --gray-600: #3e5f52;
            --gray-800: #1a2e26;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-50); min-height: 100vh; color: var(--gray-800); }

        .navbar {
            background: var(--green-900);
            padding: 0 32px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .navbar-logo { width: 38px; height: 38px; background: var(--green-400); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .navbar-name { font-size: 18px; font-weight: 700; color: var(--white); }
        .nav-back { height: 36px; padding: 0 16px; background: rgba(255,255,255,0.08); color: var(--green-200); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .nav-back:hover { background: rgba(255,255,255,0.14); color: var(--white); }

        .page-header { background: var(--green-800); padding: 40px 32px; }
        .page-header-inner { max-width: 960px; margin: 0 auto; }
        .page-eyebrow { font-size: 12px; color: var(--green-300); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .page-title { font-size: 30px; font-weight: 800; color: var(--white); letter-spacing: -0.5px; }
        .page-sub { font-size: 14px; color: var(--green-200); margin-top: 6px; }

        .main { max-width: 960px; margin: 0 auto; padding: 40px 32px; }

        .field-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }

        .field-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s;
        }
        .field-card:hover {
            border-color: var(--green-400);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(34, 135, 79, 0.12);
        }
        .field-image { position: relative; }
        .field-image img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .field-badge {
            position: absolute;
            top: 12px; left: 12px;
            background: var(--green-500);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field-body { padding: 20px; }
        .field-name { font-size: 17px; font-weight: 700; color: var(--gray-800); margin-bottom: 6px; }
        .field-price { font-size: 22px; font-weight: 800; color: var(--green-600); }
        .field-price span { font-size: 13px; font-weight: 500; color: var(--gray-400); }
        .field-info { display: flex; gap: 16px; margin: 12px 0 18px; }
        .field-info-item { font-size: 12px; color: var(--gray-400); display: flex; align-items: center; gap: 4px; }
        .btn-book {
            display: block;
            width: 100%;
            height: 44px;
            background: var(--green-500);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            line-height: 44px;
            transition: background 0.2s;
        }
        .btn-book:hover { background: var(--green-400); }

        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-title { font-size: 20px; font-weight: 700; color: var(--gray-600); margin-bottom: 8px; }
        .empty-sub { font-size: 14px; color: var(--gray-400); }

        @media (max-width: 600px) {
            .navbar { padding: 0 16px; }
            .page-header { padding: 28px 16px; }
            .main { padding: 24px 16px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">
        <div class="navbar-logo">⚽</div>
        <span class="navbar-name">LapanganKu</span>
    </a>
    <a href="dashboard.php" class="nav-back">← Dashboard</a>
</nav>

<div class="page-header">
    <div class="page-header-inner">
        <p class="page-eyebrow">Tersedia sekarang</p>
        <h1 class="page-title">🏟 Pilih Lapangan</h1>
        <p class="page-sub">Semua lapangan siap dipakai — pilih yang cocok dan booking langsung</p>
    </div>
</div>

<div class="main">
    <?php if (mysqli_num_rows($data) > 0): ?>
    <div class="field-grid">
        <?php while ($row = mysqli_fetch_assoc($data)):
            $id_lapangan   = $row['id_lapangan'];
            $nama_lapangan = htmlspecialchars($row['nama_lapangan']);
            $harga         = number_format($row['harga_per_jam']);
        ?>
        <div class="field-card">
            <div class="field-image">
                <img src="https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=800&q=80" alt="<?= $nama_lapangan ?>">
                <span class="field-badge">✅ Tersedia</span>
            </div>
            <div class="field-body">
                <div class="field-name"><?= $nama_lapangan ?></div>
                <div class="field-price">
                    Rp <?= $harga ?><span>/jam</span>
                </div>
                <div class="field-info">
                    <div class="field-info-item">⏰ 5 slot tersedia</div>
                    <div class="field-info-item">📍 Kendari</div>
                </div>
                <a href="booking.php?id=<?= $id_lapangan ?>" class="btn-book">
                    Booking Sekarang
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🏟</div>
        <div class="empty-title">Belum ada lapangan tersedia</div>
        <div class="empty-sub">Admin belum menambahkan lapangan. Coba lagi nanti.</div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
