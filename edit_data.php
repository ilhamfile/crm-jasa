<?php
// edit_data.php
require_once 'config.php';
require_once __DIR__ . '/includes/client_photo.php';

// Cek Autentikasi (Bisa diakses Staff atau Klien)
$is_staff = isset($_SESSION['user_id']);
$is_client = isset($_SESSION['klien_id']);

if (!$is_staff && !$is_client) {
    header("Location: login.php");
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function textLength(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

/**
 * Mengubah kolom ENUM lama menjadi VARCHAR agar nama layanan baru dapat disimpan.
 * Installer versi terbaru sudah memakai VARCHAR. Fungsi ini menjaga kompatibilitas
 * dengan database yang dibuat dari dump SQL lama.
 */
function ensureFlexibleServiceColumn(PDO $pdo): ?string
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `pesanan_layanan` LIKE 'jenis_layanan'");
        $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        $type = strtolower((string) ($column['Type'] ?? ''));

        if ($type !== '' && substr($type, 0, 5) === 'enum(') {
            $pdo->exec(
                "ALTER TABLE `pesanan_layanan`
                 MODIFY COLUMN `jenis_layanan` VARCHAR(150) NOT NULL"
            );
        }

        return null;
    } catch (Throwable $exception) {
        return 'Kolom jenis layanan masih memakai format lama. Nama layanan baru mungkin tidak dapat disimpan. '
            . 'Jalankan ALTER TABLE pesanan_layanan MODIFY jenis_layanan VARCHAR(150) NOT NULL; '
            . 'atau berikan izin ALTER kepada pengguna database.';
    }
}

$schemaWarning = ensureFlexibleServiceColumn($pdo);

$idKlien = filter_input(INPUT_GET, 'id_klien', FILTER_VALIDATE_INT);
$idPesanan = filter_input(INPUT_GET, 'id_pesanan', FILTER_VALIDATE_INT);

/* Kompatibilitas dengan link lama: edit_data.php?id=ID_PESANAN */
if (!$idPesanan) {
    $idPesanan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if (!$idPesanan) {
    exit('ID pesanan tidak valid.');
}

$sqlData = "
    SELECT
        k.*,
        p.*
    FROM klien k
    INNER JOIN pesanan_layanan p
        ON k.id_klien = p.id_klien
    WHERE p.id_pesanan = :id_pesanan
";

$paramsData = ['id_pesanan' => $idPesanan];

if ($idKlien) {
    $sqlData .= " AND k.id_klien = :id_klien";
    $paramsData['id_klien'] = $idKlien;
}

$sqlData .= ' LIMIT 1';

$stmt = $pdo->prepare($sqlData);
$stmt->execute($paramsData);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    exit('Data tidak ditemukan.');
}

// Validasi Keamanan: Klien HANYA boleh mengedit profilnya sendiri
if ($is_client && $_SESSION['klien_id'] != $data['id_klien']) {
    die('Akses ditolak! Anda tidak diizinkan mengubah data klien lain.');
}

$idPesanan = (int) $idPesanan;
$idKlien = (int) $data['id_klien'];
$error = '';

$defaultServices = [
    'Skripsi',
    'Tesis',
    'Disertasi',
    'Publikasi Jurnal',
    'Artikel',
    'Bisnis Plan',
    'Jurnal dan Poster',
    'Olah Data',
];

// Ambil nama layanan yang pernah digunakan agar muncul sebagai saran.
$serviceSuggestions = $defaultServices;
try {
    $serviceStmt = $pdo->query(
        "SELECT DISTINCT TRIM(jenis_layanan) AS jenis_layanan
         FROM pesanan_layanan
         WHERE TRIM(jenis_layanan) <> ''
         ORDER BY jenis_layanan ASC"
    );

    foreach (($serviceStmt ? $serviceStmt->fetchAll(PDO::FETCH_COLUMN) : []) as $serviceName) {
        $serviceName = trim((string) $serviceName);
        if ($serviceName !== '') {
            $serviceSuggestions[] = $serviceName;
        }
    }
} catch (Throwable $exception) {
    // Saran bawaan tetap tersedia jika query gagal.
}

$serviceSuggestions = array_values(array_unique($serviceSuggestions));
sort($serviceSuggestions, SORT_NATURAL | SORT_FLAG_CASE);

$submittedNewServices = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $fotoBaru = null;
    $fotoLama = $data['foto_klien'] ?? null;
    $tglMasuk = trim((string) ($_POST['tanggal_daftar'] ?? ''));
    $alamat = trim((string) ($_POST['alamat'] ?? ''));
    $noTelp = trim((string) ($_POST['no_telp'] ?? ''));
    $institusi = trim((string) ($_POST['institusi'] ?? ''));
    $fakultas = trim((string) ($_POST['fakultas'] ?? ''));
    $prodi = trim((string) ($_POST['program_studi'] ?? ''));
    $judul = trim((string) ($_POST['judul_penelitian'] ?? ''));
    
    $layanan = trim((string) ($_POST['jenis_layanan'] ?? ''));
    $deadline = trim((string) ($_POST['deadline'] ?? ''));
    $dealingRaw = trim((string) ($_POST['nilai_dealing'] ?? '0'));
    $dealing = filter_var($dealingRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($dealing === false) {
        $dealing = null;
    }
    
    // [KEAMANAN] Jika yang mengedit adalah Klien, timpa input form dengan data lama dari database
    if ($is_client) {
        $layanan = $data['jenis_layanan'];
        $deadline = $data['deadline'];
        $dealing = $data['nilai_dealing'];
        $_POST['layanan_baru_jenis'] = []; // Kosongkan array agar tidak bisa nambah layanan sisipan
    }

    $newServiceNames = $_POST['layanan_baru_jenis'] ?? [];
    $newServiceDeals = $_POST['layanan_baru_dealing'] ?? [];
    $newServiceDeadlines = $_POST['layanan_baru_deadline'] ?? [];

    if (!is_array($newServiceNames)) {
        $newServiceNames = [];
    }
    if (!is_array($newServiceDeals)) {
        $newServiceDeals = [];
    }
    if (!is_array($newServiceDeadlines)) {
        $newServiceDeadlines = [];
    }

    $rowCount = min(max(count($newServiceNames), count($newServiceDeals), count($newServiceDeadlines)), 20);

    for ($index = 0; $index < $rowCount; $index++) {
        $newName = trim((string) ($newServiceNames[$index] ?? ''));
        $newDealRaw = trim((string) ($newServiceDeals[$index] ?? ''));
        $newDeadline = trim((string) ($newServiceDeadlines[$index] ?? ''));

        if ($newName === '' && $newDealRaw === '' && $newDeadline === '') {
            continue;
        }

        $submittedNewServices[] = [
            'jenis_layanan' => $newName,
            'nilai_dealing' => $newDealRaw,
            'deadline' => $newDeadline,
        ];
    }

    $validationErrors = [];

    if ($nama === '') {
        $validationErrors[] = 'Nama lengkap wajib diisi.';
    }
    if ($noTelp === '') {
        $validationErrors[] = 'Nomor telepon wajib diisi.';
    }
    if ($tglMasuk === '') {
        $validationErrors[] = 'Tanggal masuk wajib diisi.';
    }
    if ($layanan === '') {
        $validationErrors[] = 'Jenis layanan utama wajib diisi.';
    } elseif (textLength($layanan) > 150) {
        $validationErrors[] = 'Jenis layanan utama maksimal 150 karakter.';
    }
    if ($deadline === '') {
        $validationErrors[] = 'Deadline layanan utama wajib diisi.';
    }
    if ($dealing === null) {
        $validationErrors[] = 'Nilai dealing utama harus berupa angka nol atau lebih besar.';
    }

    $validatedNewServices = [];
    foreach ($submittedNewServices as $index => $newService) {
        $number = $index + 1;
        $newName = $newService['jenis_layanan'];
        $newDeadline = $newService['deadline'];
        $newDealRaw = $newService['nilai_dealing'];
        $newDeal = filter_var($newDealRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($newName === '') {
            $validationErrors[] = "Jenis layanan tambahan ke-{$number} wajib diisi.";
        } elseif (textLength($newName) > 150) {
            $validationErrors[] = "Jenis layanan tambahan ke-{$number} maksimal 150 karakter.";
        }

        if ($newDeal === false) {
            $validationErrors[] = "Nilai dealing layanan tambahan ke-{$number} harus berupa angka nol atau lebih besar.";
        }

        if ($newDeadline === '') {
            $validationErrors[] = "Deadline layanan tambahan ke-{$number} wajib diisi.";
        }

        if ($newName !== '' && $newDeal !== false && $newDeadline !== '') {
            $validatedNewServices[] = [
                'jenis_layanan' => $newName,
                'nilai_dealing' => (int) $newDeal,
                'deadline' => $newDeadline,
            ];
        }
    }

    if (empty($validationErrors)) {
        try {
            $fotoBaru = uploadClientPhoto($_FILES['foto_klien'] ?? null);
            $fotoDisimpan = $fotoBaru ?: $fotoLama;

            $pdo->beginTransaction();

            $sqlKlien = "
                UPDATE klien
                SET
                    nama_lengkap = ?,
                    foto_klien = ?,
                    tanggal_daftar = ?,
                    alamat = ?,
                    no_telp = ?,
                    institusi = ?,
                    fakultas = ?,
                    program_studi = ?,
                    judul_penelitian = ?
                WHERE id_klien = ?
            ";

            $pdo->prepare($sqlKlien)->execute([
                $nama,
                $fotoDisimpan,
                $tglMasuk,
                $alamat,
                $noTelp,
                $institusi,
                $fakultas,
                $prodi,
                $judul,
                $idKlien,
            ]);

            $sqlPesanan = "
                UPDATE pesanan_layanan
                SET jenis_layanan = ?, nilai_dealing = ?, deadline = ?
                WHERE id_pesanan = ? AND id_klien = ?
            ";
            $pdo->prepare($sqlPesanan)->execute([
                $layanan,
                $dealing,
                $deadline,
                $idPesanan,
                $idKlien,
            ]);

            if (!empty($validatedNewServices)) {
                $insertOrder = $pdo->prepare(
                    "INSERT INTO pesanan_layanan
                        (id_klien, jenis_layanan, nilai_dealing, deadline, status_pelunasan)
                     VALUES (?, ?, ?, ?, 'Tertunda')"
                );

                $insertPayment = $pdo->prepare(
                    "INSERT INTO pembayaran_termin
                        (id_pesanan, termin_1, termin_2, termin_3)
                     VALUES (?, 0, 0, 0)"
                );

                foreach ($validatedNewServices as $newService) {
                    $insertOrder->execute([
                        $idKlien,
                        $newService['jenis_layanan'],
                        $newService['nilai_dealing'],
                        $newService['deadline'],
                    ]);

                    $newOrderId = (int) $pdo->lastInsertId();
                    $insertPayment->execute([$newOrderId]);
                }
            }

            $pdo->commit();

            if ($fotoBaru && !empty($fotoLama) && $fotoBaru !== $fotoLama) {
                deleteClientPhoto($fotoLama);
            }

            header('Location: detail_klien.php?id=' . $idKlien . '&updated=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (!empty($fotoBaru)) {
                deleteClientPhoto($fotoBaru);
            }

            $error = 'Gagal memperbarui data: ' . $exception->getMessage();
        }
    } else {
        $error = implode(' ', $validationErrors);
    }

    // Pertahankan nilai yang dikirim jika validasi gagal.
    $data = array_merge($data, [
        'nama_lengkap' => $nama,
        'tanggal_daftar' => $tglMasuk,
        'alamat' => $alamat,
        'no_telp' => $noTelp,
        'institusi' => $institusi,
        'fakultas' => $fakultas,
        'program_studi' => $prodi,
        'judul_penelitian' => $judul,
        'jenis_layanan' => $layanan,
        'nilai_dealing' => $dealingRaw,
        'deadline' => $deadline,
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Klien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <style>
        .service-section {
            border: 1px solid #dee2e6;
            border-radius: 14px;
            background: #f8f9fa;
            padding: 18px;
        }

        .new-service-card {
            position: relative;
            border: 1px solid #d9e2ef;
            border-radius: 12px;
            background: #fff;
            padding: 16px;
        }

        .new-service-card + .new-service-card {
            margin-top: 12px;
        }

        .remove-service-btn {
            min-width: 42px;
        }

        .service-help {
            color: #6c757d;
            font-size: .82rem;
        }

        @media (max-width: 575.98px) {
            body {
                padding-top: 18px !important;
                padding-bottom: 18px !important;
            }

            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            .card-body {
                padding: 18px !important;
            }

            .service-section {
                padding: 14px;
            }
        }
    </style>
</head>
<body class="bg-light py-5">
<div class="container-fluid px-4">
    <div class="card shadow border-0">
        <div class="card-header bg-warning text-dark fw-bold">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Form Edit Data Klien &amp; Layanan</h5>
        </div>
        <div class="card-body p-4">

            <?php if ($schemaWarning): ?>
                <div class="alert alert-warning py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= e($schemaWarning) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="namaLengkap">
                        Nama Lengkap Klien <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="namaLengkap"
                        name="nama_lengkap"
                        class="form-control"
                        value="<?= e($data['nama_lengkap'] ?? '') ?>"
                        required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold" for="fotoKlien">Foto Klien</label>
                    <div class="client-photo-uploader">
                        <div class="client-photo-preview" id="photoPreview">
                            <?php
                            $fotoEdit = ltrim(
                                str_replace('\\', '/', (string) ($data['foto_klien'] ?? '')),
                                '/'
                            );

                            $fotoEditValid =
                                $fotoEdit !== ''
                                && strpos($fotoEdit, 'uploads/foto_klien/') === 0
                                && is_file(__DIR__ . '/' . $fotoEdit);
                            ?>

                            <?php if ($fotoEditValid): ?>
                                <img src="<?= e($fotoEdit) ?>" alt="Foto klien">
                            <?php else: ?>
                                <i class="bi bi-person"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <input
                                type="file"
                                name="foto_klien"
                                id="fotoKlien"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp">
                            <div class="client-photo-help mt-2">
                                Kosongkan jika foto tidak ingin diganti. Maksimal 2 MB.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold" for="tanggalDaftar">
                        Tanggal Masuk / Tanggal Deal <span class="text-danger">*</span>
                    </label>
                    <input
                        type="date"
                        id="tanggalDaftar"
                        name="tanggal_daftar"
                        class="form-control"
                        value="<?= e($data['tanggal_daftar'] ?? '') ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold" for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="2"><?= e($data['alamat'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold" for="noTelp">
                        No. Telp / WA <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="noTelp"
                        name="no_telp"
                        class="form-control"
                        value="<?= e($data['no_telp'] ?? '') ?>"
                        required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold" for="institusi">Institusi</label>
                        <input
                            type="text"
                            id="institusi"
                            name="institusi"
                            class="form-control"
                            value="<?= e($data['institusi'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold" for="fakultas">Fakultas</label>
                        <input
                            type="text"
                            id="fakultas"
                            name="fakultas"
                            class="form-control"
                            value="<?= e($data['fakultas'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold" for="programStudi">Program Studi</label>
                    <input
                        type="text"
                        id="programStudi"
                        name="program_studi"
                        class="form-control"
                        value="<?= e($data['program_studi'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold" for="judulPenelitian">Judul Penelitian</label>
                    <textarea id="judulPenelitian" name="judul_penelitian" class="form-control" rows="2"><?= e($data['judul_penelitian'] ?? '') ?></textarea>
                </div>

                <datalist id="serviceSuggestions">
                    <?php foreach ($serviceSuggestions as $serviceName): ?>
                        <option value="<?= e($serviceName) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                
                <!-- HANYA STAFF YANG BISA MELIHAT BAGIAN LAYANAN -->
                <?php if ($is_staff): ?>
                <section class="service-section mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Layanan Utama yang Sedang Dikerjakan</h6>
                            <div class="service-help">
                                Pilih dari saran atau ketik nama layanan baru secara langsung.
                            </div>
                        </div>
                        <span class="badge text-bg-warning align-self-start">
                            Pesanan #<?= (int) $idPesanan ?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" for="jenisLayanan">
                            Jenis Layanan <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="jenisLayanan"
                            name="jenis_layanan"
                            class="form-control"
                            list="serviceSuggestions"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="Contoh: Analisis Statistik Lanjutan"
                            value="<?= e($data['jenis_layanan'] ?? '') ?>"
                            required>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold" for="nilaiDealing">
                                Nilai Kesepakatan (Dealing)
                            </label>
                            <input
                                type="number"
                                id="nilaiDealing"
                                name="nilai_dealing"
                                class="form-control"
                                min="0"
                                step="1"
                                value="<?= e($data['nilai_dealing'] ?? 0) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold" for="deadline">
                                Deadline Kerja <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                id="deadline"
                                name="deadline"
                                class="form-control"
                                value="<?= e($data['deadline'] ?? '') ?>"
                                required>
                        </div>
                    </div>
                </section>
                <section class="service-section mb-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Layanan Tambahan untuk Klien Ini</h6>
                            <div class="service-help">
                                Setiap layanan tambahan disimpan sebagai pesanan baru dan memiliki pembayaran termin sendiri.
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm fw-semibold"
                            id="addServiceButton">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Layanan Baru
                        </button>
                    </div>

                    <div id="newServicesContainer">
                        <?php foreach ($submittedNewServices as $newService): ?>
                            <div class="new-service-card">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-lg-5">
                                        <label class="form-label small fw-bold">Jenis Layanan Baru <span class="text-danger">*</span></label>
                                        <input
                                            type="text"
                                            name="layanan_baru_jenis[]"
                                            class="form-control"
                                            list="serviceSuggestions"
                                            maxlength="150"
                                            autocomplete="off"
                                            value="<?= e($newService['jenis_layanan']) ?>"
                                            required>
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label class="form-label small fw-bold">Nilai Dealing <span class="text-danger">*</span></label>
                                        <input
                                            type="number"
                                            name="layanan_baru_dealing[]"
                                            class="form-control"
                                            min="0"
                                            step="1"
                                            value="<?= e($newService['nilai_dealing']) ?>"
                                            required>
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-3">
                                        <label class="form-label small fw-bold">Deadline <span class="text-danger">*</span></label>
                                        <input
                                            type="date"
                                            name="layanan_baru_deadline[]"
                                            class="form-control"
                                            value="<?= e($newService['deadline']) ?>"
                                            required>
                                    </div>
                                    <div class="col-12 col-lg-1 d-grid">
                                        <button type="button" class="btn btn-outline-danger remove-service-btn" aria-label="Hapus layanan tambahan">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-muted small mt-3" id="emptyServiceMessage" <?= !empty($submittedNewServices) ? 'hidden' : '' ?>>
                        Belum ada layanan tambahan. Klik tombol “Tambah Layanan Baru” jika klien mengambil layanan lain.
                    </div>
                </section>
                <?php endif; ?>
                <!-- BATAS AKHIR TAMPILAN STAFF -->
                
                <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                    <i class="bi bi-check-circle me-1"></i>Simpan Perubahan Data
                </button>
                <a href="detail_klien.php?id=<?= (int) $idKlien ?>" class="btn btn-link w-100 text-muted mt-2 small">
                    Batal &amp; Kembali
                </a>
            </form>
        </div>
    </div>
</div>

<template id="newServiceTemplate">
    <div class="new-service-card">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label small fw-bold">Jenis Layanan Baru <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="layanan_baru_jenis[]"
                    class="form-control"
                    list="serviceSuggestions"
                    maxlength="150"
                    autocomplete="off"
                    placeholder="Ketik atau pilih layanan"
                    required>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small fw-bold">Nilai Dealing <span class="text-danger">*</span></label>
                <input
                    type="number"
                    name="layanan_baru_dealing[]"
                    class="form-control"
                    min="0"
                    step="1"
                    placeholder="Contoh: 2500000"
                    required>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small fw-bold">Deadline <span class="text-danger">*</span></label>
                <input
                    type="date"
                    name="layanan_baru_deadline[]"
                    class="form-control"
                    required>
            </div>
            <div class="col-12 col-lg-1 d-grid">
                <button type="button" class="btn btn-outline-danger remove-service-btn" aria-label="Hapus layanan tambahan">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    const photoInput = document.getElementById('fotoKlien');
    const photoPreview = document.getElementById('photoPreview');

    photoInput?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file || !photoPreview) return;

        const image = document.createElement('img');
        image.alt = 'Pratinjau foto klien';
        image.src = URL.createObjectURL(file);
        image.onload = () => URL.revokeObjectURL(image.src);

        photoPreview.replaceChildren(image);
    });

    const addButton = document.getElementById('addServiceButton');
    const container = document.getElementById('newServicesContainer');
    const template = document.getElementById('newServiceTemplate');
    const emptyMessage = document.getElementById('emptyServiceMessage');
    const maximumRows = 20;

    const updateEmptyState = () => {
        const rowCount = container?.querySelectorAll('.new-service-card').length ?? 0;
        if (emptyMessage) emptyMessage.hidden = rowCount > 0;
        if (addButton) addButton.disabled = rowCount >= maximumRows;
    };

    const bindRemoveButton = (scope) => {
        scope.querySelector('.remove-service-btn')?.addEventListener('click', () => {
            scope.remove();
            updateEmptyState();
        });
    };

    container?.querySelectorAll('.new-service-card').forEach(bindRemoveButton);

    addButton?.addEventListener('click', () => {
        if (!container || !template) return;

        const currentRows = container.querySelectorAll('.new-service-card').length;
        if (currentRows >= maximumRows) return;

        const fragment = template.content.cloneNode(true);
        const newCard = fragment.querySelector('.new-service-card');
        if (!newCard) return;

        bindRemoveButton(newCard);
        container.appendChild(fragment);
        updateEmptyState();

        newCard.querySelector('input[name="layanan_baru_jenis[]"]')?.focus();
    });

    updateEmptyState();
})();
</script>
</body>
</html>
