<?php
// cetak_laporan.php
include 'config.php';
// auth.php
include_once 'auth.php';
$periode = $_GET['periode'] ?? 'bulanan';

$query = "SELECT k.nama_lengkap, p.jenis_layanan, p.nilai_dealing, k.tanggal_daftar,
                 (t.termin_1 + t.termin_2 + t.termin_3) as total_bayar
          FROM klien k
          JOIN pesanan_layanan p ON k.id_klien = p.id_klien
          JOIN pembayaran_termin t ON p.id_pesanan = t.id_pesanan";

if ($periode == 'mingguan') {
    $query .= " WHERE YEARWEEK(k.tanggal_daftar, 1) = YEARWEEK(CURDATE(), 1)";
    $judul = "MINGGUAN (MINGGU INI)";
} elseif ($periode == 'tahunan') {
    $query .= " WHERE YEAR(k.tanggal_daftar) = YEAR(CURDATE())";
    $judul = "TAHUNAN (TAHUN INI)";
} else { 
    $query .= " WHERE MONTH(k.tanggal_daftar) = MONTH(CURDATE()) AND YEAR(k.tanggal_daftar) = YEAR(CURDATE())";
    $judul = "BULANAN (BULAN INI)";
}

$stmt = $pdo->query($query);
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Pendapatan</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.4; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <h2 class="text-center">LAPORAN PENDAPATAN JASA AKADEMIK</h2>
    <h4 class="text-center">Periode: <?= $judul ?></h4>
    <hr>
    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th>Tanggal Masuk</th>
                <th>Nama Klien</th>
                <th>Layanan</th>
                <th class="text-right">Nilai Kontrak</th>
                <th class="text-right">Uang Masuk</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; $grand_dealing=0; $grand_masuk=0;
            foreach($laporan as $row): 
                $grand_dealing += $row['nilai_dealing'];
                $grand_masuk += $row['total_bayar'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_daftar'])) ?></td>
                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td><?= $row['jenis_layanan'] ?></td>
                <td class="text-right">Rp <?= number_format($row['nilai_dealing'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="4" class="text-center">TOTAL REKAPITULASI</td>
                <td class="text-right">Rp <?= number_format($grand_dealing, 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($grand_masuk, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>