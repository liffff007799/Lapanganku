<?php
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit; }
include 'koneksi.php';

/** @var mysqli $koneksi */
$user_id = intval($_SESSION['id']);

// JOIN ke jadwal_slot untuk ambil jam_mulai & jam_selesai
$query = mysqli_query($koneksi, "
    SELECT p.id_pemesanan, p.tanggal_pesan, p.total_harga, p.status, p.catatan, p.created_at,
           l.nama_lapangan, l.harga_per_jam,
           js.jam_mulai, js.jam_selesai, js.tanggal AS tanggal_slot
    FROM pemesanan p
    LEFT JOIN lapangan l    ON p.id_lapangan = l.id_lapangan
    LEFT JOIN jadwal_slot js ON p.id_slot    = js.id_slot
    WHERE p.id_user = '$user_id'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking — LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{
            --green-900:#0a2e1a;--green-800:#0f3d22;--green-600:#1a6b3f;
            --green-500:#22874f;--green-400:#2da863;--green-300:#4dc47e;
            --green-200:#86dba8;--green-100:#c0f0d4;--green-50:#e8faf0;
            --white:#ffffff;--gray-50:#f7f9f8;--gray-100:#eef2f0;--gray-200:#d8e2dd;
            --gray-400:#88a097;--gray-600:#3e5f52;--gray-800:#1a2e26;
        }
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);min-height:100vh;color:var(--gray-800);}
        .navbar{background:var(--green-900);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
        .navbar-brand{display:flex;align-items:center;gap:12px;text-decoration:none;}
        .navbar-logo{width:38px;height:38px;background:var(--green-400);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .navbar-name{font-size:18px;font-weight:700;color:var(--white);}
        .nav-back{height:36px;padding:0 16px;background:rgba(255,255,255,.08);color:var(--green-200);border:1px solid rgba(255,255,255,.15);border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:all .2s;}
        .nav-back:hover{background:rgba(255,255,255,.14);color:var(--white);}
        .page-header{background:var(--green-800);padding:40px 32px;}
        .page-header-inner{max-width:900px;margin:0 auto;}
        .page-eyebrow{font-size:12px;color:var(--green-300);font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
        .page-title{font-size:30px;font-weight:800;color:var(--white);letter-spacing:-.5px;}
        .page-sub{font-size:14px;color:var(--green-200);margin-top:6px;}
        .main{max-width:900px;margin:0 auto;padding:36px 32px;}
        .toast-success{background:var(--green-50);border:1px solid var(--green-100);border-radius:10px;padding:12px 18px;font-size:14px;color:var(--gray-600);margin-bottom:20px;}
        .booking-list{display:flex;flex-direction:column;gap:14px;}
        .booking-card{background:var(--white);border:1px solid var(--gray-200);border-radius:16px;padding:22px 24px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:start;}
        .booking-top{display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;}
        .booking-field{font-size:16px;font-weight:700;color:var(--gray-800);}
        .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;}
        .badge-pending{background:#fff8e1;color:#b07d00;border:1px solid #f5d77a;}
        .badge-approved{background:var(--green-50);color:var(--green-600);border:1px solid var(--green-200);}
        .badge-rejected{background:#fff1f0;color:#c0392b;border:1px solid #ffc0bc;}
        .badge-selesai{background:#e8f4fd;color:#1a6b9a;border:1px solid #90cdf4;}
        .booking-details{display:flex;flex-wrap:wrap;gap:8px 24px;font-size:13px;color:var(--gray-400);}
        .booking-details strong{color:var(--gray-600);}
        .booking-price{font-size:20px;font-weight:800;color:var(--green-600);white-space:nowrap;}
        .booking-date-created{font-size:12px;color:var(--gray-400);margin-top:4px;}
        .booking-catatan{margin-top:10px;font-size:13px;color:var(--gray-400);background:var(--gray-50);border-radius:8px;padding:8px 12px;}
        .empty-state{text-align:center;padding:80px 20px;}
        .empty-icon{font-size:64px;margin-bottom:16px;}
        .empty-title{font-size:20px;font-weight:700;color:var(--gray-600);margin-bottom:8px;}
        .empty-sub{font-size:14px;color:var(--gray-400);margin-bottom:24px;}
        .btn-book-now{display:inline-block;height:44px;padding:0 24px;background:var(--green-500);color:var(--white);border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;text-decoration:none;line-height:44px;transition:background .2s;}
        .btn-book-now:hover{background:var(--green-400);}
        @media(max-width:600px){.navbar{padding:0 16px;}.page-header{padding:28px 16px;}.main{padding:24px 16px;}.booking-card{grid-template-columns:1fr;}}
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
        <p class="page-eyebrow">Akun Saya</p>
        <h1 class="page-title">📜 Riwayat Booking</h1>
        <p class="page-sub">Semua booking yang pernah kamu buat</p>
    </div>
</div>

<div class="main">
    <?php if (isset($_GET['booked'])): ?>
    <div class="toast-success">✅ Booking berhasil dikirim! Admin akan mengkonfirmasi dalam waktu singkat.</div>
    <?php endif; ?>

    <?php if ($query && mysqli_num_rows($query) > 0): ?>
    <div class="booking-list">
        <?php while ($row = mysqli_fetch_assoc($query)):
            $s = strtolower(trim($row['status']));
            if ($s === 'dikonfirmasi')   { $bc = 'badge-approved'; $bt = '✅ Dikonfirmasi'; }
            elseif ($s === 'dibatalkan') { $bc = 'badge-rejected'; $bt = '❌ Dibatalkan'; }
            elseif ($s === 'selesai')    { $bc = 'badge-selesai';  $bt = '⭐ Selesai'; }
            else                         { $bc = 'badge-pending';  $bt = '⏳ Menunggu Konfirmasi'; }

            // Tampilkan jam dari JOIN
            if (!empty($row['jam_mulai']) && !empty($row['jam_selesai'])) {
                $jam_display = substr($row['jam_mulai'], 0, 5) . '–' . substr($row['jam_selesai'], 0, 5);
            } else {
                $jam_display = '-';
            }
        ?>
        <div class="booking-card">
            <div>
                <div class="booking-top">
                    <span class="booking-field"><?= htmlspecialchars($row['nama_lapangan'] ?? 'Lapangan dihapus') ?></span>
                    <span class="badge <?= $bc ?>"><?= $bt ?></span>
                </div>
                <div class="booking-details">
                    <span>📅 <strong><?= date('d M Y', strtotime($row['tanggal_pesan'])) ?></strong></span>
                    <span>⏰ <strong><?= htmlspecialchars($jam_display) ?></strong></span>
                </div>
                <?php if (!empty($row['catatan'])): ?>
                <div class="booking-catatan">💬 <?= htmlspecialchars($row['catatan']) ?></div>
                <?php endif; ?>
                <div class="booking-date-created">Dipesan: <?= date('d M Y, H:i', strtotime($row['created_at'])) ?></div>
            </div>
            <div>
                <div class="booking-price">Rp <?= number_format($row['total_harga']) ?></div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <div class="empty-title">Belum ada booking</div>
        <div class="empty-sub">Kamu belum pernah membooking lapangan. Yuk mulai sekarang!</div>
        <a href="lapangan.php" class="btn-book-now">🏟 Booking Lapangan</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>