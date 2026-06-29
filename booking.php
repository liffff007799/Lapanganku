<?php
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit; }
include 'koneksi.php';
if (!isset($_GET['id'])) { header("Location: lapangan.php"); exit; }

$id_lapangan = intval($_GET['id']);

/** @var mysqli $koneksi */
$query = mysqli_query($koneksi, "SELECT * FROM lapangan WHERE id_lapangan = '$id_lapangan'");
if (mysqli_num_rows($query) === 0) { die("Lapangan tidak ditemukan."); }
$lapangan = mysqli_fetch_assoc($query);

// ─── Tangkap tanggal dari 3 dropdown ──────────────────────────
$tanggal_dipilih = '';
$sel_hari  = isset($_POST['tgl_hari'])  ? intval($_POST['tgl_hari'])  : 0;
$sel_bulan = isset($_POST['tgl_bulan']) ? intval($_POST['tgl_bulan']) : 0;
$sel_tahun = isset($_POST['tgl_tahun']) ? intval($_POST['tgl_tahun']) : 0;

if ($sel_hari && $sel_bulan && $sel_tahun) {
    if (checkdate($sel_bulan, $sel_hari, $sel_tahun)) {
        $tanggal_dipilih = sprintf('%04d-%02d-%02d', $sel_tahun, $sel_bulan, $sel_hari);
    }
}

$tahun_min = (int)date('Y');
$tahun_max = $tahun_min + 2;

// ─── Ambil slot dari tabel jadwal_slot sesuai lapangan & tanggal ──
$slots = [];
if ($tanggal_dipilih) {
    $tgl_esc = mysqli_real_escape_string($koneksi, $tanggal_dipilih);
    $q_slots = mysqli_query($koneksi,
        "SELECT id_slot, jam_mulai, jam_selesai, status_slot
         FROM jadwal_slot
         WHERE id_lapangan = '$id_lapangan'
           AND tanggal = '$tgl_esc'
         ORDER BY jam_mulai ASC"
    );
    while ($s = mysqli_fetch_assoc($q_slots)) {
        $jam_mulai   = substr($s['jam_mulai'],  0, 5); // HH:MM
        $jam_selesai = substr($s['jam_selesai'], 0, 5);
        $jam_int     = (int)str_replace(':', '', $jam_mulai);
        if ($jam_int < 1200) { $icon = '🌅'; $label = 'Pagi'; }
        elseif ($jam_int < 1600) { $icon = '☀️'; $label = 'Siang'; }
        elseif ($jam_int < 1900) { $icon = '🌤'; $label = 'Sore'; }
        else { $icon = '🌙'; $label = 'Malam'; }

        $slots[$s['id_slot']] = [
            'label'      => $label,
            'jam'        => "$jam_mulai – $jam_selesai",
            'icon'       => $icon,
            'is_booked'  => ($s['status_slot'] !== 'tersedia'),
        ];
    }
} else {
    // Tampilkan slot generik sebelum tanggal dipilih (dari semua slot lapangan ini)
    $q_generic = mysqli_query($koneksi,
        "SELECT DISTINCT jam_mulai, jam_selesai
         FROM jadwal_slot WHERE id_lapangan = '$id_lapangan'
         ORDER BY jam_mulai ASC LIMIT 10"
    );
    $tmp_id = 1;
    while ($s = mysqli_fetch_assoc($q_generic)) {
        $jam_mulai   = substr($s['jam_mulai'],  0, 5);
        $jam_selesai = substr($s['jam_selesai'], 0, 5);
        $jam_int     = (int)str_replace(':', '', $jam_mulai);
        if ($jam_int < 1200) { $icon = '🌅'; $label = 'Pagi'; }
        elseif ($jam_int < 1600) { $icon = '☀️'; $label = 'Siang'; }
        elseif ($jam_int < 1900) { $icon = '🌤'; $label = 'Sore'; }
        else { $icon = '🌙'; $label = 'Malam'; }
        $slots[$tmp_id++] = ['label' => $label, 'jam' => "$jam_mulai – $jam_selesai", 'icon' => $icon, 'is_booked' => false];
    }
}

// Slot yang sudah terpesan (dari tabel pemesanan, untuk double-booking guard)
$booked_slots = [];
if ($tanggal_dipilih) {
    $tgl_esc = mysqli_real_escape_string($koneksi, $tanggal_dipilih);
    $q_booked = mysqli_query($koneksi,
        "SELECT id_slot FROM pemesanan
         WHERE id_lapangan = '$id_lapangan'
           AND tanggal_pesan = '$tgl_esc'
           AND status NOT IN ('dibatalkan')"
    );
    while ($row = mysqli_fetch_assoc($q_booked)) {
        $booked_slots[] = (int)$row['id_slot'];
    }
    // Tandai juga slot yang status_slot = 'terpesan' atau 'diblokir' di jadwal_slot
    foreach ($slots as $sid => &$sl) {
        if ($sl['is_booked'] || in_array($sid, $booked_slots)) {
            $sl['is_booked'] = true;
        }
    }
    unset($sl);
}

// ─── Proses booking ────────────────────────────────────────────
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    if (!$tanggal_dipilih || empty($_POST['id_slot'])) {
        $error = 'Pilih tanggal lengkap dan slot waktu terlebih dahulu.';
    } else {
        $user_id       = intval($_SESSION['id']);
        $id_slot       = intval($_POST['id_slot']);
        $catatan       = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
        $total_harga   = $lapangan['harga_per_jam'];
        $tgl_esc       = mysqli_real_escape_string($koneksi, $tanggal_dipilih);
        $status_baru   = 'pending';

        // Validasi: slot valid?
        if (!array_key_exists($id_slot, $slots)) {
            $error = 'Slot waktu tidak valid.';
        }
        // Validasi: tanggal tidak di masa lalu
        elseif ($tanggal_dipilih < date('Y-m-d')) {
            $error = 'Tanggal tidak boleh di masa lalu.';
        }
        else {
            // Race condition guard
            mysqli_query($koneksi, "START TRANSACTION");
            $race_check = mysqli_query($koneksi,
                "SELECT id_pemesanan FROM pemesanan
                 WHERE id_lapangan = '$id_lapangan'
                   AND id_slot     = '$id_slot'
                   AND tanggal_pesan = '$tgl_esc'
                   AND status NOT IN ('dibatalkan')
                 FOR UPDATE"
            );
            // Cek juga status slot di jadwal_slot
            $slot_check = mysqli_query($koneksi,
                "SELECT status_slot FROM jadwal_slot
                 WHERE id_slot = '$id_slot' AND status_slot != 'tersedia'"
            );
            if (mysqli_num_rows($race_check) > 0 || ($slot_check && mysqli_num_rows($slot_check) > 0)) {
                mysqli_query($koneksi, "ROLLBACK");
                $error = 'Slot sudah tidak tersedia. Pilih slot atau tanggal lain.';
            } else {
                $insert = mysqli_query($koneksi,
                    "INSERT INTO pemesanan
                        (id_user, id_slot, id_lapangan, tanggal_pesan, total_harga, status, catatan)
                     VALUES
                        ('$user_id','$id_slot','$id_lapangan','$tgl_esc','$total_harga','$status_baru','$catatan')"
                );
                if ($insert) {
                    // Update status slot menjadi terpesan
                    mysqli_query($koneksi,
                        "UPDATE jadwal_slot SET status_slot='terpesan'
                         WHERE id_slot='$id_slot'"
                    );
                    mysqli_query($koneksi, "COMMIT");
                    header("Location: riwayat.php?booked=1");
                    exit;
                } else {
                    mysqli_query($koneksi, "ROLLBACK");
                    $error = 'Booking gagal: ' . mysqli_error($koneksi);
                }
            }
        }
    }
}

$sisa_slot = 0;
foreach ($slots as $sl) { if (!$sl['is_booked']) $sisa_slot++; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking <?= htmlspecialchars($lapangan['nama_lapangan']) ?> — LapanganKu</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --g900:#0a2e1a;--g800:#0f3d22;--g700:#145229;--g600:#1a6b3f;--g500:#22874f;
    --g400:#2da863;--g300:#4dc47e;--g200:#86dba8;--g100:#c0f0d4;--g50:#e8faf0;
    --white:#ffffff;
    --gray-50:#f7f9f8;--gray-100:#eef2f0;--gray-200:#d8e2dd;--gray-300:#b4c9bf;
    --gray-400:#88a097;--gray-600:#3e5f52;--gray-800:#1a2e26;
    --red-bg:#fff1f0;--red-border:#ffc0bc;--red-text:#c0392b;
    --amber-bg:#fffbeb;--amber-border:#fde68a;--amber-text:#92600a;
}
body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--gray-50); min-height:100vh; color:var(--gray-800); }
.navbar { background:var(--g900); padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
.navbar-brand { display:flex; align-items:center; gap:12px; text-decoration:none; }
.navbar-logo { width:38px; height:38px; background:var(--g400); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; }
.navbar-name { font-size:18px; font-weight:700; color:var(--white); }
.nav-back { height:36px; padding:0 16px; background:rgba(255,255,255,.08); color:var(--g200); border:1px solid rgba(255,255,255,.15); border-radius:8px; font-family:inherit; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:6px; transition:background .2s,color .2s; }
.nav-back:hover { background:rgba(255,255,255,.14); color:var(--white); }
.layout { max-width:900px; margin:0 auto; padding:40px 24px; display:grid; grid-template-columns:1fr 360px; gap:28px; align-items:start; }
.card { background:var(--white); border:1px solid var(--gray-200); border-radius:20px; padding:32px; }
.card-title { font-size:20px; font-weight:800; color:var(--gray-800); letter-spacing:-.4px; margin-bottom:24px; }
.alert { border-radius:12px; padding:13px 16px; font-size:14px; margin-bottom:22px; display:flex; align-items:flex-start; gap:10px; }
.alert-error { background:var(--red-bg); border:1px solid var(--red-border); color:var(--red-text); }
.alert-warning { background:var(--amber-bg); border:1px solid var(--amber-border); color:var(--amber-text); }
.form-group { margin-bottom:22px; }
.form-label { display:block; font-size:11px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:.8px; margin-bottom:9px; }
.form-control { width:100%; height:48px; padding:0 16px; border:1.5px solid var(--gray-200); border-radius:8px; font-family:inherit; font-size:15px; color:var(--gray-800); background:var(--gray-50); transition:border-color .2s,box-shadow .2s; outline:none; }
.form-control:focus { border-color:var(--g500); background:var(--white); box-shadow:0 0 0 3px rgba(34,135,79,.1); }
textarea.form-control { height:auto; padding:13px 16px; resize:vertical; }
.date-picker-row { display:grid; grid-template-columns:80px 1fr 90px; gap:8px; }
.date-sel { padding:0 10px; cursor:pointer; }
.date-error { font-size:12px; color:var(--red-text); margin-top:6px; }
.avail-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.avail-label { font-size:11px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:.8px; }
.avail-count { font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; }
.avail-count.ok  { background:var(--g50);  color:var(--g600); }
.avail-count.low { background:var(--amber-bg); color:var(--amber-text); }
.avail-count.full{ background:var(--red-bg);   color:var(--red-text); }
.slot-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
.slot-radio { display:none; }
.slot-label { display:flex; align-items:center; gap:12px; border:1.5px solid var(--gray-200); border-radius:8px; padding:12px 14px; cursor:pointer; background:var(--gray-50); transition:border-color .18s,background .18s,transform .12s; position:relative; }
.slot-label:hover:not(.booked) { border-color:var(--g300); background:var(--g50); transform:translateY(-1px); }
.slot-radio:checked + .slot-label { border-color:var(--g500); background:var(--g50); box-shadow:0 0 0 3px rgba(34,135,79,.08); }
.slot-label.booked { opacity:.55; cursor:not-allowed; background:var(--gray-100); border-color:var(--gray-200); transform:none !important; }
.slot-icon { font-size:22px; flex-shrink:0; }
.slot-info { flex:1; min-width:0; }
.slot-name { font-size:13px; font-weight:700; color:var(--gray-800); }
.slot-time { font-size:12px; color:var(--gray-400); margin-top:1px; }
.slot-tag { display:inline-block; margin-top:5px; font-size:10px; font-weight:700; letter-spacing:.3px; padding:2px 8px; border-radius:20px; }
.slot-tag.booked-tag { background:#fee2e2; color:#b91c1c; }
.slot-tag.avail-tag  { background:var(--g50); color:var(--g600); }
.slot-check { width:18px; height:18px; border-radius:50%; background:var(--g500); border:none; display:none; align-items:center; justify-content:center; flex-shrink:0; }
.slot-check::after { content:'✓'; color:#fff; font-size:11px; font-weight:700; }
.slot-radio:checked + .slot-label .slot-check { display:flex; }
.slot-tip { text-align:center; padding:28px 16px; font-size:13px; color:var(--gray-400); border:1.5px dashed var(--gray-200); border-radius:8px; }
.btn-book { width:100%; height:50px; background:var(--g500); color:var(--white); border:none; border-radius:8px; font-family:inherit; font-size:16px; font-weight:700; cursor:pointer; transition:background .2s,transform .1s; margin-top:6px; }
.btn-book:hover { background:var(--g400); }
.btn-book:active { transform:scale(.98); }
.btn-book:disabled { background:var(--gray-300); cursor:not-allowed; }
.btn-cancel { display:block; width:100%; height:44px; margin-top:10px; background:transparent; color:var(--gray-400); border:1.5px solid var(--gray-200); border-radius:8px; font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; text-align:center; line-height:42px; transition:border-color .2s,color .2s; }
.btn-cancel:hover { border-color:var(--gray-400); color:var(--gray-600); }
.summary-card { background:var(--g800); border-radius:20px; padding:28px; color:var(--white); position:sticky; top:80px; }
.summary-img { width:100%; height:150px; object-fit:cover; border-radius:12px; margin-bottom:20px; }
.summary-badge { display:inline-block; background:var(--g500); color:var(--white); font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; margin-bottom:12px; }
.summary-name { font-size:20px; font-weight:800; letter-spacing:-.3px; margin-bottom:6px; }
.summary-price { font-size:28px; font-weight:800; color:var(--g300); }
.summary-price span { font-size:14px; font-weight:500; color:var(--g200); }
.divider { border:none; border-top:1px solid rgba(255,255,255,.12); margin:20px 0; }
.summary-row { display:flex; justify-content:space-between; align-items:flex-start; font-size:13px; margin-bottom:11px; gap:12px; }
.summary-row-label { color:var(--g200); flex-shrink:0; }
.summary-row-val { font-weight:600; text-align:right; }
.summary-row-val.accent { color:var(--g300); }
.summary-slot-live { background:rgba(255,255,255,.06); border-radius:8px; padding:10px 12px; font-size:13px; color:var(--g200); margin-top:8px; min-height:40px; }
.summary-note { font-size:12px; color:var(--g300); margin-top:16px; line-height:1.6; border-top:1px solid rgba(255,255,255,.08); padding-top:14px; }
.steps { display:flex; align-items:center; margin-bottom:28px; }
.step { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--gray-400); }
.step.done { color:var(--g500); }
.step.active { color:var(--gray-800); }
.step-dot { width:22px; height:22px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; background:var(--gray-200); color:var(--gray-400); }
.step.done .step-dot { background:var(--g500); color:var(--white); }
.step.active .step-dot { background:var(--gray-800); color:var(--white); }
.step-line { flex:1; height:1px; background:var(--gray-200); margin:0 8px; }
@media (max-width:700px) {
    .layout { grid-template-columns:1fr; padding:20px 16px; gap:20px; }
    .summary-card { position:static; }
    .slot-grid { grid-template-columns:1fr; }
    .card { padding:22px 18px; }
}
</style>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">
        <div class="navbar-logo">⚽</div>
        <span class="navbar-name">LapanganKu</span>
    </a>
    <a href="lapangan.php" class="nav-back">← Pilih Lapangan</a>
</nav>

<div class="layout">
    <div>
        <div class="card">
            <div class="steps">
                <div class="step done"><div class="step-dot">✓</div> Pilih Lapangan</div>
                <div class="step-line"></div>
                <div class="step active"><div class="step-dot">2</div> Detail Booking</div>
                <div class="step-line"></div>
                <div class="step"><div class="step-dot">3</div> Konfirmasi</div>
            </div>

            <h2 class="card-title">📅 Detail Booking</h2>

            <?php if ($error): ?>
            <div class="alert alert-error"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>

            <form method="POST" id="bookingForm">

                <div class="form-group">
                    <label class="form-label">Tanggal Main</label>
                    <div class="date-picker-row">
                        <select name="tgl_hari" id="tgl_hari" class="form-control date-sel" required>
                            <option value="">Hari</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>" <?= $sel_hari === $d ? 'selected' : '' ?>><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
                            <?php endfor; ?>
                        </select>
                        <?php $nama_bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; ?>
                        <select name="tgl_bulan" id="tgl_bulan" class="form-control date-sel" required>
                            <option value="">Bulan</option>
                            <?php foreach ($nama_bulan as $i => $nb): $val = $i + 1; ?>
                            <option value="<?= $val ?>" <?= $sel_bulan === $val ? 'selected' : '' ?>><?= $nb ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tgl_tahun" id="tgl_tahun" class="form-control date-sel" required>
                            <option value="">Tahun</option>
                            <?php for ($y = $tahun_min; $y <= $tahun_max; $y++): ?>
                            <option value="<?= $y ?>" <?= $sel_tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php if ($sel_hari && $sel_bulan && $sel_tahun && !$tanggal_dipilih): ?>
                    <p class="date-error">Tanggal tidak valid. Periksa kembali.</p>
                    <?php endif; ?>
                    <?php if ($tanggal_dipilih && $tanggal_dipilih < date('Y-m-d')): ?>
                    <p class="date-error">Tanggal tidak boleh di masa lalu.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <?php if ($tanggal_dipilih): ?>
                        <div class="avail-bar">
                            <span class="avail-label">Pilih Slot Waktu</span>
                            <?php
                                $cls = $sisa_slot === 0 ? 'full' : ($sisa_slot <= 2 ? 'low' : 'ok');
                                $txt = $sisa_slot === 0 ? 'Semua terpesan' : "$sisa_slot slot tersedia";
                            ?>
                            <span class="avail-count <?= $cls ?>"><?= $txt ?></span>
                        </div>
                        <?php if (empty($slots)): ?>
                        <div class="alert alert-warning" style="margin-bottom:14px">
                            <span>📅</span><span>Belum ada slot yang dibuka admin untuk tanggal ini. Coba tanggal lain.</span>
                        </div>
                        <?php elseif ($sisa_slot === 0): ?>
                        <div class="alert alert-warning" style="margin-bottom:14px">
                            <span>📅</span><span>Semua slot pada tanggal ini sudah penuh. Coba pilih tanggal lain.</span>
                        </div>
                        <?php endif; ?>
                        <div class="slot-grid">
                            <?php
                            $prev_slot = isset($_POST['id_slot']) ? intval($_POST['id_slot']) : null;
                            foreach ($slots as $id => $slot):
                                $is_booked  = $slot['is_booked'];
                                $is_checked = ($prev_slot === $id && !$is_booked);
                            ?>
                            <div>
                                <input type="radio" name="id_slot" id="slot_<?= $id ?>" class="slot-radio" value="<?= $id ?>"
                                    <?= $is_booked  ? 'disabled' : '' ?>
                                    <?= $is_checked ? 'checked'  : '' ?>>
                                <label for="slot_<?= $id ?>" class="slot-label <?= $is_booked ? 'booked' : '' ?>">
                                    <span class="slot-icon"><?= $slot['icon'] ?></span>
                                    <div class="slot-info">
                                        <div class="slot-name"><?= $slot['label'] ?></div>
                                        <div class="slot-time"><?= htmlspecialchars($slot['jam']) ?></div>
                                        <?php if ($is_booked): ?>
                                        <span class="slot-tag booked-tag">Sudah dipesan</span>
                                        <?php else: ?>
                                        <span class="slot-tag avail-tag">Tersedia</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="slot-check"></div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <label class="form-label">Pilih Slot Waktu</label>
                        <div class="slot-tip">📆 Pilih tanggal terlebih dahulu untuk melihat ketersediaan slot</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan <span style="color:var(--gray-400);font-weight:400;text-transform:none;letter-spacing:0">(opsional)</span></label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: sewa rompi, minta bola tambahan..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="book_now" class="btn-book" id="btnBook"
                    <?= (($sisa_slot === 0 || empty($slots)) && $tanggal_dipilih) ? 'disabled' : '' ?>>
                    Konfirmasi Booking
                </button>
                <a href="lapangan.php" class="btn-cancel">Batal</a>
            </form>
        </div>
    </div>

    <div>
        <div class="summary-card">
            <img src="https://images.unsplash.com/photo-1575361204480-aadea25e6e68?auto=format&fit=crop&w=600&q=80" class="summary-img" alt="Foto lapangan">
            <span class="summary-badge">✅ Tersedia</span>
            <div class="summary-name"><?= htmlspecialchars($lapangan['nama_lapangan']) ?></div>
            <div class="summary-price">Rp <?= number_format($lapangan['harga_per_jam'], 0, ',', '.') ?><span>/jam</span></div>
            <hr class="divider">
            <div class="summary-row">
                <span class="summary-row-label">Dipesan oleh</span>
                <span class="summary-row-val"><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-row-label">Tanggal</span>
                <span class="summary-row-val" id="summaryTanggal"><?= $tanggal_dipilih ? date('d M Y', strtotime($tanggal_dipilih)) : '—' ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-row-label">Slot dipilih</span>
                <span class="summary-row-val" id="summarySlotLabel">—</span>
            </div>
            <div class="summary-row">
                <span class="summary-row-label">Status awal</span>
                <span class="summary-row-val">Menunggu konfirmasi</span>
            </div>
            <hr class="divider">
            <div class="summary-row">
                <span class="summary-row-label">Total tagihan</span>
                <span class="summary-row-val accent">Rp <?= number_format($lapangan['harga_per_jam'], 0, ',', '.') ?></span>
            </div>
            <div class="summary-slot-live" id="summarySlotBox">Belum ada slot dipilih</div>
            <p class="summary-note">💡 Booking akan dikonfirmasi admin. Pantau status di halaman <strong>Riwayat</strong>.</p>
        </div>
    </div>
</div>

<script>
const slotData = <?= json_encode(array_map(function($sl){ return ['label'=>$sl['label'],'jam'=>$sl['jam'],'icon'=>$sl['icon']]; }, $slots)) ?>;

function cekAutoSubmit() {
    const h = document.getElementById('tgl_hari').value;
    const b = document.getElementById('tgl_bulan').value;
    const y = document.getElementById('tgl_tahun').value;
    if (h && b && y) document.getElementById('bookingForm').submit();
}
['tgl_hari','tgl_bulan','tgl_tahun'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', cekAutoSubmit);
});

document.querySelectorAll('.slot-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (!this.checked || this.disabled) return;
        const id = this.value;
        const slot = slotData[id];
        if (!slot) return;
        document.getElementById('summarySlotLabel').textContent = slot.label + ' (' + slot.jam + ')';
        document.getElementById('summarySlotBox').innerHTML = '<strong>' + slot.icon + ' ' + slot.label + '</strong> · ' + slot.jam;
    });
});

(function() {
    const checked = document.querySelector('.slot-radio:checked');
    if (checked) {
        const id = checked.value; const slot = slotData[id];
        if (slot) {
            document.getElementById('summarySlotLabel').textContent = slot.label + ' (' + slot.jam + ')';
            document.getElementById('summarySlotBox').innerHTML = '<strong>' + slot.icon + ' ' + slot.label + '</strong> · ' + slot.jam;
        }
    }
})();

const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
function updateSummaryTanggal() {
    const h = document.getElementById('tgl_hari').value;
    const b = document.getElementById('tgl_bulan').value;
    const y = document.getElementById('tgl_tahun').value;
    const el = document.getElementById('summaryTanggal');
    el.textContent = (h && b && y) ? String(h).padStart(2,'0') + ' ' + namaBulan[parseInt(b)-1] + ' ' + y : '—';
}
['tgl_hari','tgl_bulan','tgl_tahun'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', updateSummaryTanggal);
});
updateSummaryTanggal();
</script>
</body>
</html>