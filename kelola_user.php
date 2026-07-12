<?php
// kelola_user.php
include 'auth.php';
include 'config.php';

// Ambil semua daftar user
$stmt = $pdo->query("SELECT id, username, nama_lengkap, privileges FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fitur Hapus User Aman
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    // Mencegah menghapus diri sendiri yang sedang login
    if ($id_hapus == $_SESSION['user_id']) {
        header("Location: kelola_user.php?msg=gagal");
    } else {
        $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$id_hapus]);
        header("Location: kelola_user.php?msg=sukses");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - PT. Lentera Statistics Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center">
            <h5 class="mb-0">⚙️ Manajemen Akun Pengguna</h5>
            <a href="index.php" class="btn btn-light btn-sm fw-bold">🏠 Dashboard</a>
        </div>
        <div class="card-body p-4">
            <?php if(($_GET['msg'] ?? '') == 'sukses'): ?> <div class="alert alert-success py-2 small">User berhasil dihapus!</div> <?php endif; ?>
            <?php if(($_GET['msg'] ?? '') == 'gagal'): ?> <div class="alert alert-danger py-2 small">Keamanan Terkunci: Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif!</div> <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center small">
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Privileges</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($users as $u): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
                            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                            <td class="text-center"><span class="badge <?= $u['privileges'] == 'Admin' ? 'bg-danger' : 'bg-primary' ?>"><?= $u['privileges'] ?></span></td>
                            <td class="text-center">
                                <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning py-0 px-2 fw-bold">✏️ Edit</a>
                                <a href="kelola_user.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>