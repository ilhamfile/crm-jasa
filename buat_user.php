<?php
include 'config.php';

// Silakan ganti username & password yang Anda inginkan di sini
$username_buat = "ilham"; 
$password_buat = "RahasiaGua123!"; // Gunakan kombinasi huruf besar, kecil, angka, dan simbol
$nama_lengkap  = "Administrator";

// Proses Hashing Keamanan Tingkat Tinggi
$password_aman = password_hash($password_buat, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
    $stmt->execute([$username_buat, $password_aman, $nama_lengkap]);
    echo "Akun Berhasil Dibuat!<br>";
    echo "Username: " . $username_buat . "<br>";
    echo "Password: " . $password_buat . "<br>";
    echo "<b style='color:red;'>PENTING: Segera hapus file buat_user.php ini dari cPanel Anda demi keamanan!</b>";
} catch (PDOException $e) {
    echo "Gagal membuat akun: " . $e->getMessage();
}
?>