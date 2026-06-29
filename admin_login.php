<?php

session_start();
include 'koneksi.php';

if (!isset($koneksi) || $koneksi === null) {
    $koneksi = mysqli_connect('localhost', 'root', '', 'lapanganku');
    if (!$koneksi) {
        die('Database connection failed: ' . mysqli_connect_error());
    }
}

if(isset($_POST['login']))
{
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM admin
        WHERE username='$username'
        AND password='$password'"
    );

    if(mysqli_num_rows($query) > 0)
    {
        $_SESSION['admin'] = $username;

        header("Location: admin_dashboard.php");
        exit;
    }
    else
    {
        echo "<script>alert('Login Admin Gagal');</script>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - LapanganKu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body{
            background:
            linear-gradient(rgba(0,0,0,0.80), rgba(0,0,0,0.80)),
            url('https://images.unsplash.com/photo-1570498839593-e565b39455fc?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .admin-box{
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

        .btn-admin{
            background: #dc3545;
            color: white;
            font-weight: bold;
            height: 50px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .btn-admin:hover{
            transform: scale(1.03);
        }

    </style>
</head>
<body>

<div class="admin-box text-center">

    <div class="logo">🛡</div>

    <div class="title">Admin Panel</div>

    <div class="subtitle">
        Kelola seluruh sistem booking futsal
    </div>

    <form method="POST">

        <div class="mb-3 text-start">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-4 text-start">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" name="login" class="btn btn-admin w-100">
            Masuk Admin
        </button>

    </form>

</div>

</body>
</html>