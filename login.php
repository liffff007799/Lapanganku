<?php
session_start();

/** @var mysqli $koneksi */
include 'koneksi.php';

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        if (password_verify($password, $data['password'])) {
            $_SESSION['id']   = $data['id_user'];
            $_SESSION['nama'] = $data['nama'];
            $_SESSION['role'] = $data['role'];

            if ($data['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = 'Password yang kamu masukkan salah.';
        }
    } else {
        $error = 'Email tidak ditemukan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — LapanganKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-900: #0a2e1a;
            --green-800: #0f3d22;
            --green-700: #155230;
            --green-600: #1a6b3f;
            --green-500: #22874f;
            --green-400: #2da863;
            --green-300: #4dc47e;
            --green-200: #86dba8;
            --green-100: #c0f0d4;
            --green-50:  #e8faf0;
            --white: #ffffff;
            --gray-50: #f8faf9;
            --gray-100: #f0f4f2;
            --gray-200: #dce7e1;
            --gray-400: #8fa89a;
            --gray-600: #4a6358;
            --gray-800: #1e2e28;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--green-900);
        }

        /* LEFT PANEL */
        .left-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            background: var(--green-800);
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 360px; height: 360px;
            background: var(--green-600);
            border-radius: 50%;
            opacity: 0.25;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 280px; height: 280px;
            background: var(--green-500);
            border-radius: 50%;
            opacity: 0.15;
        }

        .brand {
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 52px; height: 52px;
            background: var(--green-400);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 14px;
            color: var(--green-200);
            margin-top: 6px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-headline {
            font-size: 38px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.2;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .hero-headline span {
            color: var(--green-300);
        }

        .hero-sub {
            font-size: 16px;
            color: var(--green-200);
            line-height: 1.6;
        }

        .feature-list {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--green-100);
            font-size: 14px;
        }

        .feature-dot {
            width: 8px; height: 8px;
            background: var(--green-400);
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* RIGHT PANEL */
        .right-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background: var(--white);
        }

        .login-card {
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            margin-bottom: 36px;
        }

        .login-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--gray-800);
            letter-spacing: -0.5px;
        }

        .login-sub {
            font-size: 14px;
            color: var(--gray-400);
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            border: 1.5px solid var(--gray-200);
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            color: var(--gray-800);
            background: var(--gray-50);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--green-500);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(34, 135, 79, 0.12);
        }

        .error-box {
            background: #fff1f0;
            border: 1px solid #ffc0bc;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            color: #c0392b;
            margin-bottom: 20px;
        }

        .btn-login {
            width: 100%;
            height: 50px;
            background: var(--green-500);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            letter-spacing: 0.2px;
        }

        .btn-login:hover { background: var(--green-400); }
        .btn-login:active { transform: scale(0.99); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--gray-400);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }

        .btn-admin {
            width: 100%;
            height: 46px;
            background: transparent;
            color: var(--gray-600);
            border: 1.5px solid var(--gray-200);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-admin:hover {
            border-color: var(--green-400);
            color: var(--green-600);
            background: var(--green-50);
        }

        .register-link {
            text-align: center;
            margin-top: 28px;
            font-size: 14px;
            color: var(--gray-400);
        }

        .register-link a {
            color: var(--green-600);
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover { text-decoration: underline; }

        /* Field icon wrapper */
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 18px;
            pointer-events: none;
        }
        .input-wrapper .form-control { padding-right: 44px; }

        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 32px 24px; }
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

    <div class="hero-content">
        <h1 class="hero-headline">
            Booking lapangan<br><span>tanpa ribet,</span><br>langsung main.
        </h1>
        <p class="hero-sub">
            Tersedia berbagai lapangan futsal berkualitas<br>yang bisa kamu pesan kapan saja dan di mana saja.
        </p>
    </div>

    <div class="feature-list">
        <div class="feature-item">
            <div class="feature-dot"></div>
            Pilih lapangan & slot waktu favoritmu
        </div>
        <div class="feature-item">
            <div class="feature-dot"></div>
            Booking dikonfirmasi oleh admin
        </div>
        <div class="feature-item">
            <div class="feature-dot"></div>
            Pantau status booking secara real-time
        </div>
    </div>
</div>

<div class="right-panel">
    <div class="login-card">
        <div class="login-header">
            <h2 class="login-title">Selamat datang kembali</h2>
            <p class="login-sub">Masuk untuk lanjutkan booking-mu</p>
        </div>

        <?php if ($error): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-wrapper">
                    <input type="email" name="email" class="form-control"
                           placeholder="kamu@email.com" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Masukkan password" required>
                    <span class="input-icon" style="cursor:pointer" onclick="togglePass()">👁</span>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">
                Masuk ke LapanganKu
            </button>
        </form>

        <div class="divider">atau</div>

        <a href="admin_login.php" class="btn-admin">
            🔐 &nbsp; Masuk sebagai Admin
        </a>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>