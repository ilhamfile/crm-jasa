<?php
// laporan_laba_rugi.php
include 'auth.php';
include 'config.php';

$tgl_mulai = $_GET['mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['selesai'] ?? date('Y-m-t');
$orientasi = $_GET['orientasi'] ?? 'portrait';

$total_pendapatan = 0;
$total_pengeluaran = 0;
$list_pendapatan = [];
$list_pengeluaran = [];

$grafik_mingguan = [];
$grafik_bulanan = [];
$grafik_tahunan = [];

if (isset($_GET['filter'])) {
    // ==========================================
    // 1. TARIK DATA PENDAPATAN
    // ==========================================
    $query_pendapatan = "
        SELECT k.nama_lengkap, p.status_pelunasan,
               t.termin_1, t.tgl_termin_1,
               t.termin_2, t.tgl_termin_2,
               t.termin_3, t.tgl_termin_3
        FROM klien k
        JOIN pesanan_layanan p ON k.id_klien = p.id_klien
        JOIN pembayaran_termin t ON p.id_pesanan = t.id_pesanan
    ";
    $stmt_pend = $pdo->prepare($query_pendapatan);
    $stmt_pend->execute();
    $semua_transaksi = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);

    foreach ($semua_transaksi as $row) {
        $bayar_periode_ini = 0;
        $termin_dibayar = [];
        $tgl_transaksi_pendapatan = [];

        if (!empty($row['tgl_termin_1']) && $row['tgl_termin_1'] >= $tgl_mulai && $row['tgl_termin_1'] <= $tgl_selesai) {
            $bayar_periode_ini += $row['termin_1'];
            if ($row['termin_1'] > 0) { $termin_dibayar[] = '1'; $tgl_transaksi_pendapatan[] = ['tgl' => $row['tgl_termin_1'], 'nominal' => $row['termin_1']]; }
        }
        if (!empty($row['tgl_termin_2']) && $row['tgl_termin_2'] >= $tgl_mulai && $row['tgl_termin_2'] <= $tgl_selesai) {
            $bayar_periode_ini += $row['termin_2'];
            if ($row['termin_2'] > 0) { $termin_dibayar[] = '2'; $tgl_transaksi_pendapatan[] = ['tgl' => $row['tgl_termin_2'], 'nominal' => $row['termin_2']]; }
        }
        if (!empty($row['tgl_termin_3']) && $row['tgl_termin_3'] >= $tgl_mulai && $row['tgl_termin_3'] <= $tgl_selesai) {
            $bayar_periode_ini += $row['termin_3'];
            if ($row['termin_3'] > 0) { $termin_dibayar[] = '3'; $tgl_transaksi_pendapatan[] = ['tgl' => $row['tgl_termin_3'], 'nominal' => $row['termin_3']]; }
        }

        if ($bayar_periode_ini > 0) {
            $list_pendapatan[] = [
                'nama' => $row['nama_lengkap'],
                'termin' => implode(', ', $termin_dibayar),
                'status' => $row['status_pelunasan'],
                'nominal' => $bayar_periode_ini
            ];
            $total_pendapatan += $bayar_periode_ini;

            foreach ($tgl_transaksi_pendapatan as $tp) {
                $mgu = 'W' . date('W-Y', strtotime($tp['tgl']));
                $bln = date('M-Y', strtotime($tp['tgl']));
                $thn = date('Y', strtotime($tp['tgl']));
                $grafik_mingguan[$mgu]['pendapatan'] = ($grafik_mingguan[$mgu]['pendapatan'] ?? 0) + $tp['nominal'];
                $grafik_bulanan[$bln]['pendapatan'] = ($grafik_bulanan[$bln]['pendapatan'] ?? 0) + $tp['nominal'];
                $grafik_tahunan[$thn]['pendapatan'] = ($grafik_tahunan[$thn]['pendapatan'] ?? 0) + $tp['nominal'];
            }
        }
    }

    // ==========================================
    // 2. TARIK DATA PENGELUARAN
    // ==========================================
    $stmt_out = $pdo->prepare("SELECT * FROM pengeluaran_perusahaan WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal ASC");
    $stmt_out->execute([$tgl_mulai, $tgl_selesai]);
    $list_pengeluaran = $stmt_out->fetchAll(PDO::FETCH_ASSOC);

    foreach ($list_pengeluaran as $out) {
        $total_pengeluaran += $out['biaya'];
        $mgu = 'W' . date('W-Y', strtotime($out['tanggal']));
        $bln = date('M-Y', strtotime($out['tanggal']));
        $thn = date('Y', strtotime($out['tanggal']));
        $grafik_mingguan[$mgu]['pengeluaran'] = ($grafik_mingguan[$mgu]['pengeluaran'] ?? 0) + $out['biaya'];
        $grafik_bulanan[$bln]['pengeluaran'] = ($grafik_bulanan[$bln]['pengeluaran'] ?? 0) + $out['biaya'];
        $grafik_tahunan[$thn]['pengeluaran'] = ($grafik_tahunan[$thn]['pengeluaran'] ?? 0) + $out['biaya'];
    }
}

$laba_bersih = $total_pendapatan - $total_pengeluaran;

ksort($grafik_mingguan);
ksort($grafik_bulanan);
ksort($grafik_tahunan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perusahaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            @page { 
                size: <?= $orientasi === 'landscape' ? 'landscape' : 'portrait' ?>; 
                margin: 15mm;
            }
            .no-print { display: none !important; }
            body { background-color: #fff; font-size: 14px;}
            .card { border: none !important; box-shadow: none !important; }
            /* Memastikan saat print padding hilang dan melebar sesuai kertas */
            .container-fluid { padding: 0 !important; width: 100% !important; } 
            .no-print-hide { display: flex !important; }
            .chart-box { page-break-inside: avoid; }
        }
        
        .table-rincian td { vertical-align: middle; }

        .header-table { width: 100%; border-collapse: collapse; border: none; margin-bottom: 5px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        .logo-img { max-height: 85px; width: auto; display: block; }
        .company-name { font-size: 22px; font-weight: bold; color: #1a252f; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .company-tagline { font-size: 13px; font-style: italic; color: #7f8c8d; margin: 4px 0; }
        .company-address { font-size: 12px; color: #444; margin: 0; line-height: 1.4; }
        .line-bold { border: 0; border-top: 2px solid #2c3e50; margin-top: 15px; margin-bottom: 2px; }
        .line-thin { border: 0; border-top: 1px solid #2c3e50; margin-top: 0; margin-bottom: 20px; }
        .report-title { text-align: center; font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px; color: #2c3e50; }
        .chart-container { position: relative; margin: auto; height: 260px; width: 100%; }
    </style>
</head>
<body class="bg-light py-4">

<div class="container-fluid px-4">
    
    <div class="card shadow mb-4 no-print border-0">
        <div class="card-body bg-white">
            <h5 class="fw-bold mb-3">🔍 Filter & Pengaturan Laporan</h5>
            <form method="GET" class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Orientasi Cetak Cetak</label>
                    <select name="orientasi" class="form-select">
                        <option value="portrait" <?= $orientasi === 'portrait' ? 'selected' : '' ?>>Portrait (Vertikal)</option>
                        <option value="landscape" <?= $orientasi === 'landscape' ? 'selected' : '' ?>>Landscape (Horizontal)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="filter" class="btn btn-primary w-100 fw-bold">Tampilkan Laporan</button>
                </div>
            </form>
            <div class="mt-3">
                <a href="index.php" class="btn btn-secondary btn-sm">⬅ Dashboard</a>
                <a href="pengeluaran.php" class="btn btn-outline-danger btn-sm">Kelola Pengeluaran</a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['filter'])): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body px-4 py-5 px-lg-5">
            
            <table class="header-table">
                <tr>
                    <td style="width: 12%;">
                        <img src="logo.webp" alt="Logo LSI" class="logo-img">
                    </td>
                    <td style="width: 88%; padding-left: 20px;">
                        <h3 class="company-name">PT. LENTERA STATISTICS INDONESIA</h3>
                        <div class="company-tagline">"Bimbingan Skripsi, Tesis & Disertasi"</div>
                        <p class="company-address">
                            Jl. Sukabumi No. 42 Kota Bandung, Jawa Barat (Gedung Graha BPD PHRI)
                        </p>
                    </td>
                </tr>
            </table>
            
            <hr class="line-bold">
            <hr class="line-thin">

            <div class="text-center mb-4">
                <div class="report-title">LAPORAN</div>
                <p class="text-muted small mt-1">Periode: <?= date('d M Y', strtotime($tgl_mulai)) ?> s/d <?= date('d M Y', strtotime($tgl_selesai)) ?></p>
            </div>

            <div class="chart-box mb-5">
                <h6 class="fw-bold text-dark mb-3 bg-light p-2 rounded">📈 GRAFIK NERACA PERBANDINGAN</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-white text-center shadow-sm">
                            <span class="small fw-bold text-muted text-uppercase">Neraca Mingguan</span>
                            <div class="chart-container mt-2">
                                <canvas id="chartMinggu"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-white text-center shadow-sm">
                            <span class="small fw-bold text-muted text-uppercase">Neraca Bulanan</span>
                            <div class="chart-container mt-2">
                                <canvas id="chartBulan"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-white text-center shadow-sm">
                            <span class="small fw-bold text-muted text-uppercase">Neraca Tahunan</span>
                            <div class="chart-container mt-2">
                                <canvas id="chartTahun"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table table-borderless table-rincian mb-4">
                <tbody>
                    <tr>
                        <td colspan="4"><h6 class="fw-bold text-success mb-2 bg-light p-2 rounded">A. PENDAPATAN OPERASIONAL</h6></td>
                    </tr>
                    
                    <?php if(empty($list_pendapatan)): ?>
                        <tr><td colspan="4" class="ps-4 text-muted fst-italic pb-3">Tidak ada pendapatan di periode ini.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($list_pendapatan as $p): ?>
                        <tr>
                            <td class="ps-4 text-muted" style="width: 3%;"><?= $no++ ?>.</td>
                            <td style="width: 57%;">
                                Pendapatan dari <strong><?= htmlspecialchars($p['nama']) ?></strong><br>
                                <small class="text-muted">Pembayaran: Termin <?= $p['termin'] ?></small>
                            </td>
                            <td class="text-center" style="width: 15%;">
                                <span class="badge <?= $p['status'] == 'Selesai' ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-2"><?= $p['status'] ?></span>
                            </td>
                            <td class="text-end text-success fw-bold" style="width: 25%;">Rp <?= number_format($p['nominal'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr style="border-top: 1px solid #ddd; border-bottom: 2px solid #333;">
                        <td colspan="3" class="fw-bold text-end py-3">TOTAL PENDAPATAN KOTOR:</td>
                        <td class="text-end fw-bold text-success py-3 fs-5">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                    </tr>
                    <tr><td colspan="4" style="height: 30px;"></td></tr>

                    <tr>
                        <td colspan="4"><h6 class="fw-bold text-danger mb-2 bg-light p-2 rounded">B. BEBAN & PENGELUARAN OPERASIONAL</h6></td>
                    </tr>

                    <?php if(empty($list_pengeluaran)): ?>
                        <tr><td colspan="4" class="ps-4 text-muted fst-italic pb-3">Tidak ada pengeluaran di periode ini.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($list_pengeluaran as $out): ?>
                        <tr>
                            <td class="ps-4 text-muted" style="width: 3%;"><?= $no++ ?>.</td>
                            <td colspan="2">
                                <strong><?= htmlspecialchars($out['jenis_pengeluaran']) ?></strong>
                                <?php if(!empty($out['keterangan'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($out['keterangan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-danger fw-bold">Rp <?= number_format($out['biaya'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr style="border-top: 1px solid #ddd; border-bottom: 2px solid #333;">
                        <td colspan="3" class="fw-bold text-end py-3">TOTAL PENGELUARAN:</td>
                        <td class="text-end fw-bold text-danger py-3 fs-5">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="p-4 rounded shadow mt-4" style="background-color: <?= $laba_bersih >= 0 ? '#e8f8f5' : '#fdedec' ?>; border-left: 6px solid <?= $laba_bersih >= 0 ? '#27ae60' : '#c0392b' ?>;">
                <h3 class="d-flex justify-content-between mb-0 fw-bold" style="color: <?= $laba_bersih >= 0 ? '#27ae60' : '#c0392b' ?>;">
                    <span><?= $laba_bersih >= 0 ? 'LABA BERSIH (PROFIT)' : 'RUGI BERSIH' ?> :</span>
                    <span>Rp <?= number_format(abs($laba_bersih), 0, ',', '.') ?></span>
                </h3>
            </div>

            <div class="mt-5 pt-4 row no-print-hide" style="display: none;">
                <div class="col-8"></div>
                <div class="col-4 text-center">
                    <p>Bandung, <?= date('d F Y') ?></p>
                    <p>Manajer Operasional</p>
                    <br><br><br>
                    <p class="fw-bold">( ______________________ )</p>
                </div>
            </div>

            <div class="text-end mt-5 no-print">
                <button onclick="window.print()" class="btn btn-success fw-bold px-4 py-2 shadow-sm">
                    🖨️ Cetak Dokumen Laporan (<?= ucfirst($orientasi) ?>)
                </button>
            </div>

        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const configChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } }
};

new Chart(document.getElementById('chartMinggu'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($grafik_mingguan)) ?>,
        datasets: [
            { label: 'In', data: <?= json_encode(array_column($grafik_mingguan, 'pendapatan')) ?>, backgroundColor: '#2ecc71' },
            { label: 'Out', data: <?= json_encode(array_column($grafik_mingguan, 'pengeluaran')) ?>, backgroundColor: '#e74c3c' }
        ]
    },
    options: configChartOptions
});

new Chart(document.getElementById('chartBulan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($grafik_bulanan)) ?>,
        datasets: [
            { label: 'In', data: <?= json_encode(array_column($grafik_bulanan, 'pendapatan')) ?>, backgroundColor: '#2ecc71' },
            { label: 'Out', data: <?= json_encode(array_column($grafik_bulanan, 'pengeluaran')) ?>, backgroundColor: '#e74c3c' }
        ]
    },
    options: configChartOptions
});

new Chart(document.getElementById('chartTahun'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($grafik_tahunan)) ?>,
        datasets: [
            { label: 'In', data: <?= json_encode(array_column($grafik_tahunan, 'pendapatan')) ?>, backgroundColor: '#2ecc71' },
            { label: 'Out', data: <?= json_encode(array_column($grafik_tahunan, 'pengeluaran')) ?>, backgroundColor: '#e74c3c' }
        ]
    },
    options: configChartOptions
});
</script>
</body>
</html>