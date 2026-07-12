<?php
// upload_mou.php
include 'auth.php'; // Hanya Admin/Staff yang boleh akses halaman ini
include 'config.php';

$id_pesanan = $_GET['id'] ?? die('ID Tidak Valid');

// Ambil data klien untuk tampilan
$stmt = $pdo->prepare("SELECT k.nama_lengkap, p.file_mou FROM klien k JOIN pesanan_layanan p ON k.id_klien = p.id_klien WHERE p.id_pesanan = ?");
$stmt->execute([$id_pesanan]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Data tidak ditemukan.");

$error = '';

// Proses Eksekusi Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_mou'])) {
    $file = $_FILES['file_mou'];
    
    // Validasi Ekstensi File
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Terjadi kesalahan saat memilih file.";
    } elseif ($ext != 'pdf') {
        $error = "Gagal! Hanya file berformat .PDF yang diizinkan.";
    } elseif ($file['size'] > 5242880) { // Batas maksimal 5 MB
        $error = "Ukuran file terlalu besar! Maksimal 5 MB.";
    } else {
        // Buat folder 'uploads' otomatis jika belum ada di server
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        // Buat nama file unik agar tidak bentrok dengan data klien lain
        $filename = "MoU_" . $id_pesanan . "_" . time() . ".pdf";
        $destination = $dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Hapus file MoU lama di server jika sebelumnya sudah pernah upload
            if (!empty($data['file_mou']) && file_exists('uploads/' . $data['file_mou'])) {
                unlink('uploads/' . $data['file_mou']);
            }
            
            // Simpan nama file baru ke database
            $stmt_up = $pdo->prepare("UPDATE pesanan_layanan SET file_mou = ? WHERE id_pesanan = ?");
            $stmt_up->execute([$filename, $id_pesanan]);
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Gagal memindahkan file ke server. Cek perizinan folder.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload MoU Klien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-success text-white fw-bold"><h5>⬆️ Upload Dokumen MoU (PDF)</h5></div>
        <div class="card-body p-4">
            <p>Klien: <strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong></p>
            
            <?php if(!empty($data['file_mou'])): ?>
                <div class="alert alert-info py-2 small">
                    Dokumen MoU untuk klien ini <strong>sudah ada</strong>. Mengunggah file baru akan menimpa file yang lama.
                </div>
            <?php endif; ?>

            <?php if(!empty($error)): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label small fw-bold">Pilih File PDF</label>
                    <input type="file" name="file_mou" class="form-control" accept=".pdf" required>
                    <div class="form-text text-muted" style="font-size: 11px;">Pastikan file sudah ditandatangani. Maks 5MB.</div>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-bold">Unggah & Simpan MoU</button>
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>