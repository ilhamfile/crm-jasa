<?php
// logout_klien.php
require_once 'config.php';

// 1. Simpan ID klien ke variabel sementara sebelum dihapus
$id_klien = $_SESSION['klien_id'] ?? null;

// 2. Hapus hanya session milik klien
unset($_SESSION['klien_id']);
unset($_SESSION['nama_klien']);

// 3. Arahkan kembali ke halaman detail_klien.php milik klien tersebut
if ($id_klien) {
    header("Location: detail_klien.php?id=" . $id_klien);
} else {
    // Fallback/cadangan jika kebetulan tidak ada session yang nyangkut
    header("Location: login_klien.php");
}
exit();
?>