<?php

require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ==========================================================
   AUTHENTICATION
========================================================== */
$current_privilege = 'Guest';
$nama_user = 'Tamu (Read Only)';

// [KEAMANAN TAMBAHAN] Jika Klien mencoba mengakses dashboard utama, paksa kembali ke profilnya!
if (isset($_SESSION['klien_id'])) {
    header("Location: detail_klien.php?id=" . $_SESSION['klien_id']);
    exit();
}

if (isset($_SESSION['user_id'])) {

    $stmtUser = $pdo->prepare("
        SELECT
            privileges,
            nama_lengkap
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

/* ==========================================================
   DASHBOARD SUMMARY
========================================================== */

$totalKlien = (int) $pdo
    ->query("SELECT COUNT(*) FROM klien")
    ->fetchColumn();

$totalPesanan = (int) $pdo
    ->query("SELECT COUNT(*) FROM pesanan_layanan")
    ->fetchColumn();

$totalDeal = (float) $pdo
    ->query("
        SELECT COALESCE(SUM(nilai_dealing), 0)
        FROM pesanan_layanan
    ")
    ->fetchColumn();

$totalBayar = (float) $pdo
    ->query("
        SELECT COALESCE(
            SUM(
                IFNULL(termin_1, 0) +
                IFNULL(termin_2, 0) +
                IFNULL(termin_3, 0)
            ),
            0
        )
        FROM pembayaran_termin
    ")
    ->fetchColumn();

$totalPiutang = max(0, $totalDeal - $totalBayar);


/* ==========================================================
   DEADLINE 7 HARI
========================================================== */

$totalDeadline = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM pesanan_layanan
        WHERE deadline BETWEEN CURDATE()
        AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ")
    ->fetchColumn();

/* ==========================================================
   FUNGSI
========================================================== */

function rupiah($angka)
{

    return 'Rp '

    .

    number_format(

        $angka,

        0,

        ',',

        '.'

    );

}


/* ==========================================================
   FILTER
========================================================== */

$start_date =

$_GET['start_date']

??

'';


$end_date =

$_GET['end_date']

??

'';


/* ==========================================================
   QUERY DATA KLIEN
========================================================== */

$sql = "
SELECT
    k.*,

    p.id_pesanan,
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

    CASE
        WHEN COALESCE(p.nilai_dealing, 0) > 0 THEN
            LEAST(
                100,
                ROUND(
                    (
                        (
                            COALESCE(t.termin_1, 0) +
                            COALESCE(t.termin_2, 0) +
                            COALESCE(t.termin_3, 0)
                        ) / p.nilai_dealing
                    ) * 100,
                    1
                )
            )
        ELSE 0
    END AS progress_bayar,

    GREATEST(
        COALESCE(p.nilai_dealing, 0) -
        (
            COALESCE(t.termin_1, 0) +
            COALESCE(t.termin_2, 0) +
            COALESCE(t.termin_3, 0)
        ),
        0
    ) AS sisa_piutang

FROM klien k

INNER JOIN pesanan_layanan p
    ON k.id_klien = p.id_klien

LEFT JOIN pembayaran_termin t
    ON p.id_pesanan = t.id_pesanan
";


$params = [];

if (

!empty($start_date)

&&

!empty($end_date)

){

$sql .= "

WHERE

k.tanggal_daftar

BETWEEN

:start

AND

:end

";

$params = [

'start'=>$start_date,

'end'=>$end_date

];

}


$sql .= "

ORDER BY

p.deadline ASC

";


$stmt =

$pdo->prepare($sql);

$stmt->execute($params);

$klien_list =

$stmt->fetchAll(

PDO::FETCH_ASSOC

);


/* ==========================================================
   DASHBOARD PROGRESS
========================================================== */

$persentasePembayaran = 0;

if ($totalDeal > 0) {

    $persentasePembayaran = min(
        100,
        round(
            ($totalBayar / $totalDeal) * 100,
            1
        )
    );

}


/* ==========================================================
   PAGE INFO
========================================================== */

$pageTitle =

'Dashboard';

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="description" content="Dashboard - PT. Lentera Statistics Indonesia">
<meta name="author" content="PT. Lentera Statistics Indonesia">
<meta name="theme-color" content="#2563eb">
<title><?= htmlspecialchars($pageTitle) ?> | PT. Lentera Statistics Indonesia</title>

<!-- FAVICON -->
<link rel="icon" type="image/webp" href="assets/img/logo.webp">

<!-- GOOGLE FONT -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- BOOTSTRAP ICON -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- DATATABLE -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- THEME -->
<link rel="stylesheet" href="assets/css/common.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
<div class="dashboard-container">
    <header class="app-header">
      <div class="app-header-content"><img src="assets/img/logo.webp" alt="Logo PT. Lentera Statistics Indonesia" class="company-logo">
        <div class="company-info">
          <h1 class="company-title">PT. LENTERA STATISTICS INDONESIA</h1>
          <div class="company-tagline" style="display:none">Bimbingan Skripsi • Tesis • Disertasi</div>
          <div class="company-address" style="display:none">Konsultan Statistik & Penelitian Akademik</div>
        </div>
      </div>
    </header>
    <nav class="menu-bar">
      <div class="session-info"><span class="badge-session"><i class="bi bi-person-circle"></i><?= htmlspecialchars($current_privilege) ?>(<?= htmlspecialchars($nama_user) ?>)</span></div>
      <div class="menu-action">
          <?php if ($current_privilege !== 'Guest'): ?>
          <a href="tambah.php" class="btn btn-primary btn-menu"><i class="bi bi-person-plus-fill"></i>Tambah Klien</a>
          <a href="pengeluaran.php" class="btn btn-danger btn-menu"><i class="bi bi-cash-stack"></i>Kas Keluar</a>
          <a href="laporan_laba_rugi.php" class="btn btn-success btn-menu"><i class="bi bi-bar-chart"></i>Laporan</a>
        <div class="btn-group"><button class="btn btn-warning dropdown-toggle btn-menu" data-bs-toggle="dropdown"><i class="bi bi-people-fill"></i>User</button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="tambah_user.php"><i class="bi bi-person-plus"></i>Tambah User</a></li>
            <li><a class="dropdown-item" href="kelola_user.php"><i class="bi bi-pencil-square"></i>Edit User</a></li>
          </ul>
        </div><a href="logout.php" class="btn btn-secondary btn-menu" onclick="return confirm('Yakin ingin logout?')"><i class="bi bi-box-arrow-right"></i>Logout</a><?php else: ?><a href="login.php" class="btn btn-primary btn-menu"><i class="bi bi-box-arrow-in-right"></i>Login</a><?php endif; ?>
      </div>
    </nav>
    <section class="summary-grid">
          <div class="summary-card summary-primary">
            <div class="summary-icon"><i class="bi bi-people-fill"></i></div>
            <div class="summary-value"><?= number_format($totalKlien) ?></div>
            <div class="summary-label">Total Klien</div>
            <div class="summary-desc">Seluruh klien yang telah terdaftar.</div>
          </div>
          <div class="summary-card summary-success">
            <div class="summary-icon"><i class="bi bi-journal-check"></i></div>
            <div class="summary-value"><?= number_format($totalPesanan) ?></div>
            <div class="summary-label">Total Pesanan</div>
            <div class="summary-desc">Semua proyek yang sedang berjalan.</div>
          </div>
          <div class="summary-card summary-danger">
            <div class="summary-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="summary-value"><?= number_format($totalDeadline) ?></div>
            <div class="summary-label">Deadline 7 Hari</div>
            <div class="summary-desc">Segera membutuhkan tindak lanjut.</div>
          </div>
          <div class="summary-card summary-warning">
            <div class="summary-icon"><i class="bi bi-wallet2"></i></div>
            <div class="summary-value"><?= rupiah($totalDeal) ?></div>
            <div class="summary-label">Nilai Dealing</div>
            <div class="summary-desc">Total nilai seluruh kontrak.</div>
          </div>
          <div class="summary-card summary-info">
            <div class="summary-icon"><i class="bi bi-credit-card-2-front"></i></div>
            <div class="summary-value"><?= rupiah($totalBayar) ?></div>
            <div class="summary-label">Pembayaran Masuk</div>
            <div class="summary-desc">Total pembayaran yang diterima.</div>
          </div>
          <div class="summary-card summary-primary">
            <div class="summary-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="summary-value"><?= rupiah($totalPiutang) ?></div>
            <div class="summary-label">Total Piutang</div>
            <div class="summary-desc">Sisa pembayaran yang belum diterima.</div>
          </div>
    </section>
    <section class="dashboard-content">
      <div class="filter-card">
        <h4 class="section-title"><i class="bi bi-funnel"></i>Filter Data</h4>
        <form method="GET">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Tanggal Awal</label><input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></div>
            <div class="col-md-6"><label class="form-label">Tanggal Akhir</label><input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></div>
          </div>
          <div class="d-flex gap-2 mt-4"><button class="btn btn-primary"><i class="bi bi-search"></i>Terapkan Filter</button><a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i>Reset</a></div>
        </form>
      </div>
      <!-- INSIGHT -->
      <aside class="insight-card">
        <h4 class="section-title"><i class="bi bi-graph-up-arrow"></i>Dashboard Insight</h4>
        <div class="insight-item">
          <div class="insight-icon bg-primary"><i class="bi bi-wallet2"></i></div>
          <div><strong><?= $persentasePembayaran ?>%</strong><small>Progress Pembayaran</small>
            <div class="progress mt-2">
              <div class="progress-bar bg-primary" style="width:<?= $persentasePembayaran ?>%"></div>
            </div>
          </div>
        </div>
        <div class="insight-item">
          <div class="insight-icon bg-success"><i class="bi bi-people-fill"></i></div>
          <div><strong><?= number_format($totalKlien) ?></strong><small>Klien Aktif</small></div>
        </div>
        <div class="insight-item">
          <div class="insight-icon bg-warning"><i class="bi bi-journal-check"></i></div>
          <div><strong><?= number_format($totalPesanan) ?></strong><small>Total Pesanan</small></div>
        </div>
        <div class="insight-item">
          <div class="insight-icon bg-danger"><i class="bi bi-calendar-event"></i></div>
          <div><strong><?= number_format($totalDeadline) ?></strong><small>Deadline ≤ 7 Hari</small></div>
        </div>
        <div class="insight-item">
          <div class="insight-icon bg-info"><i class="bi bi-cash-coin"></i></div>
          <div><strong><?= rupiah($totalPiutang) ?></strong><small>Total Piutang</small></div>
        </div>
      </aside>
    </section>
    <!-- /INSIGHT -->
<?php if (!empty($klien_list)): ?>    
<!-- DATA KLIEN -->
<section class="table-card">
    <div class="table-card-header">
        <div>
          <h3 class="table-card-title"><i class="bi bi-people-fill"></i>Data Klien</h3>
          <div class="table-card-subtitle">Monitoring seluruh proyek klien</div>
        </div>
        <div class="table-search-wrapper"><input type="text" id="tableSearch" class="form-control" placeholder="Cari nama klien, universitas, layanan..."></div>
      </div>
        <div class="table-tabs">
            <button class="table-tab active" data-filter="all">Semua</button>
            <button class="table-tab" data-filter="belum">Belum</button>
            <button class="table-tab" data-filter="tertunda">Tertunda</button>
            <button class="table-tab" data-filter="selesai">Selesai</button>
            <button class="table-tab" data-filter="deadline">Deadline</button>
        </div>
      <div class="table-responsive">
        <table id="dataTable" class="table align-middle">
          <thead>
            <tr>
              <th>Klien</th>
              <th>Layanan</th>
              <th>Nilai</th>
              <th>Pembayaran</th>
              <th>Status</th>
              <th>Deadline</th>
              <th class="action-column">Aksi</th>
            </tr>
          </thead>
          <tbody>
              <?php foreach ($klien_list as $row): ?>

<?php

$statusPelunasan = strtolower(
    trim((string) ($row['status_pelunasan'] ?? 'belum'))
);

$progressBayar = max(
    0,
    min(100, (float) ($row['progress_bayar'] ?? 0))
);

if ($progressBayar >= 80) {
    $progressClass = 'progress-high';
} elseif ($progressBayar >= 30) {
    $progressClass = 'progress-medium';
} else {
    $progressClass = 'progress-low';
}

$deadlineRaw = $row['deadline'] ?? '';
$deadlineTimestamp = !empty($deadlineRaw)
    ? strtotime($deadlineRaw)
    : false;

$today = strtotime(date('Y-m-d'));

if ($deadlineTimestamp === false) {

    $deadlineTimestamp = 0;
    $deadlineClass = 'normal';
    $deadlineLabel = 'Belum Ditentukan';
    $deadlineDate = '-';

} else {

    $selisihHari = (int) floor(
        ($deadlineTimestamp - $today) / 86400
    );

    $deadlineDate = date('d M Y', $deadlineTimestamp);

    if ($selisihHari < 0) {

        $deadlineClass = 'overdue';
        $deadlineLabel = 'Terlambat';

    } elseif ($selisihHari === 0) {

        $deadlineClass = 'today';
        $deadlineLabel = 'Hari Ini';

    } elseif ($selisihHari === 1) {

        $deadlineClass = 'tomorrow';
        $deadlineLabel = 'Besok';

    } elseif ($selisihHari <= 7) {

        $deadlineClass = 'week';
        $deadlineLabel = $selisihHari . ' Hari Lagi';

    } else {

        $deadlineClass = 'normal';
        $deadlineLabel = $deadlineDate;

    }
}

$namaLengkap = trim(
    (string) ($row['nama_lengkap'] ?? '')
);

$avatarText = $namaLengkap !== ''
    ? strtoupper(substr($namaLengkap, 0, 1))
    : '?';

$fotoKlien = ltrim(
    str_replace(
        '\\',
        '/',
        (string) ($row['foto_klien'] ?? '')
    ),
    '/'
);

$fotoKlienValid =
    $fotoKlien !== '' &&
    strpos(
        $fotoKlien,
        'uploads/foto_klien/'
    ) === 0 &&
    is_file(__DIR__ . '/' . $fotoKlien);    

?>

<tr data-status="<?= htmlspecialchars($statusPelunasan, ENT_QUOTES, 'UTF-8') ?>" data-deadline="<?= (int) $deadlineTimestamp ?>">

<!-- ======================================================
KLIEN
====================================================== -->

    <td>
        <div class="client-cell">
                <div class="client-avatar <?= $fotoKlienValid ? 'has-photo' : '' ?>">
                    <?php if ($fotoKlienValid): ?>
                    <img src="<?= htmlspecialchars($fotoKlien,ENT_QUOTES,'UTF-8') ?>" alt="Foto <?= htmlspecialchars($namaLengkap,ENT_QUOTES,'UTF-8') ?>">
                    <?php else: ?>
                    <?= htmlspecialchars($avatarText,ENT_QUOTES,'UTF-8') ?>
                    <?php endif; ?>
                </div>
                <div class="client-detail">
                    <div class="client-name"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                    <div class="client-campus"><i class="bi bi-building"></i><?= htmlspecialchars($row['institusi']) ?></div>
                    <div class="client-study"><i class="bi bi-mortarboard"></i><?= htmlspecialchars($row['program_studi']) ?></div>
                    <div class="client-phone"><i class="bi bi-whatsapp text-success"></i><?= htmlspecialchars($row['no_telp']) ?></div>
                </div>
        </div>
    </td>

<!-- ======================================================
LAYANAN
====================================================== -->

    <td>
    <div class="service-box">
    <div class="service-title"><?= htmlspecialchars($row['jenis_layanan']) ?></div>
    <?php if(!empty($row['judul_penelitian'])): ?>
    <div class="service-subtitle"><?= htmlspecialchars($row['judul_penelitian']) ?></div>
    <?php endif; ?>
    </div>
    </td>

<!-- ======================================================
NILAI DEALING
====================================================== -->

    <td>
    <div class="money"><?= rupiah($row['nilai_dealing']) ?></div>
    <?php if(!empty($row['file_mou'])): ?>
            <div class="mt-2">
            <a href="uploads/<?= htmlspecialchars($row['file_mou']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text"></i>MoU</a>
    </div>
    <?php endif; ?>
    </td>

<!-- ======================================================
PEMBAYARAN
====================================================== -->

    <td class="payment-column" data-order="<?= (float) $row['total_bayar'] ?>">
        <div class="payment-value"><?= rupiah($row['total_bayar']) ?></div>
        <div class="progress payment-progress">
            <div
                class="progress-bar <?= $progressClass ?>"
                role="progressbar"
                style="width:<?= $progressBayar ?>%;"
                aria-valuenow="<?= $progressBayar ?>"
                aria-valuemin="0"
                aria-valuemax="100">
            </div>
        </div>
        <div class="progress-text">
            <?= number_format($progressBayar, 1, ',', '.') ?>%
        </div>
        <div class="payment-small">
        Sisa :<strong><?= rupiah($row['sisa_piutang']) ?></strong>
        </div>
    </td>
    
<!-- ======================================================
STATUS
====================================================== -->

    <td class="text-center">
    <?php if ($statusPelunasan === 'selesai'): ?>
        <span class="badge rounded-pill bg-success px-3 py-2">
            <i class="bi bi-check-circle-fill"></i>
            Selesai
        </span>
    
    <?php elseif ($statusPelunasan === 'tertunda'): ?>
        <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
            <i class="bi bi-pause-circle-fill"></i>
            Tertunda
        </span>
    
    <?php else: ?>
        <span class="badge rounded-pill bg-danger px-3 py-2">
            <i class="bi bi-hourglass-split"></i>
            Belum
        </span>
    <?php endif; ?>
    </td>

<!-- ======================================================
DEADLINE
====================================================== -->

    <td>
    <div class="deadline <?= htmlspecialchars($deadlineClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="deadline-label">
            <?= htmlspecialchars($deadlineLabel, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="deadline-date">
            <?= htmlspecialchars($deadlineDate, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    </td>

<!-- ======================================================
AKSI
====================================================== -->

    <td>
        <div class="action-buttons">
            <!-- DETAIL: tetap boleh untuk semua -->
            <a  href="detail_klien.php?id=<?= (int) $row['id_klien'] ?>&amp;back=<?= rawurlencode(basename($_SERVER['PHP_SELF'])) ?>"
                class="btn btn-sm btn-outline-primary"
                title="Detail Klien"
                aria-label="Detail Klien">
                <i class="bi bi-eye"></i>
            </a>
    
            <!-- EDIT + PEMBAYARAN: hanya selain Guest -->
            <?php if ($current_privilege !== 'Guest'): ?>
                <a
                    href="edit_data.php?id_klien=<?= (int) $row['id_klien'] ?>&amp;id_pesanan=<?= (int) $row['id_pesanan'] ?>&amp;back=<?= rawurlencode(basename($_SERVER['PHP_SELF'])) ?>"
                    class="btn btn-sm btn-outline-warning"
                    title="Edit Data"
                    aria-label="Edit Data">
    
                    <i class="bi bi-pencil-square"></i>
                </a>
                <a
                    href="update_bayar.php?id=<?= (int) $row['id_pesanan'] ?>"
                    class="btn btn-sm btn-outline-success"
                    title="Update Pembayaran"
                    aria-label="Update Pembayaran">
    
                    <i class="bi bi-cash-stack"></i>
                </a>
            <?php endif; ?>
            <!-- INVOICE: tetap boleh untuk semua -->
            <a
                href="cetak_invoice.php?id=<?= (int) $row['id_pesanan'] ?>"
                class="btn btn-sm btn-outline-info"
                title="Cetak Invoice"
                aria-label="Cetak Invoice"
                target="_blank"
                rel="noopener">
    
                <i class="bi bi-printer"></i>
            </a>
            <!-- HAPUS: hanya Admin -->
            <?php if ($current_privilege === 'Admin'): ?>
                <a
                    href="hapus_data.php?id=<?= (int) $row['id_klien'] ?>"
                    class="btn btn-sm btn-outline-danger"
                    title="Hapus Data"
                    aria-label="Hapus Data"
                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                    <i class="bi bi-trash"></i>
                </a>
            <?php endif; ?>
        </div>
    </td>
</tr>

<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

<!-- EMPTY STATE -->
<?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h3>Belum Ada Data Klien</h3>
        
            <p>Silakan tambahkan data klien pertama untuk mulai menggunakan dashboard.</p>
        
            <?php if($current_privilege!="Guest"): ?>
            <a href="tambah.php" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle"></i>Tambah Klien</a>
            <?php endif; ?>
        </div>
<?php endif; ?>

<!-- FLOATING ACTION BUTTON -->
    <?php if($current_privilege!="Guest"): ?>
    <a href="tambah.php" class="fab" title="Tambah Klien"><i class="bi bi-plus-lg"></i></a>
    <?php endif; ?>
    <button id="scrollTop" class="scroll-top" title="Kembali ke atas"><i class="bi bi-arrow-up"></i></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>