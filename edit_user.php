<?php
// edit_user.php
include 'auth.php';
include 'config.php';

$id_user = $_GET['id'] ?? die('ID Tidak Valid');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    die("User tidak ditemukan.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username   = trim($_POST['username']);
    $nama       = trim($_POST['nama_lengkap']);
    $privileges = $_POST['privileges'];
    $password   = $_POST['password'];

    if (!empty($username) && !empty($nama)) {
        try {
            if (!empty($password)) {
                // Jika ganti password baru, hash ulang secara aman
                $password_aman = password_hash($password, PASSWORD_DEFAULT);
                $stmt_up = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, privileges = ? WHERE id = ?");
                $stmt_up->execute([$username, $password_aman, $nama, $privileges, $id_user]);
            } else {
                // Jika password dikosongkan, pertahankan password lama
                $stmt_up = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, privileges = ? WHERE id = ?");
                $stmt_up->execute([$username, $nama, $privileges, $id_user]);
            }
            
            // Jika mengedit profil diri sendiri, perbarui data session agar sinkron
            if ($id_user == $_SESSION['user_id']) {
                $_SESSION['nama'] = $nama;
            }

            header("Location: kelola_user.php");
            exit();
        } catch (PDOException $e) {
            $error = "Gagal memperbarui data user: " . $e->getMessage();
        }
    } else {
        $error = "Username dan Nama Lengkap tidak boleh dikosongkan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Akun User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 500px;">
    <div class="card shadow border-0">
        <div class="card-header bg-warning text-dark fw-bold"><h5>✏️ Form Edit Akun Pengguna</h5></div>
        <div class="card-body p-4">
            <?php if(!empty($error)): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>

            <form method="POST">
                <div class="mb-3"><label class="form-label small fw-bold">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" required></div>
                <div class="mb-3"><label class="form-label small fw-bold">Username Sesi Login</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required></div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password Baru</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika TIDAK ingin diubah">
                    <div class="form-text text-muted" style="font-size: 11px;">Keamanan Sistem: Biarkan kolom ini kosong kecuali Anda ingin mereset password user ini.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Privileges (Hak Akses Tingkat)</label>
                    <select name="privileges" class="form-select" required>
                        <option value="Admin" <?= $u['privileges'] == 'Admin' ? 'selected' : '' ?>>Admin (Akses Penuh Pengelolaan)</option>
                        <option value="Staff" <?= $u['privileges'] == 'Staff' ? 'selected' : '' ?>>Staff (Hanya Input & Operasional)</option>
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning fw-bold text-dark">Simpan Perubahan Akun</button>
                    <a href="kelola_user.php" class="btn btn-outline-secondary btn-sm">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>