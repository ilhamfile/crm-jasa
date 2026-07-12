<?php
// cetak_invoice.php
include 'config.php';

$id_pesanan = $_GET['id'] ?? die('ID Pesanan Tidak Valid');

// PERBAIKAN: Menggunakan t.* agar tgl_termin_1, tgl_termin_2, tgl_termin_3 ikut ditarik dari database
$query = "SELECT k.*, p.*, t.* FROM klien k
          JOIN pesanan_layanan p ON k.id_klien = p.id_klien
          JOIN pembayaran_termin t ON p.id_pesanan = t.id_pesanan
          WHERE p.id_pesanan = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_pesanan]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("Data invoice tidak ditemukan.");
}

// Menghitung total yang sudah dibayar dan sisa tagihan
$total_bayar = $invoice['termin_1'] + $invoice['termin_2'] + $invoice['termin_3'];
$sisa_tagihan = $invoice['nilai_dealing'] - $total_bayar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_<?= htmlspecialchars($invoice['nama_lengkap']) ?>_<?= $id_pesanan ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            background-color: #fff;
        }
        .invoice-box {
            width: 100%;
            max-width: 100%; /* Menghapus batasan 800px */
            margin: auto;
            padding: 20px auto; /* Menambah jarak spasi kiri-kanan agar tidak terlalu mepet tepi layar */
        }
        /* Style Tabel Header Tanpa Garis */
        /* HEADER */
        .header-wrapper{
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .header-content{
            display: flex;
            align-items: center;
            gap: 20px;
            width: fit-content;
            max-width: 100%;
        }
        
        .logo-img{
            width: 85px;
            height: auto;
            flex-shrink: 0;
        }
        
        .company-info{
            text-align: left;
        }
        
        .company-name{
            margin:0;
            font-size:30px;
            font-weight:bold;
            text-transform:uppercase;
        }
        
        .company-tagline{
            margin:4px 0;
            font-size:14px;
            font-style:italic;
        }
        
        .company-address{
            margin:0;
            font-size:13px;
            line-height:1.4;
        }
        
        @media (max-width:768px){
        
            .header-content{
                text-align:center;
                gap:10px;
            }
        
            .company-info{
                text-align:center;
            }
        
            .company-name{font-size:24px;}
        
        }
        .logo-img {
            max-height: 85px;
            width: auto;
            display: block;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a252f;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-tagline {
            font-size: 13px;
            font-style: italic;
            color: #7f8c8d;
            margin: 4px 0;
        }
        .company-address {
            font-size: 12px;
            color: #444;
            margin: 0;
            line-height: 1.4;
        }
        /* Garis Pembatas Kop Surat */
        .line-bold {
            border: 0;
            border-top: 2px solid #2c3e50;
            margin-top: 15px;
            margin-bottom: 2px;
        }
        .line-thin {
            border: 0;
            border-top: 1px solid #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
        }
        /* Konten Invoice */
        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 25px;
            letter-spacing: 1px;
            color: #2c3e50;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .info-table td {
            vertical-align: top;
            padding: 4px 0;
        }
        /* Tabel Data Rincian Biaya */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .data-table th {
            background-color: #f2f4f4;
            color: #2c3e50;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border: 1px solid #bdc3c7;
        }
        .data-table td {
            padding: 10px;
            border: 1px solid #bdc3c7;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .bg-light {
            background-color: #fcfcfc;
        }
        /* Tanda Tangan */
        .footer-sign {
            margin-top: 40px;
            float: right;
            text-align: center;
            width: 200px;
        }
        .space-sign {
            height: 80px;
        }
        /* Aturan Cetak Otomatis */
        @media print {
            body { padding: 0; }
            .invoice-box { max-width: 100%; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header-wrapper">
        <div class="header-content">
            <img src="logo.webp" alt="Logo LSI" class="logo-img">
            <div class="company-info">
                <h3 class="company-name">
                    PT. LENTERA STATISTICS INDONESIA
                </h3>
                <div class="company-tagline">
                    "Bimbingan Skripsi, Tesis & Disertasi"
                </div>
                <p class="company-address">
                    Jl. Sukabumi No.42 Kota Bandung, Jawa Barat
                    (Gedung Graha BPD PHRI)
                </p>
            </div>
        </div>
    </div>
    
    <hr class="line-bold">
    <hr class="line-thin">

    <div class="invoice-title">TANDA TERIMA</div>

    <table class="info-table" style="border: none;">
        <tr>
            <td style="width: 15%; border:none;"><strong>No. Invoice</strong></td>
            <td style="width: 35%; border:none;">: INV/<?= date('Ymd', strtotime($invoice['tanggal_daftar'])) ?>/<?= $invoice['id_pesanan'] ?></td>
            <td style="width: 15%; border:none;"><strong>Nama Klien</strong></td>
            <td style="width: 35%; border:none;">: <?= htmlspecialchars($invoice['nama_lengkap']) ?></td>
        </tr>
        <tr>
            <td style="border:none;"><strong>Tanggal Masuk</strong></td>
            <td style="border:none;">: <?= date('d-m-Y', strtotime($invoice['tanggal_daftar'])) ?></td>
            <td style="border:none;"><strong>Institusi</strong></td>
            <td style="border:none;">: <?= htmlspecialchars($invoice['institusi']) ?></td>
        </tr>
        <tr>
            <td style="border:none;"><strong>Kontak</strong></td>
            <td style="border:none;">: <?= htmlspecialchars($invoice['no_telp']) ?></td>
            <td style="border:none;"><strong>Program Studi</strong></td>
            <td style="border:none;">: <?= htmlspecialchars($invoice['program_studi']) ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 65%;">Deskripsi Layanan & Judul Penelitian</th>
                <th style="width: 30%;">Jumlah / Nilai Kesepakatan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>
                    <strong>Jasa Pendampingan Bimbingan: <?= htmlspecialchars($invoice['jenis_layanan']) ?></strong><br>
                    <small style="color: #555;">Judul: "<?= htmlspecialchars($invoice['judul_penelitian']) ?>"</small><br>
                    <small style="color: #7f8c8d;">Target Deadline: <?= date('d-m-Y', strtotime($invoice['deadline'])) ?></small>
                </td>
                <td class="text-end fw-bold">Rp <?= number_format($invoice['nilai_dealing'], 0, ',', '.') ?></td>
            </tr>
            
            <?php if (!empty($invoice['termin_1']) && $invoice['termin_1'] > 0): ?>
            <tr class="bg-light">
                <td colspan="2" class="text-right">
                    Termin 1
                    <?php if(!empty($invoice['tgl_termin_1']) && $invoice['tgl_termin_1'] != '0000-00-00'): ?>
                        <span style="font-size: 11px; color: #7f8c8d;">(Tgl Bayar: <?= date('d-m-Y', strtotime($invoice['tgl_termin_1'])) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">Rp <?= number_format($invoice['termin_1'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($invoice['termin_2']) && $invoice['termin_2'] > 0): ?>
            <tr class="bg-light">
                <td colspan="2" class="text-right">
                    Termin 2
                    <?php if(!empty($invoice['tgl_termin_2']) && $invoice['tgl_termin_2'] != '0000-00-00'): ?>
                        <span style="font-size: 11px; color: #7f8c8d;">(Tgl Bayar: <?= date('d-m-Y', strtotime($invoice['tgl_termin_2'])) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">Rp <?= number_format($invoice['termin_2'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($invoice['termin_3']) && $invoice['termin_3'] > 0): ?>
            <tr class="bg-light">
                <td colspan="2" class="text-right">
                    Termin 3
                    <?php if(!empty($invoice['tgl_termin_3']) && $invoice['tgl_termin_3'] != '0000-00-00'): ?>
                        <span style="font-size: 11px; color: #7f8c8d;">(Tgl Bayar: <?= date('d-m-Y', strtotime($invoice['tgl_termin_3'])) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">Rp <?= number_format($invoice['termin_3'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>

            <tr style="background-color: #f9f9f9;">
                <td colspan="2" class="text-right fw-bold" style="color: #27ae60;">Total yang Sudah Dibayar:</td>
                <td class="text-end fw-bold" style="color: #27ae60;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold" style="color: #c0392b;">Sisa Tagihan Pelunasan:</td>
                <td class="text-end fw-bold" style="color: #c0392b;">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></td>
            </tr>
            <tr class="bg-light">
                <td colspan="2" class="text-right fw-bold">Status Invoice:</td>
                <td class="text-center fw-bold text-uppercase" style="background-color: <?= $invoice['status_pelunasan'] == 'Selesai' ? '#e8f8f5' : '#fdeadc' ?>; color: <?= $invoice['status_pelunasan'] == 'Selesai' ? '#27ae60' : '#d35400' ?>;">
                    <?= htmlspecialchars($invoice['status_pelunasan']) ?>
                </td>
            </tr>
        </tbody>
    </table>

    <p style="font-size: 11px; color: #7f8c8d; font-style: italic; margin-top: -15px;">
        * Nota ini sah dikeluarkan secara sistem otomasi sebagai bukti transaksi yang valid antara perusahaan dan klien yang bersangkutan.
    </p>

    <div class="footer-sign">
        <p>Bandung, <?= date('d F Y') ?></p>
        <p style="margin-top: -10px;">Manajer Operasional,</p>
        <div class="space-sign"></div>
        <p><strong>( ____________________ )</strong></p>
    </div>
</div>

<script>
    window.print();
</script>

</body>
</html>