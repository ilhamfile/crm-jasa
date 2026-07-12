<?php
// tambah_user.php
include 'auth.php'; // Proteksi halaman, hanya user yang sudah login yang bisa akses

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $nama       = trim($_POST['nama_lengkap']);
    $privileges = $_POST['privileges'];

    // Validasi input kosong
    if (!empty($username) && !empty($password) && !empty($nama) && !empty($privileges)) {
        
        // Keamanan 1: Cek apakah username sudah pernah terdaftar (Mencegah Duplikasi)
        $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_cek->execute([$username]);
        
        if ($stmt_cek->fetch()) {
            $error = "Username sudah digunakan oleh orang lain! Pilih username lain.";
        } else {
            // Keamanan 2: Hash password menggunakan Bcrypt (Standar keamanan tinggi)
            $password_aman = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Keamanan 3: Menggunakan PDO Prepared Statement untuk anti SQL Injection
                $stmt_ins = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, privileges) VALUES (?, ?, ?, ?)");
                $stmt_ins->execute([$username, $password_aman, $nama, $privileges]);
                
                $success = "User baru bernama <strong>$nama</strong> dengan hak akses <strong>$privileges</strong> berhasil didaftarkan!";
            } catch (PDOException $e) {
                $error = "Gagal menyimpan ke database: " . $e->getMessage();
            }
        }
    } else {
        $error = "Semua kolom form wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User Baru - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0" style="border-radius: 12px;">
        <div class="card-header bg-warning text-dark fw-bold p-3" style="border-radius: 12px 12px 0 0;">
            <h5 class="mb-0">👤 Form Pembuatan Akun Pengguna Baru</h5>
        </div>
        <div class="card-body p-4">
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger small py-2"><?= $error ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success small py-2"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap Pengguna</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ahmad Subarjo" required autocomplete="off">
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username Sesi Login</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: ahmad_staff" required autocomplete="off">
                    <div class="form-text text-muted" style="font-size: 11px;">Gunakan huruf kecil atau angka, tanpa spasi.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password Akun</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password kuat" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Privileges (Hak Akses Tingkat)</label>
                    <select name="privileges" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Tingkat Hak Akses --</option>
                        <option value="Admin">Admin (Akses Penuh Pengelolaan)</option>
                        <option value="Staff">Staff (Hanya Input & Operasional)</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning fw-bold text-dark">Daftarkan Akun Sekarang</button>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali ke Dashboard Utama</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>