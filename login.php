<?php
require_once __DIR__ . '/includes/config.php';
// Auth sudah otomatis dimuat oleh config.php di atas (lihat includes/auth.php)

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (Auth::attempt($username, $password)) {
        header('Location: Admin/index.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}

if (Auth::isLoggedIn()) {
    header('Location: Admin/index.php');
    exit;
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Si-TANGKAL</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/components.css">
<link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <span class="logo-icon">🌳</span>
            <h2>Si-TANGKAL</h2>
            <p>Sistem Informasi Pohon Kota Cimahi</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <!-- <div class="login-hint">
            Demo akun — Username: <b>admin</b> &nbsp;|&nbsp; Password: <b>admin123</b>
        </div> -->

        <p style="text-align:center; margin-top:18px; font-size:12.5px;">
            <a href="index.php" class="text-muted">← Kembali ke peta</a>
        </p>
    </div>
</div>
</body>
</html>
