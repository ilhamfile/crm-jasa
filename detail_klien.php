<?php

require_once 'config.php';

/* ==========================================================
   HAK AKSES
========================================================== */

$current_privilege = 'Guest';
$nama_user = 'Tamu (Read Only)';

if (isset($_SESSION['user_id'])) {

    $stmtUser = $pdo->prepare("
        SELECT privileges, nama_lengkap
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmtUser->execute([
        $_SESSION['user_id']
    ]);

    if ($user = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
        $current_privilege = $user['privileges'];
        $nama_user = $user['nama_lengkap'];
    }
}


/* Token keamanan khusus penghapusan pesanan. */
if (empty($_SESSION['csrf_delete_order'])) {
    $_SESSION['csrf_delete_order'] = bin2hex(random_bytes(32));
}

$deleteOrderCsrfToken = (string) $_SESSION['csrf_delete_order'];


/* ==========================================================
   HELPER
========================================================== */

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function rupiahDetail($angka): string
{
    return 'Rp ' . number_format(
        (float) $angka,
        0,
        ',',
        '.'
    );
}


function tanggalIndonesia(?string $tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    $timestamp = strtotime($tanggal);

    if ($timestamp === false) {
        return '-';
    }

    $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    return date('d', $timestamp) .
        ' ' .
        $bulan[(int) date('n', $timestamp)] .
        ' ' .
        date('Y', $timestamp);
}


function deadlineInfo(?string $deadline): array
{
    if (empty($deadline)) {
        return [
            'class' => 'normal',
            'label' => 'Belum ditentukan',
            'date'  => '-',
        ];
    }

    $deadlineTimestamp = strtotime($deadline);

    if ($deadlineTimestamp === false) {
        return [
            'class' => 'normal',
            'label' => 'Belum ditentukan',
            'date'  => '-',
        ];
    }

    $today = strtotime(date('Y-m-d'));

    $difference = (int) floor(
        ($deadlineTimestamp - $today) / 86400
    );

    if ($difference < 0) {
        $class = 'overdue';
        $label = 'Terlambat ' .
            abs($difference) .
            ' hari';
    } elseif ($difference === 0) {
        $class = 'today';
        $label = 'Hari ini';
    } elseif ($difference === 1) {
        $class = 'tomorrow';
        $label = 'Besok';
    } elseif ($difference <= 7) {
        $class = 'week';
        $label = $difference . ' hari lagi';
    } else {
        $class = 'normal';
        $label = tanggalIndonesia($deadline);
    }

    return [
        'class' => $class,
        'label' => $label,
        'date'  => tanggalIndonesia($deadline),
    ];
}


function statusInfo(?string $status): array
{
    $status = strtolower(
        trim((string) $status)
    );

    if ($status === 'selesai') {
        return [
            'class' => 'success',
            'icon'  => 'bi-check-circle-fill',
            'label' => 'Selesai',
        ];
    }

    if ($status === 'tertunda') {
        return [
            'class' => 'warning',
            'icon'  => 'bi-pause-circle-fill',
            'label' => 'Tertunda',
        ];
    }

    return [
        'class' => 'danger',
        'icon'  => 'bi-hourglass-split',
        'label' => 'Belum',
    ];
}


function whatsappNumber(?string $phone): string
{
    $number = preg_replace(
        '/[^0-9]/',
        '',
        (string) $phone
    );

    if (strpos($number, '0') === 0) {
        $number = '62' . substr($number, 1);
    }

    return $number;
}


/* ==========================================================
   PARAMETER
========================================================== */

$idKlien = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idKlien) {
    http_response_code(400);
    exit('ID klien tidak valid.');
}

$backPage = $_GET['back'] ?? 'index.php';

$allowedBackPages = [
    'index.php',
    'teuing.php',
];

if (!in_array($backPage, $allowedBackPages, true)) {
    $backPage = 'teuing.php';
}


/* ==========================================================
   HAPUS PESANAN
========================================================== */

$deleteOrderError = '';
$deleteOrderSuccess = isset($_GET['pesanan_dihapus'])
    && $_GET['pesanan_dihapus'] === '1';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'hapus_pesanan'
) {
    if ($current_privilege !== 'Admin') {
        http_response_code(403);
        exit('Hanya Admin yang dapat menghapus pesanan.');
    }

    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (
        $submittedToken === '' ||
        !hash_equals($deleteOrderCsrfToken, $submittedToken)
    ) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Silakan muat ulang halaman.');
    }

    $idPesananHapus = filter_input(
        INPUT_POST,
        'id_pesanan',
        FILTER_VALIDATE_INT
    );

    if (!$idPesananHapus) {
        $deleteOrderError = 'ID pesanan tidak valid.';
    } else {
        try {
            $stmtCariPesanan = $pdo->prepare("
                SELECT id_pesanan, file_mou
                FROM pesanan_layanan
                WHERE id_pesanan = ?
                  AND id_klien = ?
                LIMIT 1
            ");

            $stmtCariPesanan->execute([
                $idPesananHapus,
                $idKlien,
            ]);

            $pesananHapus = $stmtCariPesanan->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$pesananHapus) {
                throw new RuntimeException(
                    'Pesanan tidak ditemukan atau bukan milik klien ini.'
                );
            }

            $pdo->beginTransaction();

            /* Tetap dihapus eksplisit agar kompatibel dengan database
               hosting yang belum memakai ON DELETE CASCADE. */
            $stmtHapusPembayaran = $pdo->prepare("
                DELETE FROM pembayaran_termin
                WHERE id_pesanan = ?
            ");

            $stmtHapusPembayaran->execute([
                $idPesananHapus,
            ]);

            $stmtHapusPesanan = $pdo->prepare("
                DELETE FROM pesanan_layanan
                WHERE id_pesanan = ?
                  AND id_klien = ?
            ");

            $stmtHapusPesanan->execute([
                $idPesananHapus,
                $idKlien,
            ]);

            if ($stmtHapusPesanan->rowCount() !== 1) {
                throw new RuntimeException(
                    'Pesanan gagal dihapus dari database.'
                );
            }

            $pdo->commit();

            /* Hapus file MoU milik pesanan setelah transaksi berhasil. */
            $fileMou = basename(
                str_replace(
                    '\\',
                    '/',
                    (string) ($pesananHapus['file_mou'] ?? '')
                )
            );

            if ($fileMou !== '') {
                $mouPath = __DIR__ . '/uploads/' . $fileMou;

                if (is_file($mouPath)) {
                    @unlink($mouPath);
                }
            }

            $_SESSION['csrf_delete_order'] = bin2hex(
                random_bytes(32)
            );

            $redirectUrl = 'detail_klien.php?id=' .
                (int) $idKlien .
                '&back=' . urlencode($backPage) .
                '&pesanan_dihapus=1';

            header('Location: ' . $redirectUrl);
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $deleteOrderError = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Pesanan gagal dihapus. Silakan coba kembali.';
        }
    }
}


/* ==========================================================
   DATA KLIEN
========================================================== */

$stmtKlien = $pdo->prepare("
    SELECT
        id_klien,
        nama_lengkap,
        foto_klien,
        alamat,
        tanggal_masuk,
        no_telp,
        institusi,
        fakultas,
        program_studi,
        judul_penelitian,
        tanggal_daftar
    FROM klien
    WHERE id_klien = ?
    LIMIT 1
");

$stmtKlien->execute([
    $idKlien
]);

$klien = $stmtKlien->fetch(
    PDO::FETCH_ASSOC
);

if (!$klien) {
    http_response_code(404);
    exit('Data klien tidak ditemukan.');
}


/* ==========================================================
   DATA PESANAN
========================================================== */

$stmtPesanan = $pdo->prepare("
    SELECT
        p.id_pesanan,
        p.id_klien,
        p.jenis_layanan,
        p.nilai_dealing,
        p.deadline,
        p.status_pelunasan,
        p.file_mou,

        COALESCE(t.termin_1, 0) AS termin_1,
        COALESCE(t.termin_2, 0) AS termin_2,
        COALESCE(t.termin_3, 0) AS termin_3,

        (
            COALESCE(t.termin_1, 0) +
            COALESCE(t.termin_2, 0) +
            COALESCE(t.termin_3, 0)
        ) AS total_bayar,

        GREATEST(
            COALESCE(p.nilai_dealing, 0) -
            (
                COALESCE(t.termin_1, 0) +
                COALESCE(t.termin_2, 0) +
                COALESCE(t.termin_3, 0)
            ),
            0
        ) AS sisa_piutang,

        CASE
            WHEN COALESCE(p.nilai_dealing, 0) > 0
            THEN LEAST(
                100,
                ROUND(
                    (
                        COALESCE(t.termin_1, 0) +
                        COALESCE(t.termin_2, 0) +
                        COALESCE(t.termin_3, 0)
                    ) /
                    p.nilai_dealing *
                    100,
                    1
                )
            )
            ELSE 0
        END AS progress_bayar

    FROM pesanan_layanan p

    LEFT JOIN pembayaran_termin t
        ON p.id_pesanan = t.id_pesanan

    WHERE p.id_klien = ?

    ORDER BY
        p.id_pesanan DESC
");

$stmtPesanan->execute([
    $idKlien
]);

$pesananList = $stmtPesanan->fetchAll(
    PDO::FETCH_ASSOC
);


/* ==========================================================
   RINGKASAN
========================================================== */

$totalDealing = 0;
$totalDibayar = 0;
$totalPiutang = 0;

foreach ($pesananList as $pesanan) {
    $totalDealing +=
        (float) $pesanan['nilai_dealing'];

    $totalDibayar +=
        (float) $pesanan['total_bayar'];

    $totalPiutang +=
        (float) $pesanan['sisa_piutang'];
}

$progressKeseluruhan = $totalDealing > 0
    ? min(
        100,
        round(
            ($totalDibayar / $totalDealing) * 100,
            1
        )
    )
    : 0;

$pesananTerbaru = $pesananList[0] ?? null;


/* ==========================================================
   FOTO
========================================================== */

$fotoRelative = ltrim(
    str_replace(
        '\\',
        '/',
        (string) ($klien['foto_klien'] ?? '')
    ),
    '/'
);

$fotoValid =
    $fotoRelative !== '' &&
    strpos(
        $fotoRelative,
        'uploads/foto_klien/'
    ) === 0 &&
    is_file(__DIR__ . '/' . $fotoRelative);

$namaLengkap = trim(
    (string) $klien['nama_lengkap']
);

$initial = $namaLengkap !== ''
    ? strtoupper(substr($namaLengkap, 0, 1))
    : '?';

$waNumber = whatsappNumber(
    $klien['no_telp']
);

$pageTitle = 'Klien';

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<meta
    name="theme-color"
    content="#2563eb">

<title><?= e($pageTitle) ?> - <?= e($namaLengkap) ?> |    PT. Lentera Statistics Indonesia</title>

<link rel="icon" type="image/webp" href="assets/img/logo.webp">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet">

<link
    href="assets/css/common.css"
    rel="stylesheet">

<link
    href="assets/css/dashboard.css"
    rel="stylesheet">

<link
    href="assets/css/client.css"
    rel="stylesheet">

<style>
.client-delete-order-form {
    display: inline-flex;
    margin: 0;
}

.client-delete-order-form .btn {
    white-space: nowrap;
}

@media (max-width: 575.98px) {
    .client-order-actions .client-delete-order-form {
        width: 100%;
    }

    .client-order-actions .client-delete-order-form .btn {
        width: 100%;
    }
}
</style>

</head>

<body>

<div class="dashboard-container">

    <!-- HEADER -->

    <header class="app-header">

        <div class="app-header-content">
            <img src="assets/img/logo.webp" alt="Logo PT. Lentera Statistics Indonesia" class="company-logo">
            <div class="company-info">
                <h1 class="company-title">
                    PT. LENTERA STATISTICS INDONESIA
                </h1>
                <div class="company-tagline" style="display:none">
                    Bimbingan Skripsi • Tesis • Disertasi
                </div>
                <div class="company-address" style="display:none">
                    Konsultan Statistik & Penelitian Akademik
                </div>

            </div>

        </div>

    </header>


    <!-- TOOLBAR -->
    <div class="client-page-toolbar">
        <div>
            <!-- Tampilkan tombol Kembali HANYA jika yang login BUKAN Klien -->
            <?php if (!isset($_SESSION['klien_id'])): ?>
                <a
                    href="<?= e($backPage) ?>"
                    class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Kembali
                </a>
            <?php endif; ?>
        </div>
        
        <div class="client-toolbar-actions">
            <!-- (Tombol Edit Klien untuk Staff tetap ada di sini) -->
            <?php if ($current_privilege !== 'Guest' && $pesananTerbaru): ?>
                <a href="edit_data.php?id_klien=<?= (int) $idKlien ?>&id_pesanan=<?= (int) $pesananTerbaru['id_pesanan'] ?>&back=<?= urlencode($backPage) ?>" class="btn btn-warning">
                    <i class="bi bi-pencil-square"></i> Edit Klien
                </a>
            <?php endif; ?>

        </div>

    </div>

    <?php if ($deleteOrderSuccess): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert">

            <i class="bi bi-check-circle-fill me-1"></i>
            Pesanan berhasil dihapus. Data klien tetap tersimpan.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup"></button>

        </div>

    <?php elseif ($deleteOrderError !== ''): ?>

        <div
            class="alert alert-danger"
            role="alert">

            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <?= e($deleteOrderError) ?>

        </div>

    <?php endif; ?>


    <!-- PROFIL -->

    <section class="client-profile-card">

        <div class="client-profile-photo">

            <?php if ($fotoValid): ?>

                <img
                    src="<?= e($fotoRelative) ?>"
                    alt="Foto <?= e($namaLengkap) ?>">

            <?php else: ?>

                <span>
                    <?= e($initial) ?>
                </span>

            <?php endif; ?>

        </div>

        <div class="client-profile-content">

            <div class="client-profile-heading">

                <div>

                    <h2>
                        <?= e($namaLengkap) ?>
                    </h2>

                    <p>
                        <?= e(
                            $klien['institusi']
                            ?: 'Institusi belum dicantumkan'
                        ) ?>
                    </p>

                </div>

                <div class="client-profile-badges">
                    <span class="badge-soft-primary">
                        <i class="bi bi-person-badge"></i>
                        Klien #<?= (int) $idKlien ?>
                    </span>
                    <?php if (!empty($pesananList)): ?>
                        <?php 
                        // Menyaring agar nama layanan yang sama tidak ditampilkan berulang (opsional tapi lebih rapi)
                        $layanan_ditampilkan = [];
                        foreach ($pesananList as $pesanan) {
                            if (!in_array($pesanan['jenis_layanan'], $layanan_ditampilkan)) {
                                $layanan_ditampilkan[] = $pesanan['jenis_layanan'];
                        ?>
                                <span class="badge-soft-success">
                                    <i class="bi bi-journal-check"></i>
                                    <?= e($pesanan['jenis_layanan']) ?>
                                </span>
                        <?php 
                            }
                        } 
                        ?>
                    <?php endif; ?>
                </div>

            </div>

            <div class="client-contact-actions">
                
                <!-- Tombol Edit Profile Khusus Klien (Berada tepat di sebelah WhatsApp) -->
                <?php if (isset($_SESSION['klien_id']) && $_SESSION['klien_id'] == $idKlien && !empty($pesananTerbaru)): ?>
                <a 
                    href="edit_data.php?id_klien=<?= (int) $idKlien ?>&id_pesanan=<?= (int) $pesananTerbaru['id_pesanan'] ?>&back=<?= urlencode($backPage) ?>" 
                    class="btn btn-warning fw-bold">
                    <i class="bi bi-person-lines-fill"></i> Edit Profile
                    </a>
                <?php endif; ?>
                
                <!-- Tampilkan tombol kontak HANYA jika yang login adalah Staff/Admin ATAU Klien pemilik pesanan -->
                <?php if (isset($_SESSION['user_id']) || (isset($_SESSION['klien_id']) && $_SESSION['klien_id'] == $idKlien)): ?>
                    
                    <?php if ($waNumber !== ''): ?>
                        <a
                            href="https://wa.me/<?= e($waNumber) ?>"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-success">
                            <i class="bi bi-whatsapp"></i>
                            WhatsApp
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($klien['no_telp'])): ?>
                        <a
                            href="tel:<?= e($klien['no_telp']) ?>"
                            class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i>
                            Hubungi
                        </a>
                    <?php endif; ?>
            
                <?php endif; ?>
            
                <!-- Tombol Portal Klien / Logout Klien -->
                    <?php if (isset($_SESSION['klien_id'])): ?>
                        <a href="logout_klien.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout Klien</a>
                    <!-- Tampilkan Portal Klien HANYA jika bukan Staff (Guest/Tamu) -->
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login_klien.php" target="_blank" class="btn btn-outline-info"><i class="bi bi-box-arrow-in-right"></i> Portal Klien</a>
                    <?php endif; ?>
                
            </div>

        </div>

    </section>


    <!-- STATISTIK -->

    <section class="client-stat-grid">

        <div class="client-stat-card stat-primary">

            <div class="client-stat-icon">
                <i class="bi bi-journal-richtext"></i>
            </div>

            <div>
                <strong>
                    <?= number_format(
                        count($pesananList)
                    ) ?>
                </strong>
                <span>Total Pesanan</span>
            </div>

        </div>

        <div class="client-stat-card stat-warning">

            <div class="client-stat-icon">
                <i class="bi bi-wallet2"></i>
            </div>

            <div>
                <strong>
                    <?= rupiahDetail($totalDealing) ?>
                </strong>
                <span>Total Dealing</span>
            </div>

        </div>

        <div class="client-stat-card stat-success">

            <div class="client-stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>

            <div>
                <strong>
                    <?= rupiahDetail($totalDibayar) ?>
                </strong>
                <span>Sudah Dibayar</span>
            </div>

        </div>

        <div class="client-stat-card stat-danger">

            <div class="client-stat-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div>
                <strong>
                    <?= rupiahDetail($totalPiutang) ?>
                </strong>
                <span>Sisa Piutang</span>
            </div>

        </div>

    </section>


    <!-- DATA DASAR -->

    <div class="client-detail-grid">

        <section class="client-info-card">

            <h3 class="client-section-title">

                <i class="bi bi-person-vcard"></i>
                Informasi Klien

            </h3>

            <div class="client-info-list">

                <div class="client-info-row">

                    <span>
                        <i class="bi bi-whatsapp"></i>
                        Nomor Telepon
                    </span>

                    <strong>
                        <?= e(
                            $klien['no_telp'] ?: '-'
                        ) ?>
                    </strong>

                </div>

                <div class="client-info-row">

                    <span>
                        <i class="bi bi-building"></i>
                        Institusi
                    </span>

                    <strong>
                        <?= e(
                            $klien['institusi'] ?: '-'
                        ) ?>
                    </strong>

                </div>

                <div class="client-info-row">

                    <span>
                        <i class="bi bi-diagram-3"></i>
                        Fakultas
                    </span>

                    <strong>
                        <?= e(
                            $klien['fakultas'] ?: '-'
                        ) ?>
                    </strong>

                </div>

                <div class="client-info-row">

                    <span>
                        <i class="bi bi-mortarboard"></i>
                        Program Studi
                    </span>

                    <strong>
                        <?= e(
                            $klien['program_studi'] ?: '-'
                        ) ?>
                    </strong>

                </div>

                <div class="client-info-row">

                    <span>
                        <i class="bi bi-calendar-check"></i>
                        Tanggal Daftar
                    </span>

                    <strong>
                        <?= e(
                            tanggalIndonesia(
                                $klien['tanggal_daftar']
                            )
                        ) ?>
                    </strong>

                </div>

            </div>

        </section>


        <section class="client-info-card">

            <h3 class="client-section-title">

                <i class="bi bi-geo-alt"></i>
                Alamat & Penelitian

            </h3>

            <div class="client-research-block">

                <span>Alamat</span>

                <p>
                    <?= nl2br(
                        e(
                            $klien['alamat']
                            ?: 'Alamat belum dicantumkan.'
                        )
                    ) ?>
                </p>

            </div>

            <div class="client-research-block">

                <span>Judul Penelitian</span>

                <p>
                    <?= nl2br(
                        e(
                            $klien['judul_penelitian']
                            ?: 'Judul penelitian belum dicantumkan.'
                        )
                    ) ?>
                </p>

            </div>

        </section>

    </div>


    <!-- PROGRESS KESELURUHAN -->

    <section class="client-info-card client-payment-overview">

        <div class="client-payment-heading">

            <div>

                <h3 class="client-section-title">

                    <i class="bi bi-graph-up-arrow"></i>
                    Progress Pembayaran Keseluruhan

                </h3>

                <p>
                    Perbandingan total pembayaran
                    dengan seluruh nilai dealing.
                </p>

            </div>

            <strong>
                <?= number_format(
                    $progressKeseluruhan,
                    1,
                    ',',
                    '.'
                ) ?>%
            </strong>

        </div>

        <div class="progress client-main-progress">

            <div
                class="progress-bar <?= $progressKeseluruhan >= 80
                    ? 'progress-high'
                    : (
                        $progressKeseluruhan >= 30
                        ? 'progress-medium'
                        : 'progress-low'
                    ) ?>"
                style="width:<?= e($progressKeseluruhan) ?>%">
            </div>

        </div>

    </section>


    <!-- DAFTAR PESANAN -->

    <section class="client-info-card">

        <div class="client-orders-header d-flex justify-content-between align-items-start">
            <div>
                <h3 class="client-section-title">
                    <i class="bi bi-journal-text"></i>
                    Riwayat Pesanan
                </h3>
                <p>
                    Semua layanan dan transaksi milik klien.
                </p>
            </div>
            <?php if ($current_privilege !== 'Guest' && !empty($pesananTerbaru)): ?>
                <div>
                    <a
                        href="edit_data.php?id_klien=<?= (int) $idKlien ?>&id_pesanan=<?= (int) $pesananTerbaru['id_pesanan'] ?>&back=<?= urlencode($backPage) ?>#newServicesContainer"
                        class="btn btn-outline-primary btn-sm fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Layanan Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($pesananList)): ?>
            <div class="client-empty-state">
                <i class="bi bi-inbox"></i>
                <h4>Belum ada pesanan</h4>
                <p>
                    Klien ini belum memiliki data layanan.
                </p>
            </div>

        <?php else: ?>

            <div class="client-order-list">
                <?php foreach ($pesananList as $pesanan): ?>
                    <?php
                    $status =
                        statusInfo(
                            $pesanan['status_pelunasan']
                        );
                    $deadline =
                        deadlineInfo(
                            $pesanan['deadline']
                        );
                    $progress = max(
                        0,
                        min(
                            100,
                            (float) $pesanan['progress_bayar']
                        )
                    );
                    ?>

                    <article class="client-order-card">

                        <div class="client-order-top">

                            <div>

                                <span class="client-order-number">
                                    Pesanan
                                    #<?= (int) $pesanan['id_pesanan'] ?>
                                </span>

                                <h4>
                                    <?= e(
                                        $pesanan['jenis_layanan']
                                    ) ?>
                                </h4>

                            </div>

                            <span
                                class="client-status client-status-<?= e($status['class']) ?>">

                                <i class="bi <?= e($status['icon']) ?>"></i>

                                <?= e($status['label']) ?>

                            </span>

                        </div>

                        <div class="client-order-values">

                            <div>

                                <span>Nilai Dealing</span>

                                <strong>
                                    <?= rupiahDetail(
                                        $pesanan['nilai_dealing']
                                    ) ?>
                                </strong>

                            </div>

                            <div>

                                <span>Sudah Dibayar</span>

                                <strong>
                                    <?= rupiahDetail(
                                        $pesanan['total_bayar']
                                    ) ?>
                                </strong>

                            </div>

                            <div>

                                <span>Sisa</span>

                                <strong>
                                    <?= rupiahDetail(
                                        $pesanan['sisa_piutang']
                                    ) ?>
                                </strong>

                            </div>

                            <div>

                                <span>Deadline</span>

                                <strong class="deadline <?= e($deadline['class']) ?>">

                                    <?= e($deadline['label']) ?>

                                </strong>

                                <small>
                                    <?= e($deadline['date']) ?>
                                </small>

                            </div>

                        </div>

                        <div class="client-order-progress">

                            <div>

                                <span>Progress Pembayaran</span>

                                <strong>
                                    <?= number_format(
                                        $progress,
                                        1,
                                        ',',
                                        '.'
                                    ) ?>%
                                </strong>

                            </div>

                            <div class="progress">

                                <div
                                    class="progress-bar <?= $progress >= 80
                                        ? 'progress-high'
                                        : (
                                            $progress >= 30
                                            ? 'progress-medium'
                                            : 'progress-low'
                                        ) ?>"
                                    style="width:<?= e($progress) ?>%">
                                </div>

                            </div>

                        </div>

                        <div class="client-order-actions">
                            <?php if ($current_privilege !== 'Guest'): ?>
                                <a
                                    href="update_bayar.php?id=<?= (int) $pesanan['id_pesanan'] ?>"
                                    class="btn btn-success">

                                    <i class="bi bi-cash-stack"></i>
                                    Pembayaran
                                </a>
                                <a
                                    href="edit_data.php?id_klien=<?= (int) $idKlien ?>&id_pesanan=<?= (int) $pesanan['id_pesanan'] ?>&back=<?= urlencode($backPage) ?>"
                                    class="btn btn-warning">

                                    <i class="bi bi-pencil-square"></i>
                                    Edit
                                </a>

                                <?php if ($current_privilege === 'Admin'): ?>
                                    <form
                                        method="post"
                                        action="detail_klien.php?id=<?= (int) $idKlien ?>&back=<?= urlencode($backPage) ?>"
                                        class="client-delete-order-form"
                                        onsubmit="return confirm('Yakin ingin menghapus pesanan ini? Data pembayaran dan file MoU yang terkait juga akan dihapus. Data klien tidak ikut terhapus.');">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="hapus_pesanan">

                                        <input
                                            type="hidden"
                                            name="id_pesanan"
                                            value="<?= (int) $pesanan['id_pesanan'] ?>">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($deleteOrderCsrfToken) ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-danger">

                                            <i class="bi bi-trash3"></i>
                                            Hapus Pesanan

                                        </button>

                                    </form>

                                <?php endif; ?>

                            <?php endif; ?>

                            <a
                                href="cetak_invoice.php?id=<?= (int) $pesanan['id_pesanan'] ?>"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-outline-primary">

                                <i class="bi bi-printer"></i>
                                Invoice

                            </a>

                            <?php if (!empty($pesanan['file_mou'])): ?>
                                <a
                                    href="uploads/<?= rawurlencode(basename($pesanan['file_mou'])) ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-file-earmark-check"></i>
                                    Lihat MoU
                                </a>
                                <?php if ($current_privilege !== 'Guest'): ?>
                                    <a
                                        href="upload_mou.php?id=<?= (int) $pesanan['id_pesanan'] ?>"
                                        class="btn btn-outline-info">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                        Ganti MoU
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Tombol Lihat Pengerjaan (Milestone) -->
                                <?php if (isset($_SESSION['user_id']) || (isset($_SESSION['klien_id']) && $_SESSION['klien_id'] == $idKlien)): ?>
                                <a href="milestone.php?id_pesanan=<?= (int) $pesanan['id_pesanan'] ?>" class="btn btn-primary fw-bold text-white">
                                <i class="bi bi-list-task"></i> Lihat Pengerjaan
                                </a>
                                <?php endif; ?>
                                <?php if ($current_privilege !== 'Guest'): ?>
                                    <a
                                        href="upload_mou.php?id=<?= (int) $pesanan['id_pesanan'] ?>"
                                        class="btn btn-info text-white">
                                        <i class="bi bi-file-earmark-plus"></i>
                                        Upload MoU
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>