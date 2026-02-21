<?php
session_start();
include 'db.php';

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = ($_POST['password']);

    $query = "SELECT * FROM user WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh; /* Pastikan tinggi badan 100% dari viewport */
            margin: 0;
            display: flex;
            align-items: center; /* Pusatkan secara vertikal */
            justify-content: center; /* Pusatkan secara horizontal */
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        .login-card img {
            display: block;
            margin: 0 auto 15px auto;
            max-height: 80px;
        }
        .form-control {
            border-radius: 6px;
        }
        .btn-primary {
            border-radius: 6px;
        }
        @media (max-width: 768px) {
            .login-card {
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="assets/djbc.png" alt="Logo">
        <h1>SIKAT</h1>
        <div class="text-center" style="font-size: 18px; font-weight: bold; color: #495057; margin-bottom: 15px;">
            Sistem Kehadiran Aktif Terpadu <br> KANWIL DJBC SUMBAGTIM
        </div>
        <div class="card-header" style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">Login</div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username Anda" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password Anda" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
