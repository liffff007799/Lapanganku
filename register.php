<?php
require_once __DIR__ . '/koneksi.php';

if(isset($_POST['register'])){

    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $no_hp = $_POST['no_hp'];
    
    mysqli_query(
        $koneksi,
        "INSERT INTO users (nama,email,password,no_hp)
        VALUES ('$nama','$email','$password','$no_hp')"
    );

    echo "<script>
        alert('Registrasi berhasil!');
        window.location='login.php';
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - LapanganKu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
            url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-box{
            width: 450px;
            padding: 40px;
            border-radius: 25px;
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0px 10px 40px rgba(0,0,0,0.4);
            color: white;
        }

        .logo{
            font-size: 60px;
        }

        .title{
            font-size: 34px;
            font-weight: bold;
        }

        .subtitle{
            color: #d9d9d9;
            margin-bottom: 30px;
        }

        .form-control{
            height: 50px;
            border-radius: 12px;
        }

        .btn-register{
            background: #198754;
            color: white;
            font-weight: bold;
            height: 50px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .btn-register:hover{
            transform: scale(1.03);
        }

        a{
            color: white;
            text-decoration: none;
        }

        a:hover{
            color: #00ff99;
        }

    </style>
</head>
<body>

<div class="register-box text-center">

    <div class="logo">🏆</div>

    <div class="title">Daftar Akun</div>

    <div class="subtitle">
        Gabung dan booking lapangan futsal favoritmu
    </div>

    <form method="POST">

        <div class="mb-3 text-start">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required>
        </div>

        <div class="mb-3 text-start">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-4 text-start">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-4 text-start">
            <label>Nomor HP / WhatsApp</label>
            <input type="text" name="no_hp" class="form-control" required>
        </div>

        <button type="submit" name="register" class="btn btn-register w-100">
            Daftar Sekarang
        </button>

    </form>

    <div class="mt-4">
        Sudah punya akun?
        <br>
        <a href="login.php">Masuk di sini</a>
    </div>

</div>

</body>
</html>