<?php
// index.php
include 'config.php'; // Mengaktifkan session dan koneksi database

// --- DETEKSI HAK AKSES SECARA DINAMIS ---
$current_privilege = 'Guest';
$nama_user = 'Tamu (Read-Only)';

if (isset($_SESSION['user_id'])) {
    // Jika ada yang login, ambil data hak akses terbarunya dari database
    $stmt_priv = $pdo->prepare("SELECT privileges, nama_lengkap FROM users WHERE id = ?");
    $stmt_priv->execute([$_SESSION['user_id']]);
    $user_info = $stmt_priv->fetch(PDO::FETCH_ASSOC);
    if ($user_info) {
        $current_privilege = $user_info['privileges'];
        $nama_user = $user_info['nama_lengkap'];
    }
}

// --- PROSES AMBIL DATA KLIEN ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query = "SELECT k.*, p.id_pesanan, p.jenis_layanan, p.nilai_dealing, p.deadline, p.status_pelunasan, p.file_mou,
                 t.termin_1, t.termin_2, t.termin_3 
          FROM klien k
          JOIN pesanan_layanan p ON k.id_klien = p.id_klien
          JOIN pembayaran_termin t ON p.id_pesanan = t.id_pesanan";

if (!empty($start_date) && !empty($end_date)) {
    $query .= " WHERE k.tanggal_daftar BETWEEN :start_date AND :end_date";
}
$query .= " ORDER BY p.deadline ASC";

$stmt = $pdo->prepare($query);
if (!empty($start_date) && !empty($end_date)) {
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
} else {
    $stmt->execute();
}
$klien_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PT. Lentera Statistics Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* ===== Header ===== */
    .app-header{
        display:flex;
        justify-content:center;
        margin-bottom:10px;
    }
    
    .app-header-content{
        display:flex;
        align-items:center;
        gap:18px;
    }
    
    .app-logo{
        width:80px;
        height:auto;
    }
    
    .company-title{
        margin:0;
        color:#0d6efd;
        font-size:30px;
        font-weight:700;
        text-transform:uppercase;
    }
    
    .company-tagline{
        margin:2px 0;
        font-size:14px;
        font-style:italic;
        color:#666;
    }
    
    .company-address{
        margin:0;
        font-size:13px;
        color:#444;
    }
    
    .menu-bar{
        text-align:center;
        margin-bottom:25px;
    }
    
    .menu-bar .btn,
    .menu-bar .badge{
        margin:4px;
    }
    
    .btn-menu{
        min-width:170px;
        font-weight:600;
    }
    
    .badge-session{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:230px;
        height:38px;
        font-size:15px;
    }
    </style>    
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="app-header">
      <div class="app-header-content">
        <img src="logo.webp" class="app-logo">
        <div>
            <h2 class="company-title">PT. LENTERA STATISTICS INDONESIA</h2>
            <div class="company-tagline">Bimbingan Skripsi, Tesis & Disertasi</div>
            <div class="company-address" style="display:none">Jl. Sukabumi No.42 Kota Bandung, Jawa Barat (Gedung Graha BPD PHRI)</div>
        </div>
    </div>

</div>
    
    <div class="menu-bar">
    <span class="badge bg-dark badge-session">
        Akses Sesi:
        <?= htmlspecialchars($current_privilege) ?>
        (<?= htmlspecialchars($nama_user) ?>)
    </span>

    <?php if ($current_privilege !== 'Guest'): ?>
        <a href="pengeluaran.php" class="btn btn-danger btn-menu">💸 Kas Keluar</a>
        <a href="laporan_laba_rugi.php" class="btn btn-success btn-menu">📊 Laporan Perusahaan</a>
        <div class="btn-group">
            <button class="btn btn-warning dropdown-toggle btn-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">👤 User</button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="tambah_user.php">➕ Tambah </a></li>
                <li><a class="dropdown-item" href="kelola_user.php">⚙️ Edit</a></li>
            </ul>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-menu" onclick="return confirm('Yakin ingin keluar?')">🚪 Logout</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary btn-menu">🔑 Masuk Sebagai Admin</a>
    <?php endif; ?>

</div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Dari Tanggal Masuk</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari Klien</button>
                </div>
                <div class="col-md-2">
                    <a href="index.php" class="btn btn-secondary w-100">Reset</a>
                </div>
                <div class="col-md-2">
                    <?php if ($current_privilege !== 'Guest'): ?>
                        <a href="tambah.php" class="btn btn-success w-100">+ Klien Baru</a>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled title="Guest tidak diizinkan menambah data">🚫 + Klien Baru</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3">
        <a href="cetak_laporan.php?periode=mingguan" target="_blank" class="btn btn-outline-dark btn-sm">🖨️ Cetak Mingguan</a>
        <a href="cetak_laporan.php?periode=bulanan" target="_blank" class="btn btn-outline-dark btn-sm">🖨️ Cetak Bulanan</a>
        <a href="cetak_laporan.php?periode=tahunan" target="_blank" class="btn btn-outline-dark btn-sm">🖨️ Cetak Tahunan</a>
    </div>

    <div class="table-responsive bg-white rounded shadow-sm p-3">
    <table id="tabelKlien" class="table table-bordered table-hover align-middle" style="font-size: 13px; width: 100%;">
        <thead class="table-primary text-center">
            <tr>
                <th>No</th>
                <th>Nama Klien & Kontak</th>
                <th>Tgl Masuk</th> <th>Institusi & Studi</th>
                <th>Judul Penelitian</th>
                <th>Layanan</th>
                <th>Deadline</th>
                <th>Dealing</th>
                <th>Termin 1</th>
                <th>Termin 2</th>
                <th>Termin 3</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($klien_list)): ?>
                <tr><td colspan="13" class="text-center text-muted">Tidak ada data ditemukan.</td></tr>
            <?php else: $no=1; foreach($klien_list as $row): 
                $tgl_deadline = new DateTime($row['deadline']);
                $tgl_sekarang = new DateTime();
                $sisa_hari = $tgl_sekarang->diff($tgl_deadline)->format("%r%a");
                $badge_deadline = ($sisa_hari <= 3 && $sisa_hari >= 0) ? 'bg-danger' : ($sisa_hari < 0 ? 'bg-secondary' : 'bg-warning text-dark');
                $badge_status = $row['status_pelunasan'] == 'Selesai' ? 'bg-success' : ($row['status_pelunasan'] == 'Tertunda' ? 'bg-warning text-dark' : 'bg-danger');
                
                // Format tanggal masuk klien
                $tgl_daftar_tampil = (!empty($row['tanggal_daftar']) && $row['tanggal_daftar'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_daftar'])) : '-';
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><br>
                    <small class="text-muted">📞 <?= htmlspecialchars($row['no_telp']) ?></small>
                </td>
                <td class="text-center" data-order="<?= strtotime($row['tanggal_daftar'] ?? '') ?>">
                    <span class="badge bg-light text-dark border"><?= $tgl_daftar_tampil ?></span>
                </td>
                <td>
                    <span class="badge bg-secondary"><?= htmlspecialchars($row['institusi']) ?></span><br>
                    <small><?= htmlspecialchars($row['program_studi']) ?></small>
                </td>
                <td><small><?= htmlspecialchars($row['judul_penelitian']) ?></small></td>
                <td class="text-center fw-bold text-info"><?= $row['jenis_layanan'] ?></td>
                <td class="text-center" data-order="<?= strtotime($row['deadline']) ?>">
                    <?= date('d-m-Y', strtotime($row['deadline'])) ?><br>
                    <span class="badge <?= $badge_deadline ?>"><?= $sisa_hari < 0 ? 'Lewat' : $sisa_hari.' Hari Lagi' ?></span>
                </td>
                <td class="text-end">Rp <?= number_format($row['nilai_dealing'], 0, ',', '.') ?></td>
                <td class="text-end bg-light">Rp <?= number_format($row['termin_1'], 0, ',', '.') ?></td>
                <td class="text-end bg-light">Rp <?= number_format($row['termin_2'], 0, ',', '.') ?></td>
                <td class="text-end bg-light">Rp <?= number_format($row['termin_3'], 0, ',', '.') ?></td>
                <td class="text-center"><span class="badge <?= $badge_status ?>"><?= $row['status_pelunasan'] ?></span></td>
                <td>
                    <div class="d-grid gap-1">
                        <?php if ($current_privilege !== 'Guest'): ?>
                            <a href="update_bayar.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-sm btn-outline-primary py-0">✏️ Bayar</a>
                            <a href="edit_data.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-sm btn-outline-warning py-0">🛠️ Edit Data</a>
                            <a href="hapus_data.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data klien & seluruh riwayat pembayaran untuk pesanan ini?')">🗑️ Hapus</a>
                            <?php if ($row['file_mou']): ?>
                                <a href="uploads/<?= $row['file_mou'] ?>" target="_blank" class="btn btn-sm btn-success py-0">✅ Lihat MoU</a>
                                <a href="upload_mou.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-sm btn-outline-secondary py-0">🔄 Ganti MoU</a>
                            <?php else: ?>
                                <a href="upload_mou.php?id=<?= $row['id_pesanan'] ?>" class="btn btn-sm btn-info text-white py-0">⬆️ Upload MoU</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($row['file_mou']): ?>
                                <a href="uploads/<?= $row['file_mou'] ?>" target="_blank" class="btn btn-sm btn-success py-0">📄 Unduh MoU</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary py-0" disabled>⏳ MoU Belum</button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="#" onclick="window.open('cetak_invoice.php?id=<?= $row['id_pesanan'] ?>','_blank')" class="btn btn-sm btn-outline-danger py-0">📄 Invoice</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('.table').DataTable({
        "language": {
            "search": "🔍 Cari Data Klien:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ klien",
            "paginate": { "next": "Selanjutnya", "previous": "Sebelumnya" }
        },
        "order": [[ 5, "desc" ]] // Otomatis mengurutkan berdasarkan kolom Deadline (index 5)
    });
});
</script>
</body>
</html>