<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }

/** @var mysqli $koneksi */
$total_booking   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pemesanan"))['total'];
$total_menunggu  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pemesanan WHERE status='pending'"))['total'];
$total_disetujui = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pemesanan WHERE status='dikonfirmasi'"))['total'];
$total_user      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pelanggan'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{
            --green-900:#0a2e1a;--green-800:#0f3d22;--green-700:#155230;
            --green-600:#1a6b3f;--green-500:#22874f;--green-400:#2da863;
            --green-300:#4dc47e;--green-200:#86dba8;--green-100:#c0f0d4;
            --green-50:#e8faf0;--white:#ffffff;
            --gray-50:#f7f9f8;--gray-100:#eef2f0;--gray-200:#d8e2dd;
            --gray-400:#88a097;--gray-600:#3e5f52;--gray-800:#1a2e26;
        }
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);min-height:100vh;}
        .navbar{background:var(--green-900);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;}
        .navbar-brand{display:flex;align-items:center;gap:12px;text-decoration:none;}
        .navbar-logo{width:38px;height:38px;background:var(--green-400);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .navbar-name{font-size:18px;font-weight:700;color:var(--white);}
        .navbar-right{display:flex;align-items:center;gap:10px;}
        .admin-chip{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:0 14px;height:36px;color:var(--white);font-size:13px;}
        .admin-badge{background:var(--green-400);color:var(--green-900);font-size:10px;font-weight:800;padding:2px 8px;border-radius:10px;text-transform:uppercase;}
        .btn-logout{height:36px;padding:0 16px;background:transparent;color:#ff8a8a;border:1px solid rgba(255,100,100,.25);border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:all .2s;}
        .btn-logout:hover{background:rgba(255,80,80,.10);}
        .hero{background:var(--green-800);padding:44px 32px;position:relative;overflow:hidden;}
        .hero::after{content:'🛡';position:absolute;right:48px;top:50%;transform:translateY(-50%);font-size:100px;opacity:.08;pointer-events:none;}
        .hero-inner{max-width:900px;margin:0 auto;position:relative;z-index:1;}
        .hero-eyebrow{font-size:12px;color:var(--green-300);font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
        .hero-title{font-size:30px;font-weight:800;color:var(--white);letter-spacing:-.5px;}
        .hero-sub{font-size:14px;color:var(--green-200);margin-top:6px;}
        .main{max-width:900px;margin:0 auto;padding:40px 32px;}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:36px;}
        .stat-card{background:var(--white);border:1px solid var(--gray-200);border-radius:16px;padding:22px 20px;}
        .stat-label{font-size:12px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;}
        .stat-num{font-size:36px;font-weight:800;letter-spacing:-1px;}
        .stat-num-green{color:var(--green-600);}
        .stat-num-yellow{color:#b07d00;}
        .stat-num-blue{color:#1a5fa8;}
        .stat-num-gray{color:var(--gray-600);}
        .stat-sub{font-size:12px;color:var(--gray-400);margin-top:4px;}
        .section-title{font-size:12px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;}
        .menu-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;}
        .menu-card{background:var(--white);border:1px solid var(--gray-200);border-radius:16px;padding:24px;text-decoration:none;display:block;transition:all .2s;}
        .menu-card:hover{border-color:var(--green-400);transform:translateY(-3px);box-shadow:0 8px 24px rgba(34,135,79,.12);}
        .menu-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;}
        .icon-blue{background:#e6f2ff;}
        .icon-red{background:#fff1f0;}
        .menu-title{font-size:16px;font-weight:700;color:var(--gray-800);margin-bottom:5px;}
        .menu-desc{font-size:13px;color:var(--gray-400);line-height:1.5;}
        .menu-arrow{font-size:13px;font-weight:600;color:var(--green-600);margin-top:14px;}
        .menu-arrow-red{color:#c0392b;}
        @media(max-width:600px){.navbar{padding:0 16px;}.hero{padding:28px 16px;}.main{padding:24px 16px;}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="admin_dashboard.php" class="navbar-brand">
        <div class="navbar-logo">⚽</div>
        <span class="navbar-name">LapanganKu</span>
    </a>
    <div class="navbar-right">
        <div class="admin-chip">
            <span class="admin-badge">Admin</span>
            <?= htmlspecialchars($_SESSION['admin']) ?>
        </div>
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin logout?')">Logout</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Panel Admin</p>
        <h1 class="hero-title">🛡 Admin Dashboard</h1>
        <p class="hero-sub">Kelola semua booking dan data lapangan futsal</p>
    </div>
</div>

<div class="main">
    <p class="section-title">Ringkasan Data</p>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Booking</div>
            <div class="stat-num stat-num-gray"><?= $total_booking ?></div>
            <div class="stat-sub">Semua waktu</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Menunggu Konfirmasi</div>
            <div class="stat-num stat-num-yellow"><?= $total_menunggu ?></div>
            <div class="stat-sub">Perlu ditindaklanjuti</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Dikonfirmasi</div>
            <div class="stat-num stat-num-green"><?= $total_disetujui ?></div>
            <div class="stat-sub">Booking aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Pelanggan</div>
            <div class="stat-num stat-num-blue"><?= $total_user ?></div>
            <div class="stat-sub">Pengguna terdaftar</div>
        </div>
    </div>

    <p class="section-title">Menu Admin</p>
    <div class="menu-grid">
        <a href="data_pemesanan.php" class="menu-card">
            <div class="menu-icon icon-blue">📋</div>
            <div class="menu-title">Data Pemesanan</div>
            <div class="menu-desc">Lihat, setujui, atau tolak semua booking dari pelanggan.</div>
            <div class="menu-arrow">Buka data pemesanan →</div>
        </a>
        <a href="logout.php" class="menu-card" onclick="return confirm('Yakin logout?')">
            <div class="menu-icon icon-red">🚪</div>
            <div class="menu-title">Logout</div>
            <div class="menu-desc">Keluar dari panel admin dengan aman.</div>
            <div class="menu-arrow menu-arrow-red">Keluar →</div>
        </a>
    </div>
</div>
</body>
</html>