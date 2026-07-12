<?php
// tambah.php
include 'auth.php';
include 'config.php';
require_once __DIR__ . '/includes/client_photo.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Tangkap semua inputan dari Form
    $nama        = trim($_POST['nama_lengkap']);
    $fotoBaru    = null;
    $tgl_masuk   = $_POST['tanggal_daftar']; // Tanggal masuk klien
    $alamat      = trim($_POST['alamat']);
    $no_telp     = trim($_POST['no_telp']);
    $institusi   = trim($_POST['institusi']);
    $fakultas    = trim($_POST['fakultas']);
    $prodi       = trim($_POST['program_studi']);
    $judul       = trim($_POST['judul_penelitian']);
    
    $layanan     = $_POST['jenis_layanan'];
    $dealing     = $_POST['nilai_dealing'];
    $deadline    = $_POST['deadline'];

    // 2. Validasi input wajib
    if (!empty($nama) && !empty($no_telp) && !empty($tgl_masuk) && !empty($layanan)) {
        try {
            // Mulai transaksi database agar jika 1 gagal, semua dibatalkan
            $fotoBaru = uploadClientPhoto(
                $_FILES['foto_klien'] ?? null
            );
            $pdo->beginTransaction();

            // A. Masukkan Data ke Tabel Klien
            $sql_klien = "
                INSERT INTO klien (
                    nama_lengkap,
                    foto_klien,
                    tanggal_daftar,
                    alamat,
                    no_telp,
                    institusi,
                    fakultas,
                    program_studi,
                    judul_penelitian
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt_klien = $pdo->prepare($sql_klien);
            
            $stmt_klien->execute([
                $nama,
                $fotoBaru,
                $tgl_masuk,
                $alamat,
                $no_telp,
                $institusi,
                $fakultas,
                $prodi,
                $judul
            ]);
            
            // Ambil ID Klien yang baru saja dibuat
            $id_klien = $pdo->lastInsertId();

            // B. Masukkan Data ke Tabel Pesanan Layanan
            $sql_pesanan = "INSERT INTO pesanan_layanan (id_klien, jenis_layanan, nilai_dealing, deadline, status_pelunasan) 
                            VALUES (?, ?, ?, ?, 'Tertunda')";
            $stmt_pesanan = $pdo->prepare($sql_pesanan);
            $stmt_pesanan->execute([$id_klien, $layanan, $dealing, $deadline]);
            
            // Ambil ID Pesanan yang baru saja dibuat
            $id_pesanan = $pdo->lastInsertId();

            // C. Buat slot kosong di Tabel Pembayaran Termin
            $sql_termin = "INSERT INTO pembayaran_termin (id_pesanan, termin_1, termin_2, termin_3) 
                           VALUES (?, 0, 0, 0)";
            $stmt_termin = $pdo->prepare($sql_termin);
            $stmt_termin->execute([$id_pesanan]);

            // Eksekusi semua secara permanen
            $pdo->commit();
            
            // Lempar kembali ke halaman utama
            header("Location: index.php");
            exit();

                    } catch (Throwable $e) {
            
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            
                if (!empty($fotoBaru)) {
                    deleteClientPhoto($fotoBaru);
                }
            
                $error =
                    "Terjadi Kesalahan Sistem: " .
                    $e->getMessage();
            }
    } else {
        $error = "Kolom Nama, No. Telp, Tanggal Masuk, dan Layanan wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Klien Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/client.css">    
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white fw-bold"><h5>➕ Form Input Klien & Pesanan Baru</h5></div>
        <div class="card-body p-4">
            
            <?php if(!empty($error)): ?> 
                <div class="alert alert-danger py-2 small"><?= $error ?></div> 
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap Klien <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" required>
                </div>
                
                <div class="mb-4">
                <label class="form-label small fw-bold">
                    Foto Klien
                </label>
                <div class="client-photo-uploader">
                    <div
                        class="client-photo-preview"
                        id="photoPreview">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="flex-grow-1">
                        <input
                            type="file"
                            name="foto_klien"
                            id="fotoKlien"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp">
                        <div class="client-photo-help mt-2">
                            JPG, PNG, atau WEBP. Maksimal 2 MB.
                        </div>
                    </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tanggal Masuk / Tanggal Deal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">No. Telp / WA <span class="text-danger">*</span></label>
                    <input type="text" name="no_telp" class="form-control" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label small fw-bold">Institusi</label>
                        <input type="text" name="institusi" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-bold">Fakultas</label>
                        <input type="text" name="fakultas" class="form-control">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Program Studi</label>
                    <input type="text" name="program_studi" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Judul Penelitian</label>
                    <textarea name="judul_penelitian" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Jenis Layanan <span class="text-danger">*</span></label>
                    <select name="jenis_layanan" class="form-select" required>
                        <option value="" selected disabled>-- Pilih Layanan --</option>
                        <option value="Skripsi">Skripsi</option>
                        <option value="Tesis">Tesis</option>
                        <option value="Disertasi">Disertasi</option>
                        <option value="Publikasi Jurnal">Publikasi Jurnal</option>
                        <option value="Artikel">Artikel</option>
                        <option value="Bisnis Plan">Bisnis Plan</option>
                        <option value="Jurnal dan Poster">Jurnal dan Poster</option>
                        <option value="Olah Data">Olah Data</option>
                    </select>
                </div>
                
                <div class="row mb-4">
                    <div class="col">
                        <label class="form-label small fw-bold">Nilai Kesepakatan (Dealing) <span class="text-danger">*</span></label>
                        <input type="number" name="nilai_dealing" class="form-control" placeholder="Contoh: 5000000" required>
                    </div>
                    <div class="col">
                        <label class="form-label small fw-bold">Target Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 fw-bold">Simpan Data Klien Baru</button>
                <a href="index.php" class="btn btn-link w-100 text-muted mt-2 small">Batal & Kembali</a>
            </form>
        </div>
    </div>
</div>
<script>
document
    .getElementById('fotoKlien')
    ?.addEventListener('change', function () {

        const preview =
            document.getElementById('photoPreview');

        const file = this.files[0];

        if (!file || !preview) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {

            preview.innerHTML =
                '<img src="' +
                event.target.result +
                '" alt="Preview foto">';

        };

        reader.readAsDataURL(file);

    });
</script>
</body>
</html>