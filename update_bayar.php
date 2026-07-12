<?php
// update_bayar.php
include 'auth.php';
include 'config.php';

$id = $_GET['id'] ?? die('ID Tidak Valid');

// Ambil data pembayaran beserta tanggal termin
$stmt = $pdo->prepare("SELECT p.nilai_dealing, t.* FROM pesanan_layanan p JOIN pembayaran_termin t ON p.id_pesanan = t.id_pesanan WHERE p.id_pesanan = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Data tidak ditemukan.");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // FILTER ANGKA: Jika kosong jadikan 0
    $t1 = (!empty($_POST['termin_1'])) ? (int)$_POST['termin_1'] : 0;
    $t2 = (!empty($_POST['termin_2'])) ? (int)$_POST['termin_2'] : 0;
    $t3 = (!empty($_POST['termin_3'])) ? (int)$_POST['termin_3'] : 0;

    // FILTER TANGGAL: Jika tidak diisi, biarkan NULL di database
    $tgl1 = !empty($_POST['tgl_termin_1']) ? $_POST['tgl_termin_1'] : null;
    $tgl2 = !empty($_POST['tgl_termin_2']) ? $_POST['tgl_termin_2'] : null;
    $tgl3 = !empty($_POST['tgl_termin_3']) ? $_POST['tgl_termin_3'] : null;
    
    $total_masuk = $t1 + $t2 + $t3;
    $dealing = (int)$data['nilai_dealing'];

    // Logika Otomatisasi Status
    if ($total_masuk >= $dealing) {
        $status = "Selesai";
    } elseif ($total_masuk > 0 && $total_masuk < $dealing) {
        $status = "Tertunda";
    } else {
        $status = "Belum";
    }

    try {
        $pdo->beginTransaction();

        // Update Pembayaran Termin & Tanggalnya
        $sql_up = "UPDATE pembayaran_termin SET termin_1 = ?, tgl_termin_1 = ?, termin_2 = ?, tgl_termin_2 = ?, termin_3 = ?, tgl_termin_3 = ? WHERE id_pesanan = ?";
        $stmt_up = $pdo->prepare($sql_up);
        $stmt_up->execute([$t1, $tgl1, $t2, $tgl2, $t3, $tgl3, $id]);

        // Update Status
        $stmt_st = $pdo->prepare("UPDATE pesanan_layanan SET status_pelunasan = ? WHERE id_pesanan = ?");
        $stmt_st->execute([$status, $id]);

        $pdo->commit();
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal memproses pembayaran: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white fw-bold"><h5>💳 Update Cicilan Pembayaran</h5></div>
        <div class="card-body p-4">
            
            <?php if(!empty($error)): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>

            <p class="text-danger fw-bold mb-4">Total Nilai Kontrak (Dealing): Rp <?= number_format($data['nilai_dealing'], 0, ',', '.') ?></p>
            
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Nominal Termin 1</label>
                        <input type="number" name="termin_1" class="form-control" value="<?= $data['termin_1'] == 0 ? '' : $data['termin_1'] ?>" placeholder="Opsional">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Tgl Bayar Termin 1</label>
                        <input type="date" name="tgl_termin_1" class="form-control" value="<?= htmlspecialchars($data['tgl_termin_1'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Nominal Termin 2</label>
                        <input type="number" name="termin_2" class="form-control" value="<?= $data['termin_2'] == 0 ? '' : $data['termin_2'] ?>" placeholder="Opsional">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Tgl Bayar Termin 2</label>
                        <input type="date" name="tgl_termin_2" class="form-control" value="<?= htmlspecialchars($data['tgl_termin_2'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-success">Nominal Termin 3 (Pelunasan)</label>
                        <input type="number" name="termin_3" class="form-control border-success" value="<?= $data['termin_3'] == 0 ? '' : $data['termin_3'] ?>" placeholder="Opsional">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-success">Tgl Bayar Termin 3</label>
                        <input type="date" name="tgl_termin_3" class="form-control border-success" value="<?= htmlspecialchars($data['tgl_termin_3'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold">Perbarui Data Pembayaran</button>
                <a href="index.php" class="btn btn-link w-100 text-muted mt-2 small">Batal & Kembali</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>