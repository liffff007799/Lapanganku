<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }
include 'koneksi.php';

/** @var mysqli $koneksi */

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_pemesanan = intval($_GET['id']);
    $action       = $_GET['action'];

    if ($action === 'setuju') {
        $status_baru = 'dikonfirmasi';
    } elseif ($action === 'tolak') {
        $status_baru = 'dibatalkan';
    } else {
        header("Location: data_pemesanan.php");
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE pemesanan SET status = ? WHERE id_pemesanan = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $status_baru, $id_pemesanan);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Jika dibatalkan, kembalikan status slot ke 'tersedia'
    if ($status_baru === 'dibatalkan') {
        $q_slot = mysqli_query($koneksi, "SELECT id_slot FROM pemesanan WHERE id_pemesanan='$id_pemesanan'");
        if ($q_slot && $r = mysqli_fetch_assoc($q_slot)) {
            $id_slot = intval($r['id_slot']);
            mysqli_query($koneksi, "UPDATE jadwal_slot SET status_slot='tersedia' WHERE id_slot='$id_slot'");
        }
    }

    header("Location: data_pemesanan.php?updated=1&action=$action");
    exit;
}

// JOIN ke jadwal_slot untuk ambil jam_mulai & jam_selesai
$query = mysqli_query($koneksi, "
    SELECT p.id_pemesanan, p.tanggal_pesan, p.total_harga, p.status, p.catatan, p.id_slot,
           u.nama AS nama_user, u.no_hp AS no_hp_user,
           l.nama_lapangan,
           js.jam_mulai, js.jam_selesai
    FROM pemesanan p
    LEFT JOIN users u       ON p.id_user     = u.id_user
    LEFT JOIN lapangan l    ON p.id_lapangan = l.id_lapangan
    LEFT JOIN jadwal_slot js ON p.id_slot    = js.id_slot
    ORDER BY p.id_pemesanan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemesanan — Admin LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{
            --green-900:#0a2e1a;--green-800:#0f3d22;--green-600:#1a6b3f;
            --green-500:#22874f;--green-400:#2da863;--green-300:#4dc47e;
            --green-200:#86dba8;--green-100:#c0f0d4;--green-50:#e8faf0;
            --white:#ffffff;
            --gray-50:#f7f9f8;--gray-100:#eef2f0;--gray-200:#d8e2dd;
            --gray-400:#88a097;--gray-600:#3e5f52;--gray-700:#2d4a3e;--gray-800:#1a2e26;
        }
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);min-height:100vh;}
        .navbar{background:var(--green-900);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
        .navbar-brand{display:flex;align-items:center;gap:12px;text-decoration:none;}
        .navbar-logo{width:38px;height:38px;background:var(--green-400);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .navbar-name{font-size:18px;font-weight:700;color:var(--white);}
        .nav-back{height:36px;padding:0 16px;background:rgba(255,255,255,.08);color:var(--green-200);border:1px solid rgba(255,255,255,.15);border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:all .2s;}
        .nav-back:hover{background:rgba(255,255,255,.14);color:var(--white);}
        .main{max-width:1100px;margin:0 auto;padding:36px 32px;}
        .page-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
        .page-title{font-size:24px;font-weight:800;color:var(--gray-800);letter-spacing:-.5px;}
        .toast-success{background:var(--green-50);border:1px solid var(--green-200);border-radius:10px;padding:12px 18px;font-size:14px;color:var(--gray-700);margin-bottom:20px;}
        .toast-danger{background:#fff1f0;border:1px solid #ffc0bc;border-radius:10px;padding:12px 18px;font-size:14px;color:#c0392b;margin-bottom:20px;}
        .table-card{background:var(--white);border:1px solid var(--gray-200);border-radius:16px;overflow:hidden;}
        .table-wrapper{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:14px;}
        thead th{background:var(--green-900);color:var(--white);padding:14px 16px;text-align:left;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;}
        tbody tr{border-bottom:1px solid var(--gray-100);transition:background .15s;}
        tbody tr:hover{background:var(--gray-50);}
        tbody tr:last-child{border-bottom:none;}
        td{padding:14px 16px;vertical-align:middle;color:var(--gray-800);}
        .td-id{font-weight:700;color:var(--gray-400);font-size:12px;}
        .td-name{font-weight:600;}
        .td-phone{color:var(--gray-400);font-size:12px;}
        .td-price{font-weight:700;color:var(--green-600);}
        .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;}
        .badge-pending{background:#fff8e1;color:#b07d00;border:1px solid #f5d77a;}
        .badge-approved{background:var(--green-50);color:var(--gray-700);border:1px solid var(--green-200);}
        .badge-rejected{background:#fff1f0;color:#c0392b;border:1px solid #ffc0bc;}
        .badge-selesai{background:#e8f4fd;color:#1a6b9a;border:1px solid #90cdf4;}
        .badge-slot{background:var(--gray-100);color:var(--gray-600);border:1px solid var(--gray-200);}
        .btn-approve{height:30px;padding:0 14px;background:var(--green-500);color:var(--white);border:none;border-radius:6px;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:background .2s;margin-right:6px;}
        .btn-approve:hover{background:var(--green-400);}
        .btn-reject{height:30px;padding:0 14px;background:transparent;color:#c0392b;border:1px solid #ffc0bc;border-radius:6px;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all .2s;}
        .btn-reject:hover{background:#fff1f0;}
        .empty-row td{text-align:center;padding:60px 20px;color:var(--gray-400);}
        @media(max-width:600px){.navbar{padding:0 16px;}.main{padding:20px 12px;}}
    </style>
</head>
<body>
<nav class="navbar">
    <a href="admin_dashboard.php" class="navbar-brand">
        <div class="navbar-logo">⚽</div>
        <span class="navbar-name">LapanganKu</span>
    </a>
    <a href="admin_dashboard.php" class="nav-back">← Dashboard</a>
</nav>

<div class="main">
    <div class="page-top">
        <h1 class="page-title">Data Pemesanan</h1>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <?php if ($_GET['action'] === 'setuju'): ?>
        <div class="toast-success">✅ Booking berhasil <strong>dikonfirmasi</strong>.</div>
        <?php else: ?>
        <div class="toast-danger">❌ Booking berhasil <strong>dibatalkan</strong>.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Lapangan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Total</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0):
                    while ($row = mysqli_fetch_assoc($query)):
                        $s = strtolower(trim($row['status']));

                        if ($s === 'dikonfirmasi') {
                            $bc = 'badge-approved'; $bt = '✅ Dikonfirmasi';
                        } elseif ($s === 'dibatalkan') {
                            $bc = 'badge-rejected'; $bt = '❌ Dibatalkan';
                        } elseif ($s === 'selesai') {
                            $bc = 'badge-selesai'; $bt = '⭐ Selesai';
                        } else {
                            $bc = 'badge-pending'; $bt = '⏳ Menunggu';
                        }

                        // Tampilkan jam dari JOIN jadwal_slot
                        if (!empty($row['jam_mulai']) && !empty($row['jam_selesai'])) {
                            $jam_display = substr($row['jam_mulai'], 0, 5) . '–' . substr($row['jam_selesai'], 0, 5);
                        } else {
                            $jam_display = 'Slot #' . $row['id_slot'];
                        }
                ?>
                    <tr>
                        <td class="td-id">#<?= $row['id_pemesanan'] ?></td>
                        <td>
                            <div class="td-name"><?= htmlspecialchars($row['nama_user'] ?? 'Dihapus') ?></div>
                            <div class="td-phone"><?= htmlspecialchars($row['no_hp_user'] ?? '-') ?></div>
                        </td>
                        <td><?= htmlspecialchars($row['nama_lapangan'] ?? 'Dihapus') ?></td>
                        <td><?= date('d M Y', strtotime($row['tanggal_pesan'])) ?></td>
                        <td><span class="badge badge-slot"><?= htmlspecialchars($jam_display) ?></span></td>
                        <td class="td-price">Rp <?= number_format($row['total_harga']) ?></td>
                        <td style="color:var(--gray-400);font-size:13px;max-width:140px;"><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>
                        <td><span class="badge <?= $bc ?>"><?= $bt ?></span></td>
                        <td>
                            <?php if ($s === 'pending'): ?>
                            <a href="data_pemesanan.php?action=setuju&id=<?= $row['id_pemesanan'] ?>"
                               class="btn-approve"
                               onclick="return confirm('Konfirmasi booking ini?')">✓ Setuju</a>
                            <a href="data_pemesanan.php?action=tolak&id=<?= $row['id_pemesanan'] ?>"
                               class="btn-reject"
                               onclick="return confirm('Batalkan booking ini?')">✗ Tolak</a>
                            <?php else: ?>
                            <span style="color:var(--gray-300);font-size:13px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr class="empty-row"><td colspan="9">Belum ada data pemesanan masuk.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>