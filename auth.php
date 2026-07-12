<?php
// auth.php
include_once 'config.php';

// Jika tidak ada session user_id, artinya belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>