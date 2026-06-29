<?php
include 'koneksi.php';

$error = '';

if (isset($_POST['register'])) {
    // Menghilangkan spasi di awal/akhir input
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $no_hp    = trim($_POST['no_hp']);
    $password = $_POST['password'];

    // 1. Validasi Sisi Server (Backend Validation)
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Nama, Email, dan Password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal harus 8 karakter.';
    } else {
        // Enkripsi password menggunakan Bcrypt
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);

        /** @var mysqli $koneksi */ 
        // Baris di atas adalah perintah khusus agar teks editor (VS Code) tahu bahwa $koneksi itu ada dan tidak memunculkan garis merah.

        // 2. Cek apakah email sudah terdaftar menggunakan Prepared Statement (Sangat Aman)
        $cek_stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek_stmt, "s", $email);
        mysqli_stmt_execute($cek_stmt);
        mysqli_stmt_store_result($cek_stmt);

        if (mysqli_stmt_num_rows($cek_stmt) > 0) {
            $error = 'Email sudah terdaftar. Coba login atau gunakan email lain.';
            mysqli_stmt_close($cek_stmt);
        } else {
            mysqli_stmt_close($cek_stmt);

            // 3. Proses Insert data baru menggunakan Prepared Statement
            $insert_stmt = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, 'pelanggan')");
            mysqli_stmt_bind_param($insert_stmt, "ssss", $nama, $email, $password_hashed, $no_hp);
            $query = mysqli_stmt_execute($insert_stmt);

            if ($query) {
                mysqli_stmt_close($insert_stmt);
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = 'Pendaftaran gagal, silakan coba lagi beberapa saat lagi.';
                mysqli_stmt_close($insert_stmt);
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
    <title>Daftar Akun — LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-900: #0a2e1a; --green-800: #0f3d22; --green-600: #1a6b3f;
            --green-500: #22874f; --green-400: #2da863; --green-300: #4dc47e;
            --green-200: #86dba8; --green-100: #c0f0d4; --green-50: #e8faf0;
            --white: #ffffff; --gray-50: #f8faf9; --gray-100: #f0f4f2;
            --gray-200: #dce7e1; --gray-400: #8fa89a; --gray-600: #4a6358; --gray-800: #1e2e28;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--green-900);
        }
        .left-panel {
            position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 48px; background: var(--green-800);
        }
        .left-panel::before {
            content: ''; position: absolute; top: -80px; right: -80px;
            width: 360px; height: 360px; background: var(--green-600);
            border-radius: 50%; opacity: 0.25;
        }
        .brand { position: relative; z-index: 1; }
        .brand-icon {
            width: 52px; height: 52px; background: var(--green-400);
            border-radius: 14px; display: flex; align-items: center;
            justify-content: center; font-size: 26px; margin-bottom: 20px;
        }
        .brand-name { font-size: 28px; font-weight: 700; color: var(--white); letter-spacing: -0.5px; }
        .brand-tagline { font-size: 14px; color: var(--green-200); margin-top: 6px; }
        .steps { position: relative; z-index: 1; }
        .step { display: flex; gap: 16px; margin-bottom: 28px; }
        .step-num {
            width: 36px; height: 36px; background: var(--green-600); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--green-100); font-size: 14px; font-weight: 700; flex-shrink: 0;
        }
        .step-text h4 { color: var(--white); font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .step-text p { color: var(--green-200); font-size: 13px; line-height: 1.5; }
        .right-panel {
            display: flex; align-items: center; justify-content: center;
            padding: 48px; background: var(--white); overflow-y: auto;
        }
        .register-card { width: 100%; max-width: 420px; }
        .register-header { margin-bottom: 32px; }
        .register-title { font-size: 26px; font-weight: 700; color: var(--gray-800); letter-spacing: -0.5px; }
        .register-sub { font-size: 14px; color: var(--gray-400); margin-top: 6px; }
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block; font-size: 13px; font-weight: 600; color: var(--gray-600);
            margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%; height: 48px; padding: 0 16px;
            border: 1.5px solid var(--gray-200); border-radius: 10px;
            font-family: inherit; font-size: 15px; color: var(--gray-800);
            background: var(--gray-50); transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        .form-control:focus {
            border-color: var(--green-500); background: var(--white);
            box-shadow: 0 0 0 3px rgba(34, 135, 79, 0.12);
        }
        .error-box {
            background: #fff1f0; border: 1px solid #ffc0bc; border-radius: 10px;
            padding: 12px 16px; font-size: 14px; color: #c0392b; margin-bottom: 20px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-register {
            width: 100%; height: 50px; background: var(--green-500); color: var(--white);
            border: none; border-radius: 10px; font-family: inherit; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.1s; margin-top: 8px;
        }
        .btn-register:hover { background: var(--green-400); }
        .btn-register:active { transform: scale(0.99); }
        .login-link { text-align: center; margin-top: 24px; font-size: 14px; color: var(--gray-400); }
        .login-link a { color: var(--green-600); font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
        .hint { font-size: 12px; color: var(--gray-400); margin-top: 6px; }
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 32px 24px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="left-panel">
    <div class="brand">
        <div class="brand-icon">⚽</div>
        <div class="brand-name">LapanganKu</div>
        <div class="brand-tagline">Platform booking lapangan futsal modern</div>
    </div>
    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">
                <h4>Buat akun gratis</h4>
                <p>Daftar hanya butuh beberapa detik. Tidak perlu kartu kredit.</p>
            </div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-text">
                <h4>Pilih lapangan & jam main</h4>
                <p>Telusuri lapangan tersedia dan pilih slot waktu yang cocok.</p>
            </div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-text">
                <h4>Konfirmasi & langsung main</h4>
                <p>Admin konfirmasi booking kamu dan kamu siap bermain!</p>
            </div>
        </div>
    </div>
    <p style="color:var(--green-300);font-size:13px;position:relative;z-index:1;">
        Sudah bergabung dengan ratusan pemain aktif 🎉
    </p>
</div>

<div class="right-panel">
    <div class="register-card">
        <div class="register-header">
            <h2 class="register-title">Buat akun baru</h2>
            <p class="register-sub">Isi data di bawah untuk mulai booking</p>
        </div>

        <?php if ($error): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control"
                       placeholder="Contoh: Ahmad Rizky" required
                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="kamu@email.com" required
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control"
                           placeholder="08xxxxxxxxxx" maxlength="15"
                           value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min. 8 karakter" required minlength="8">
                </div>
            </div>

            <p class="hint">Dengan mendaftar, kamu menyetujui ketentuan layanan LapanganKu.</p>

            <button type="submit" name="register" class="btn-register">
                Daftar Sekarang — Gratis
            </button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>
</div>

</body>
</html>