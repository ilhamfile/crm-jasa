<?php
// login_klien.php
require_once 'config.php';

// Jika klien sudah login, arahkan ke detail pesanan mereka
if (isset($_SESSION['klien_id'])) {
    header("Location: detail_klien.php?id=" . $_SESSION['klien_id']);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_wa = preg_replace('/[^0-9]/', '', $_POST['no_wa']);
    $password = $_POST['password'];

    if (!empty($no_wa) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM klien WHERE no_telp LIKE ?");
        $stmt->execute(["%$no_wa%"]);
        $klien = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($klien) {
            if (empty($klien['password']) && $password === '123456') {
                $_SESSION['klien_id']   = $klien['id_klien'];
                $_SESSION['nama_klien'] = $klien['nama_lengkap'];
                // PERBAIKAN: Redirect ke detail_klien.php
                header("Location: detail_klien.php?id=" . $klien['id_klien']);
                exit();
            } 
            elseif (!empty($klien['password']) && password_verify($password, $klien['password'])) {
                $_SESSION['klien_id']   = $klien['id_klien'];
                $_SESSION['nama_klien'] = $klien['nama_lengkap'];
                // PERBAIKAN: Redirect ke detail_klien.php
                header("Location: detail_klien.php?id=" . $klien['id_klien']);
                exit();
            } else {
                $error = "Nomor WhatsApp atau Kata Sandi salah!";
            }
        } else {
            $error = "Data Klien tidak ditemukan!";
        }
    } else {
        $error = "Nomor WA dan Kata Sandi wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Klien - PT Valtekindo Global Intertek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
<div class="card shadow border-0" style="width: 100%; max-width: 400px; border-radius: 16px;">
    <div class="card-body p-4">
        <h4 class="text-center fw-bold text-primary mb-1">PORTAL KLIEN</h4>
        <p class="text-center text-muted small mb-4">Pantau Progress & Revisi Pekerjaan</p>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger py-2 small text-center"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Nomor WhatsApp</label>
                <input type="text" name="no_wa" class="form-control" placeholder="Contoh: 08123456789" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Kata Sandi</label>
                <input type="password" name="password" class="form-control" placeholder="Default: 123456" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Masuk ke Portal</button>
        </form>
    </div>
</div>
</body>
</html>