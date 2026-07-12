<?php
// cetak_mou.php
include 'config.php';
// auth.php
include_once 'auth.php';
$id = $_GET['id'] ?? die('ID Tidak Valid');

$stmt = $pdo->prepare("SELECT k.*, p.* FROM klien k JOIN pesanan_layanan p ON k.id_klien = p.id_klien WHERE p.id_pesanan = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data tidak ditemukan.");
}

// Set Header agar didownload sebagai file Word (.doc / .docx kompatibel)
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename=MoU_Kesepakatan_".str_replace(' ', '_', $data['nama_lengkap']).".doc");
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <title>Surat Perjanjian MoU</title>
    <style>
        body { font-family: 'Times New Roman', Serif; line-height: 1.6; padding: 20px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 5px; text-transform: uppercase; }
        .subtitle { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 30px; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        table.sign { width: 100%; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="title">SURAT PERJANJIAN KERJASAMA KONSULTASI AKADEMIK</div>
    <div class="subtitle">Nomor Perjanjian: Perj/<?= $data['id_pesanan'] ?>/<?= date('Y') ?></div>

    <p>Yang bertanda tangan di bawah ini:</p>
    <ol>
        <li><strong>Nama Lembaga:</strong> Penyedia Jasa Akademik Profesional<br>Selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.</li>
        <li><strong>Nama Klien:</strong> <?= htmlspecialchars($data['nama_lengkap']) ?><br>
            <strong>Institusi:</strong> <?= htmlspecialchars($data['institusi']) ?><br>
            <strong>Alamat:</strong> <?= htmlspecialchars($data['alamat']) ?><br>
            Selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
        </li>
    </ol>

    <p>Kedua belah pihak secara sadar dan sukarela bersepakat untuk mengadakan perjanjian kerjasama pendampingan penyelesaian tugas akhir berbentuk <strong><?= $data['jenis_layanan'] ?></strong> dengan rincian kesepakatan sebagai berikut:</p>

    <div class="section-title">PASAL 1: RUANG LINGKUP PEKERJAAN</div>
    <p>PIHAK PERTAMA berkewajiban memberikan bimbingan, arahan materi, dan penyusunan teknis laporan untuk membantu PIHAK KEDUA menyelesaikan karya ilmiah berjudul: <br><em>"<?= htmlspecialchars($data['judul_penelitian']) ?>"</em>.</p>

    <div class="section-title">PASAL 2: NILAI KONTRAK & PEMBAYARAN TERMIN</div>
    <p>Total nilai investasi yang disepakati oleh kedua belah pihak adalah sebesar <strong>Rp <?= number_format($data['nilai_dealing'], 0, ',', '.') ?></strong>. Sistem pembayaran wajib dilakukan secara berkala melalui 3 (tiga) termin cicilan yang diatur di dalam sistem administrasi tagihan resmi.</p>

    <div class="section-title">PASAL 3: WAKTU PENYELESAIAN (DEADLINE)</div>
    <p>PIHAK PERTAMA berkomitmen menyelesaikan draf pendampingan seluruhnya kepada PIHAK KEDUA selambat-lambatnya pada tanggal <strong><?= date('d-m-Y', strtotime($data['deadline'])) ?></strong>, dengan catatan PIHAK KEDUA kooperatif dalam memberikan feedback masukan bimbingan.</p>

    <div class="section-title">PASAL 4: KETENTUAN KHUSUS</div>
    <p>Dokumen kesepakatan ini dibuat dalam bentuk digital dan dapat direvisi secara fleksibel sesuai kesepakatan tertulis lanjutan demi kebaikan proses penyelesaian akademik.</p>

    <table class="sign">
        <tr>
            <td width="50%">
                Pihak Pertama,<br><br><br><br>
                (.........................................)<br>
                Admin Jasa Akademik
            </td>
            <td width="50%">
                Pihak Kedua (Klien),<br><br><br><br>
                (<strong><?= htmlspecialchars($data['nama_lengkap']) ?></strong>)<br>
                Mahasiswa / Peneliti
            </td>
        </tr>
    </table>
</body>
</html>