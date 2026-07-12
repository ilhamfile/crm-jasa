<?php
// hapus_data.php
include 'auth.php';
include 'config.php';

// PROTEKSI BACKEND: Tolak akses jika yang login adalah guest
if (isset($_SESSION['role']) && $_SESSION['role'] === 'guest') {
    die("Akses Ditolak: Akun Guest tidak memiliki wewenang untuk menghapus data.");
}

$id_pesanan = $_GET['id'] ?? die('ID Pesanan Tidak Valid');

try {
    // Mulai database transaction agar proses penghapusan aman & sinkron
    $pdo->beginTransaction();

    // 1. Hapus data dari pembayaran_termin terlebih dahulu karena bergantung pada id_pesanan
    $stmt_pembayaran = $pdo->prepare("DELETE FROM pembayaran_termin WHERE id_pesanan = ?");
    $stmt_pembayaran->execute([$id_pesanan]);

    // 2. Hapus data dari pesanan_layanan
    $stmt_pesanan = $pdo->prepare("DELETE FROM pesanan_layanan WHERE id_pesanan = ?");
    $stmt_pesanan->execute([$id_pesanan]);

    // Jika kedua proses di atas berhasil tanpa error, simpan perubahan secara permanen
    $pdo->commit();
    
    // Alihkan kembali halaman ke dashboard utama setelah sukses menghapus
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    // Jika di tengah jalan ada error, batalkan semua proses hapus agar database tidak korup/patah relasi
    $pdo->rollBack();
    die("Gagal menghapus data dari sistem: " . $e->getMessage());
}
?>