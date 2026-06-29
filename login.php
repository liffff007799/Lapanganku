<?php
session_start();
include 'koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    $koneksi = mysqli_connect('localhost', 'root', '', 'lapanganku');
}

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM users
        WHERE email='$email'
        AND password='$password'"
    );

    if(mysqli_num_rows($query) > 0){

        $data = mysqli_fetch_assoc($query);

        $_SESSION['id'] = $data['id'];
        $_SESSION['nama'] = $data['nama'];

        header("Location: dashboard.php");
        exit;

    }else{
        echo "<script>alert('Email atau Password Salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - LapanganKu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.70), rgba(0,0,0,0.70)),
            url('https://images.unsplash.com/photo-1518604666860-9ed391f76460?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box{
            width: 420px;
            padding: 40px;
            border-radius: 25px;
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0px 10px 40px rgba(0,0,0,0.4);
            color: white;
        }

        .logo{
            font-size: 65px;
        }

        .title{
            font-size: 35px;
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

        .btn-login{
            background: #28a745;
            color: white;
            font-weight: bold;
            height: 50px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .btn-login:hover{
            transform: scale(1.03);
        }

        a{
            color: #ffffff;
            text-decoration: none;
        }

        a:hover{
            color: #00ff99;
        }

    </style>
</head>
<body>

<div class="login-box text-center">

    <div class="logo">⚽</div>

    <div class="title">LapanganKu</div>

    <div class="subtitle">
        Sistem Booking Lapangan Futsal Modern
    </div>

    <form method="POST">

        <div class="mb-3 text-start">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-4 text-start">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100">
            Masuk ke Lapangan
        </button>

    </form>

    <div class="mt-4">
        Belum punya akun?
        <br>
        <a href="register.php">Daftar Sekarang</a>
    </div>

</div>

</body>
</html>