<?php
// pengeluaran.php
include 'auth.php';
include 'config.php';

// Proses simpan data jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_pengeluaran'])) {
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis_pengeluaran'];
    $biaya = (int)$_POST['biaya'];
    $keterangan = $_POST['keterangan'];

    $stmt = $pdo->prepare("INSERT INTO pengeluaran_perusahaan (tanggal, jenis_pengeluaran, biaya, keterangan) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$tanggal, $jenis, $biaya, $keterangan])) {
        $sukses = "Data pengeluaran berhasil dicatat!";
    } else {
        $error = "Gagal mencatat pengeluaran.";
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM pengeluaran_perusahaan WHERE id_pengeluaran = ?");
    $stmt->execute([$id_hapus]);
    header("Location: pengeluaran.php");
    exit();
}

// Ambil riwayat pengeluaran
$stmt_list = $pdo->query("SELECT * FROM pengeluaran_perusahaan ORDER BY tanggal DESC");
$pengeluaran_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kas Keluar - Pengeluaran Perusahaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light pb-5">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">⬅ Kembali ke Dashboard</a>
        <a class="btn btn-warning btn-sm fw-bold" href="laporan_laba_rugi.php">📊 Ke Halaman Laporan Keuangan</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white fw-bold">💸 Catat Pengeluaran Baru</div>
                <div class="card-body">
                    <?php if(isset($sukses)): ?> <div class="alert alert-success py-2 small"><?= $sukses ?></div> <?php endif; ?>
                    <?php if(isset($error)): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jenis Pengeluaran</label>
                            <input type="text" name="jenis_pengeluaran" class="form-control" placeholder="Contoh: Beli Kertas HVS, Listrik..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Biaya (Rp)</label>
                            <input type="number" name="biaya" class="form-control" placeholder="Hanya angka" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                        </div>
                        <button type="submit" name="tambah_pengeluaran" class="btn btn-danger w-100 fw-bold">Simpan Pengeluaran</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white fw-bold">📋 Riwayat Pengeluaran Operasional</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Pengeluaran</th>
                                <th>Keterangan</th>
                                <th class="text-end">Biaya</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_pengeluaran = 0;
                            foreach ($pengeluaran_list as $row): 
                                $total_pengeluaran += $row['biaya'];
                            ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td><strong><?= htmlspecialchars($row['jenis_pengeluaran']) ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars($row['keterangan']) ?></small></td>
                                <td class="text-end text-danger fw-bold">Rp <?= number_format($row['biaya'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <a href="pengeluaran.php?hapus=<?= $row['id_pengeluaran'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pengeluaran_list)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada data pengeluaran dicatat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-danger">
                            <tr>
                                <th colspan="3" class="text-end">TOTAL KESELURUHAN PENGELUARAN:</th>
                                <th class="text-end">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>