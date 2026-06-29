<?php
session_start();
include 'koneksi.php'; // Pastikan path file ini benar

// Pastikan variabel $koneksi terdefinisi
global $koneksi;

if (!isset($_SESSION['id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: lapangan.php"); exit; }

$id_lapangan = intval($_GET['id']);

// Ambil data lapangan
$query = mysqli_query($koneksi, "SELECT * FROM lapangan WHERE id_lapangan = '$id_lapangan'");
if (!$query || mysqli_num_rows($query) === 0) { die("Lapangan tidak ditemukan."); }
$lapangan = mysqli_fetch_assoc($query);

// Proses Booking
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_booking'])) {
    $id_slot = intval($_POST['slot_id'] ?? 0);
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal'] ?? '');
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    
    if (empty($tanggal) || $id_slot == 0) {
        $error = "Silakan pilih tanggal dan slot waktu.";
    } else {
        $user_id = $_SESSION['id'];
        $harga   = $lapangan['harga_per_jam'];
        
        $sql = "INSERT INTO pemesanan (id_user, id_lapangan, id_slot, tanggal, total_harga, status, catatan) 
                VALUES ('$user_id', '$id_lapangan', '$id_slot', '$tanggal', '$harga', 'pending', '$catatan')";
        
        if (mysqli_query($koneksi, $sql)) {
            header("Location: riwayat.php?success=1");
            exit;
        } else {
            $error = "Database Error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking <?= htmlspecialchars($lapangan['nama_lapangan']) ?></title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 10px; max-width: 600px; margin: auto; }
        .slot-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; }
        .slot-item { padding: 15px; border: 1px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; }
        .error { color: #a94442; background: #f2dede; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Booking: <?= htmlspecialchars($lapangan['nama_lapangan']) ?></h2>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <label>Tanggal Booking:</label>
            <input type="date" name="tanggal" required class="form-control" style="width:100%; margin-bottom:15px;">
            
            <label>Pilih Jam:</label>
            <div class="slot-grid">
                <?php 
                // Menggunakan DISTINCT untuk menghindari error sql_mode
                $q = mysqli_query($koneksi, "SELECT DISTINCT id_slot, jam_mulai, jam_selesai FROM jadwal_slot ORDER BY jam_mulai ASC");
                if ($q) {
                    while ($s = mysqli_fetch_assoc($q)) {
                        echo "<label class='slot-item'>
                                <input type='radio' name='slot_id' value='{$s['id_slot']}'> 
                                <br>".substr($s['jam_mulai'], 0, 5)."
                              </label>";
                    }
                }
                ?>
            </div>
            
            <textarea name="catatan" placeholder="Catatan tambahan..." style="width:100%; margin-bottom:15px;"></textarea>
            <button type="submit" name="proses_booking" style="width:100%; padding:12px; background:green; color:white; border:none; cursor:pointer;">Konfirmasi Booking</button>
        </form>
    </div>
</body>
</html>