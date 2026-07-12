<?php
// logout.php
include 'config.php';

// Hapus semua variabel session
$_SESSION = array();

// Hapus cookie session dari browser komputer
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session di server
session_destroy();

// Tendang ke halaman login
header("Location: index.php");
exit();