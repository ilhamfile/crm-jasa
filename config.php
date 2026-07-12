<?php
// --- FITUR KEAMANAN SESSION (Wajib di paling atas) ---
ini_set('session.cookie_httponly', 1); // Mencegah pencurian session via Javascript (XSS)
ini_set('session.use_only_cookies', 1); // Memaksa session hanya menggunakan cookie aman

// Jika website Anda sudah menggunakan HTTPS (SSL), aktifkan baris di bawah ini dengan menghapus tanda //
// ini_set('session.cookie_secure', 1); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- KONFIGURASI DATABASE ANDA ---
$host = "localhost";
$user = "ergm_akademik";
$pass = ".Astound007w";
$db   = "ergm_akademik";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>