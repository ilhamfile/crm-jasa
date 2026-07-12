<?php
// login.php
include 'config.php';

// Jika sudah login, langsung lempar ke index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kebiri input untuk menghindari spasi tak sengaja
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Lapisan Keamanan 1: Prepared Statement untuk anti SQL Injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Lapisan Keamanan 2: Verifikasi hash password (anti-crack)
        if ($user && password_verify($password, $user['password'])) {
            
            // Lapisan Keamanan 3: Regenerasi ID Sesi untuk mencegah Session Hijacking
            session_regenerate_id(true);

            // Simpan data ke session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['nama']      = $user['nama_lengkap'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Jasa Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="bg-secondary">
<div class="card shadow" style="width: 100%; max-width: 400px; border-radius: 12px;">
    <div class="card-body p-4">
        <h4 class="text-center fw-bold text-primary mb-3">SYSTEM LOGIN</h4>
        <p class="text-center text-muted small mb-4">Sistem CRM Administrasi Jasa Akademik</p>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger py-2 small text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Masuk ke Sistem</button>
        </form>
    </div>
</div>
</body>
</html>