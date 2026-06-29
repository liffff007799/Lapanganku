<?php

session_start();
include __DIR__ . '/koneksi.php';

if (!isset($koneksi) || empty($koneksi)) {
    die('Koneksi database tidak tersedia.');
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_lapangan = $_GET['id'];

$data     = mysqli_query($koneksi, "SELECT * FROM lapangan WHERE id='$id_lapangan'");
$lapangan = mysqli_fetch_assoc($data);

if (!$lapangan) {
    die("Lapangan tidak ditemukan.");
}

// ─── Ambil semua booking aktif di lapangan ini (untuk ditampilkan ke user) ───
$booking_aktif = mysqli_query(
    $koneksi,
    "SELECT tanggal, jam_mulai, jam_selesai, status 
     FROM pemesanan 
     WHERE lapangan_id = '$id_lapangan' 
       AND status IN ('Menunggu', 'Disetujui')
     ORDER BY tanggal, jam_mulai"
);

$jadwal_terpakai = [];
while ($b = mysqli_fetch_assoc($booking_aktif)) {
    $jadwal_terpakai[] = $b;
}

// ─── Proses form booking ───────────────────────────────────────────────────
$error   = '';
$success = false;

if (isset($_POST['simpan'])) {

    $user_id     = $_SESSION['id'];
    $tanggal     = $_POST['tanggal'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // 1. Validasi: jam selesai harus lebih dari jam mulai
    if ($jam_selesai <= $jam_mulai) {
        $error = 'Jam selesai harus lebih besar dari jam mulai.';
    } else {

        // 2. CEK TABRAKAN JADWAL
        $cek_tabrakan = mysqli_query(
            $koneksi,
            "SELECT id, jam_mulai, jam_selesai 
             FROM pemesanan
             WHERE lapangan_id = '$id_lapangan'
               AND tanggal     = '$tanggal'
               AND status      IN ('Menunggu', 'Disetujui')
               AND '$jam_mulai'   < jam_selesai
               AND '$jam_selesai' > jam_mulai"
        );

        if (mysqli_num_rows($cek_tabrakan) > 0) {
            $konflik = mysqli_fetch_assoc($cek_tabrakan);
            $error = "Jadwal bertabrakan! Lapangan sudah dipesan pada "
                   . date('H:i', strtotime($konflik['jam_mulai']))
                   . " – "
                   . date('H:i', strtotime($konflik['jam_selesai']))
                   . " di tanggal tersebut.";
        } else {
            // 3. Aman — simpan booking
            $insert = mysqli_query(
                $koneksi,
                "INSERT INTO pemesanan
                    (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, status)
                 VALUES
                    ('$user_id', '$id_lapangan', '$tanggal', '$jam_mulai', '$jam_selesai', 'Menunggu')"
            );

            if ($insert) {
                $success = true;
            } else {
                $error = 'Booking gagal disimpan: ' . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Lapangan — LapanganKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background:
                linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
                url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
        }

        .booking-card {
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 40px;
        }

        .lapangan-title { font-size: 32px; font-weight: bold; }
        .price { font-size: 26px; color: #00ff88; font-weight: bold; }
        .form-control { border-radius: 12px; height: 50px; }
        .btn-book { height: 50px; border-radius: 12px; font-weight: bold; }

        /* ── Jadwal terpakai ── */
        .jadwal-section {
            background: rgba(0,0,0,0.30);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .jadwal-section h6 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aed9c0;
            margin-bottom: 10px;
        }
        .slot-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .slot-item:last-child { border-bottom: none; }
        .slot-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .dot-waiting  { background: #ffc107; }
        .dot-approved { background: #00ff88; }
        .slot-date { color: #aaa; font-size: 13px; min-width: 90px; }
        .slot-jam  { font-weight: 600; }
        .slot-status { font-size: 12px; color: #aaa; margin-left: auto; }

        .no-slot { color: #aaa; font-size: 14px; }

        /* ── Error & success ── */
        .alert-clash {
            background: rgba(220, 53, 69, 0.20);
            border: 1px solid rgba(220, 53, 69, 0.50);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success-custom {
            background: rgba(0, 200, 100, 0.15);
            border: 1px solid rgba(0, 200, 100, 0.40);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* ── Date filter badge ── */
        #tanggal-filter-info {
            font-size: 13px;
            color: #aed9c0;
            margin-bottom: 8px;
            display: none;
        }
    </style>
</head>
<body>

<?php if ($success): ?>
<script>
    alert('Booking berhasil dibuat! Menunggu konfirmasi admin.');
    window.location = 'riwayat.php';
</script>
<?php endif; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="booking-card">

                <div class="text-center mb-4">
                    <h1>⚽ Booking Lapangan</h1>
                    <p>Lengkapi detail bookingmu</p>
                </div>

                <div class="mb-4 text-center">
                    <div class="lapangan-title">
                        <?= htmlspecialchars($lapangan['nama_lapangan']) ?>
                    </div>
                    <div class="price">
                        Rp <?= number_format($lapangan['harga']) ?>/jam
                    </div>
                </div>

                <!-- ── JADWAL SUDAH TERPAKAI ──────────────────────── -->
                <div class="jadwal-section">
                    <h6>🔒 Jadwal Sudah Terpesan</h6>

                    <?php if (empty($jadwal_terpakai)): ?>
                        <p class="no-slot mb-0">Belum ada jadwal terpesan — semua slot bebas!</p>

                    <?php else: ?>
                        <div id="tanggal-filter-info">
                            Menampilkan jadwal untuk tanggal: <strong id="label-tanggal"></strong>
                        </div>

                        <div id="list-semua">
                            <?php foreach ($jadwal_terpakai as $j): ?>
                            <div class="slot-item" data-tanggal="<?= $j['tanggal'] ?>">
                                <span class="slot-dot <?= ($j['status'] ?? '') === 'Disetujui' ? 'dot-approved' : 'dot-waiting' ?>"></span>
                                <span class="slot-date"><?= date('d M Y', strtotime($j['tanggal'])) ?></span>
                                <span class="slot-jam">
                                    <?= date('H:i', strtotime($j['jam_mulai'])) ?>
                                    –
                                    <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                                </span>
                                <span class="slot-status"><?= htmlspecialchars($j['status'] ?? '') ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="no-slot-tanggal" class="no-slot" style="display:none;">
                            Tidak ada booking di tanggal ini ✅
                        </div>
                    <?php endif; ?>
                </div>
                <!-- ──────────────────────────────────────────────── -->

                <!-- Error tabrakan -->
                <?php if ($error): ?>
                <div class="alert-clash">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">

                    <div class="mb-3">
                        <label>Tanggal Bermain</label>
                        <input type="date" name="tanggal" id="tanggalInput"
                               class="form-control"
                               min="<?= date('Y-m-d') ?>"
                               value="<?= isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : '' ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label>Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jamMulai"
                               class="form-control"
                               value="<?= isset($_POST['jam_mulai']) ? htmlspecialchars($_POST['jam_mulai']) : '' ?>"
                               required>
                    </div>

                    <div class="mb-4">
                        <label>Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jamSelesai"
                               class="form-control"
                               value="<?= isset($_POST['jam_selesai']) ? htmlspecialchars($_POST['jam_selesai']) : '' ?>"
                               required>
                    </div>

                    <!-- Preview durasi & estimasi harga -->
                    <div id="preview-harga" class="mb-3"
                         style="display:none; background:rgba(0,255,136,0.10); border:1px solid rgba(0,255,136,0.25); border-radius:12px; padding:12px 16px; font-size:14px;">
                        ⏱ Durasi: <strong id="preview-durasi"></strong> jam &nbsp;|&nbsp;
                        💰 Estimasi: <strong id="preview-total"></strong>
                    </div>

                    <!-- Peringatan JS (tabrakan sisi klien) -->
                    <div id="warning-clash"
                         style="display:none; background:rgba(255,193,7,0.15); border:1px solid rgba(255,193,7,0.4); border-radius:12px; padding:12px 16px; font-size:14px; margin-bottom:16px;">
                        ⚠️ <strong>Peringatan:</strong> Jam yang kamu pilih mungkin bertabrakan dengan booking yang sudah ada di tanggal ini.
                    </div>

                    <button type="submit" name="simpan" class="btn btn-success btn-book w-100">
                        Konfirmasi Booking
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="lapangan.php" class="btn btn-light">Kembali</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// ── DATA JADWAL TERPAKAI (dari PHP ke JS) ──────────────────────
const jadwalDB = <?= json_encode($jadwal_terpakai) ?>;
const hargaPerJam = <?= (int)$lapangan['harga'] ?>;

// ── FILTER JADWAL SAAT TANGGAL BERUBAH ────────────────────────
const tanggalInput   = document.getElementById('tanggalInput');
const jamMulaiInput  = document.getElementById('jamMulai');
const jamSelesaiInput = document.getElementById('jamSelesai');
const filterInfo     = document.getElementById('tanggal-filter-info');
const labelTanggal   = document.getElementById('label-tanggal');
const noSlot         = document.getElementById('no-slot-tanggal');
const listSemua      = document.getElementById('list-semua');

function filterJadwal() {
    const tgl = tanggalInput.value;
    if (!tgl || !listSemua) return;

    const items = listSemua.querySelectorAll('.slot-item');
    let visible = 0;

    items.forEach(item => {
        if (item.dataset.tanggal === tgl) {
            item.style.display = 'flex';
            visible++;
        } else {
            item.style.display = 'none';
        }
    });

    if (filterInfo) {
        filterInfo.style.display = 'block';
        const d = new Date(tgl + 'T00:00:00');
        labelTanggal.textContent = d.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'});
    }
    if (noSlot) noSlot.style.display = visible === 0 ? 'block' : 'none';

    checkClash();
}

// ── CEK TABRAKAN DI SISI KLIEN (peringatan dini) ──────────────
function checkClash() {
    const tgl     = tanggalInput.value;
    const mulai   = jamMulaiInput.value;
    const selesai = jamSelesaiInput.value;
    const warning = document.getElementById('warning-clash');

    if (!tgl || !mulai || !selesai || selesai <= mulai) {
        if (warning) warning.style.display = 'none';
        return;
    }

    const ada = jadwalDB.some(j => {
        return j.tanggal === tgl
            && mulai   < j.jam_selesai
            && selesai > j.jam_mulai;
    });

    if (warning) warning.style.display = ada ? 'block' : 'none';
}

// ── PREVIEW DURASI & ESTIMASI HARGA ───────────────────────────
function updatePreview() {
    const mulai   = jamMulaiInput.value;
    const selesai = jamSelesaiInput.value;
    const box     = document.getElementById('preview-harga');
    const durEl   = document.getElementById('preview-durasi');
    const totEl   = document.getElementById('preview-total');

    if (!mulai || !selesai || selesai <= mulai) {
        if (box) box.style.display = 'none';
        return;
    }

    const [hM, mM] = mulai.split(':').map(Number);
    const [hS, mS] = selesai.split(':').map(Number);
    const durMenit = (hS * 60 + mS) - (hM * 60 + mM);
    const durJam   = durMenit / 60;
    const total    = Math.ceil(durJam) * hargaPerJam;

    if (durMenit > 0) {
        durEl.textContent = durJam % 1 === 0 ? durJam : durJam.toFixed(1);
        totEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }

    checkClash();
}

// ── EVENT LISTENERS ────────────────────────────────────────────
tanggalInput.addEventListener('change', filterJadwal);
jamMulaiInput.addEventListener('change', updatePreview);
jamSelesaiInput.addEventListener('change', updatePreview);

// Jalankan saat load jika sudah ada nilai (misal setelah error POST)
if (tanggalInput.value) filterJadwal();
if (jamMulaiInput.value || jamSelesaiInput.value) updatePreview();
</script>

</body>
</html>