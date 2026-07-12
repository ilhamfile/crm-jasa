<?php
/**
 * installkeun.php
 * Installer satu file untuk Sistem CRM Administrasi Jasa Akademik.
 *
 * Cara pakai:
 * 1. Upload file ini ke folder utama website bersama file aplikasi.
 * 2. Buka https://domainanda.com/installkeun.php
 * 3. Isi data database dan akun Admin.
 * 4. Setelah berhasil, hapus file installkeun.php dari hosting.
 */

declare(strict_types=1);

@ini_set('display_errors', '0');
@ini_set('session.cookie_httponly', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.cookie_samesite', 'Lax');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

const INSTALLER_VERSION = '1.2.0';
const MINIMUM_PHP_VERSION = '7.4.0';

$baseDir = __DIR__;
$configPath = $baseDir . DIRECTORY_SEPARATOR . 'config.php';
$lockPath = $baseDir . DIRECTORY_SEPARATOR . '.installkeun.lock';
$helperDir = $baseDir . DIRECTORY_SEPARATOR . 'includes';
$helperPath = $helperDir . DIRECTORY_SEPARATOR . 'client_photo.php';
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads';
$photoUploadDir = $uploadDir . DIRECTORY_SEPARATOR . 'foto_klien';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function postValue(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function createDirectory(string $path): void
{
    if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
        throw new RuntimeException('Gagal membuat folder: ' . basename($path));
    }

    if (!is_writable($path)) {
        throw new RuntimeException('Folder tidak dapat ditulis: ' . basename($path));
    }
}

function writeFileAtomically(string $path, string $contents, int $permissions = 0644): void
{
    $directory = dirname($path);
    $temporary = @tempnam($directory, '.installkeun-');

    if ($temporary === false) {
        throw new RuntimeException('Gagal membuat file sementara di folder website.');
    }

    try {
        $written = @file_put_contents($temporary, $contents, LOCK_EX);

        if ($written === false || $written !== strlen($contents)) {
            throw new RuntimeException('Gagal menulis file: ' . basename($path));
        }

        @chmod($temporary, $permissions);

        if (!@rename($temporary, $path)) {
            throw new RuntimeException('Gagal menyimpan file: ' . basename($path));
        }
    } finally {
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }
}

function quoteDatabaseName(string $database): string
{
    return '`' . str_replace('`', '``', $database) . '`';
}

function buildConfig(
    string $host,
    int $port,
    string $database,
    string $username,
    string $password
): string {
    $hostExport = var_export($host, true);
    $databaseExport = var_export($database, true);
    $usernameExport = var_export($username, true);
    $passwordExport = var_export($password, true);

    return <<<PHP
<?php
// Dibuat otomatis oleh installkeun.php pada {$GLOBALS['installationDate']}.
// Jangan membagikan isi file ini karena memuat kredensial database.

ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

\$isHttps =
    (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ||
    (isset(\$_SERVER['SERVER_PORT']) && (int) \$_SERVER['SERVER_PORT'] === 443) ||
    (isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) \$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

if (\$isHttps) {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

\$host = {$hostExport};
\$port = {$port};
\$user = {$usernameExport};
\$pass = {$passwordExport};
\$db   = {$databaseExport};

try {
    \$pdo = new PDO(
        "mysql:host={\$host};port={\$port};dbname={\$db};charset=utf8mb4",
        \$user,
        \$pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException \$e) {
    error_log('Koneksi database gagal: ' . \$e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database atau hubungi administrator.');
}
?>
PHP;
}

function clientPhotoHelperContents(): string
{
    return <<<'PHP'
<?php
/**
 * Helper unggahan foto klien.
 * Dibuat otomatis oleh installkeun.php apabila file belum tersedia.
 */

if (!function_exists('uploadClientPhoto')) {
    function uploadClientPhoto(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Unggahan foto gagal. Kode kesalahan: ' . $error);
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('File foto tidak valid.');
        }

        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            throw new RuntimeException('Ukuran foto maksimal 2 MB.');
        }

        $imageInfo = @getimagesize($temporaryPath);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedTypes[$mime])) {
            throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
        }

        $directory = dirname(__DIR__) . '/uploads/foto_klien';

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder foto klien tidak dapat dibuat.');
        }

        $filename = 'klien_' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('Foto klien gagal disimpan.');
        }

        @chmod($destination, 0644);

        return 'uploads/foto_klien/' . $filename;
    }
}

if (!function_exists('deleteClientPhoto')) {
    function deleteClientPhoto(?string $relativePath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
        $prefix = 'uploads/foto_klien/';

        if ($relativePath === '' || strpos($relativePath, $prefix) !== 0) {
            return;
        }

        $filename = basename($relativePath);
        $absolutePath = dirname(__DIR__) . '/uploads/foto_klien/' . $filename;

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
PHP;
}

function uploadHtaccessContents(): string
{
    return <<<'HTACCESS'
<FilesMatch "\.(php|php[0-9]?|phtml|phar)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order Allow,Deny
        Deny from all
    </IfModule>
</FilesMatch>
HTACCESS;
}

$installationDate = date('Y-m-d H:i:s T');
$errors = [];
$success = false;
$steps = [];
$backupName = null;

$requirements = [
    'php' => version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '>='),
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'root_writable' => is_writable($baseDir),
];

$isLocked = is_file($lockPath);

if (empty($_SESSION['installkeun_csrf'])) {
    $_SESSION['installkeun_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $csrf = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['installkeun_csrf'], $csrf)) {
        $errors[] = 'Sesi instalasi tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    foreach ($requirements as $requirement => $passed) {
        if (!$passed) {
            $errors[] = 'Persyaratan server belum terpenuhi: ' . $requirement . '.';
        }
    }

    $dbHost = postValue('db_host', 'localhost');
    $dbPortRaw = postValue('db_port', '3306');
    $dbName = postValue('db_name');
    $dbUser = postValue('db_user');
    $dbPass = isset($_POST['db_password']) ? (string) $_POST['db_password'] : '';
    $adminName = postValue('admin_name');
    $adminUsername = postValue('admin_username', 'admin');
    $adminPassword = isset($_POST['admin_password']) ? (string) $_POST['admin_password'] : '';
    $adminPasswordConfirmation = isset($_POST['admin_password_confirmation'])
        ? (string) $_POST['admin_password_confirmation']
        : '';
    $createDatabase = isset($_POST['create_database']);

    if ($dbHost === '') {
        $errors[] = 'Host database wajib diisi.';
    }

    if (!ctype_digit($dbPortRaw) || (int) $dbPortRaw < 1 || (int) $dbPortRaw > 65535) {
        $errors[] = 'Port database tidak valid.';
    }

    $dbPort = (int) $dbPortRaw;

    if ($dbName === '' || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $dbName)) {
        $errors[] = 'Nama database hanya boleh berisi huruf, angka, garis bawah, atau tanda minus.';
    }

    if ($dbUser === '') {
        $errors[] = 'Username database wajib diisi.';
    }

    if (strlen($adminName) < 3 || strlen($adminName) > 100) {
        $errors[] = 'Nama lengkap Admin harus terdiri dari 3 sampai 100 karakter.';
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $adminUsername)) {
        $errors[] = 'Username Admin harus terdiri dari 3 sampai 50 karakter tanpa spasi.';
    }

    if (strlen($adminPassword) < 8) {
        $errors[] = 'Password Admin minimal 8 karakter.';
    }

    if (!hash_equals($adminPassword, $adminPasswordConfirmation)) {
        $errors[] = 'Konfirmasi password Admin tidak sama.';
    }

    if (!$errors) {
        $pdo = null;
        $configBackupPath = null;
        $newConfigWritten = false;

        try {
            // Pastikan folder utama benar-benar dapat ditulis sebelum menyentuh database.
            $probe = @tempnam($baseDir, '.installkeun-probe-');

            if ($probe === false) {
                throw new RuntimeException('Folder utama website tidak dapat ditulis oleh PHP.');
            }

            @unlink($probe);

            $serverPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $steps[] = 'Koneksi ke server database berhasil.';

            if ($createDatabase) {
                $serverPdo->exec(
                    'CREATE DATABASE IF NOT EXISTS ' . quoteDatabaseName($dbName) .
                    ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
                );
                $steps[] = 'Database berhasil dibuat atau sudah tersedia.';
            }

            $pdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $steps[] = 'Database aplikasi berhasil dipilih.';

            $schemaStatements = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INT NOT NULL AUTO_INCREMENT,
                    username VARCHAR(50) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    nama_lengkap VARCHAR(100) NOT NULL,
                    privileges ENUM('Admin','Staff') NOT NULL DEFAULT 'Staff',
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_users_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS klien (
                    id_klien INT NOT NULL AUTO_INCREMENT,
                    nama_lengkap VARCHAR(150) NOT NULL,
                    foto_klien VARCHAR(255) DEFAULT NULL,
                    alamat TEXT DEFAULT NULL,
                    tanggal_masuk DATE DEFAULT NULL,
                    no_telp VARCHAR(30) DEFAULT NULL,
                    institusi VARCHAR(150) DEFAULT NULL,
                    fakultas VARCHAR(150) DEFAULT NULL,
                    program_studi VARCHAR(150) DEFAULT NULL,
                    judul_penelitian TEXT DEFAULT NULL,
                    tanggal_daftar DATE DEFAULT NULL,
                    PRIMARY KEY (id_klien),
                    KEY idx_klien_tanggal_daftar (tanggal_daftar),
                    KEY idx_klien_nama (nama_lengkap)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS pesanan_layanan (
                    id_pesanan INT NOT NULL AUTO_INCREMENT,
                    id_klien INT DEFAULT NULL,
                    jenis_layanan VARCHAR(100) NOT NULL,
                    nilai_dealing BIGINT NOT NULL DEFAULT 0,
                    deadline DATE NOT NULL,
                    status_pelunasan ENUM('Belum','Tertunda','Selesai') NOT NULL DEFAULT 'Belum',
                    file_mou VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (id_pesanan),
                    KEY idx_pesanan_klien (id_klien),
                    KEY idx_pesanan_deadline (deadline),
                    KEY idx_pesanan_status (status_pelunasan),
                    CONSTRAINT fk_crm_pesanan_klien
                        FOREIGN KEY (id_klien) REFERENCES klien (id_klien)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS pembayaran_termin (
                    id_pembayaran INT NOT NULL AUTO_INCREMENT,
                    id_pesanan INT DEFAULT NULL,
                    termin_1 BIGINT NOT NULL DEFAULT 0,
                    tgl_termin_1 DATE DEFAULT NULL,
                    termin_2 BIGINT NOT NULL DEFAULT 0,
                    tgl_termin_2 DATE DEFAULT NULL,
                    termin_3 BIGINT NOT NULL DEFAULT 0,
                    tgl_termin_3 DATE DEFAULT NULL,
                    PRIMARY KEY (id_pembayaran),
                    UNIQUE KEY uq_pembayaran_pesanan (id_pesanan),
                    CONSTRAINT fk_crm_pembayaran_pesanan
                        FOREIGN KEY (id_pesanan) REFERENCES pesanan_layanan (id_pesanan)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS pengeluaran_perusahaan (
                    id_pengeluaran INT NOT NULL AUTO_INCREMENT,
                    tanggal DATE NOT NULL,
                    jenis_pengeluaran VARCHAR(255) NOT NULL,
                    biaya BIGINT NOT NULL DEFAULT 0,
                    keterangan TEXT DEFAULT NULL,
                    PRIMARY KEY (id_pengeluaran),
                    KEY idx_pengeluaran_tanggal (tanggal)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ];

            foreach ($schemaStatements as $statement) {
                $pdo->exec($statement);
            }

            // Migrasi aman untuk dump lama yang masih memakai ENUM terbatas.
            $pdo->exec(
                "ALTER TABLE pesanan_layanan
                 MODIFY jenis_layanan VARCHAR(100) NOT NULL"
            );

            $steps[] = 'Seluruh tabel aplikasi berhasil disiapkan.';

            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new RuntimeException('Password Admin gagal diamankan.');
            }

            $adminStatement = $pdo->prepare(
                "INSERT INTO users (username, password, nama_lengkap, privileges)
                 VALUES (:username, :password, :nama, 'Admin')
                 ON DUPLICATE KEY UPDATE
                    password = VALUES(password),
                    nama_lengkap = VALUES(nama_lengkap),
                    privileges = 'Admin'"
            );

            $adminStatement->execute([
                ':username' => $adminUsername,
                ':password' => $passwordHash,
                ':nama' => $adminName,
            ]);
            $steps[] = 'Akun Admin utama berhasil dibuat atau diperbarui.';

            createDirectory($uploadDir);
            createDirectory($photoUploadDir);
            createDirectory($helperDir);

            $uploadHtaccess = $uploadDir . DIRECTORY_SEPARATOR . '.htaccess';
            if (!is_file($uploadHtaccess)) {
                writeFileAtomically($uploadHtaccess, uploadHtaccessContents());
            }

            $uploadIndex = $uploadDir . DIRECTORY_SEPARATOR . 'index.html';
            if (!is_file($uploadIndex)) {
                writeFileAtomically($uploadIndex, "<!doctype html><title>403 Forbidden</title>\n");
            }

            $photoIndex = $photoUploadDir . DIRECTORY_SEPARATOR . 'index.html';
            if (!is_file($photoIndex)) {
                writeFileAtomically($photoIndex, "<!doctype html><title>403 Forbidden</title>\n");
            }

            if (!is_file($helperPath)) {
                writeFileAtomically($helperPath, clientPhotoHelperContents());
                $steps[] = 'Helper unggahan foto klien berhasil dibuat.';
            } else {
                $steps[] = 'Helper foto klien sudah tersedia dan tidak ditimpa.';
            }

            $steps[] = 'Folder unggahan berhasil dibuat dan diamankan.';

            if (is_file($configPath)) {
                $configBackupPath = $configPath . '.backup-' . date('Ymd-His');

                if (!@copy($configPath, $configBackupPath)) {
                    throw new RuntimeException('config.php lama tidak dapat dicadangkan.');
                }

                @chmod($configBackupPath, 0640);
                $backupName = basename($configBackupPath);
            }

            $configContents = buildConfig($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            writeFileAtomically($configPath, $configContents, 0640);
            $newConfigWritten = true;
            $steps[] = 'config.php berhasil dibuat.';

            $lockContents = json_encode(
                [
                    'installed_at' => $installationDate,
                    'installer_version' => INSTALLER_VERSION,
                    'database' => $dbName,
                    'admin_username' => $adminUsername,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );

            if ($lockContents === false) {
                throw new RuntimeException('Gagal membuat data pengunci instalasi.');
            }

            writeFileAtomically($lockPath, $lockContents . PHP_EOL, 0640);
            $steps[] = 'Instalasi dikunci untuk mencegah pemasangan ulang.';

            $success = true;
            unset($_SESSION['installkeun_csrf']);
        } catch (Throwable $exception) {
            if ($newConfigWritten && !is_file($lockPath)) {
                if ($configBackupPath !== null && is_file($configBackupPath)) {
                    @copy($configBackupPath, $configPath);
                } else {
                    @unlink($configPath);
                }
            }

            $message = $exception->getMessage();

            if ($exception instanceof PDOException) {
                $message = 'Database gagal diproses. Periksa host, nama database, username, password, dan hak akses pengguna database.';
            }

            $errors[] = $message;
            error_log('installkeun.php: ' . $exception->getMessage());
        }
    }
}

$currentValues = [
    'db_host' => postValue('db_host', 'localhost'),
    'db_port' => postValue('db_port', '3306'),
    'db_name' => postValue('db_name'),
    'db_user' => postValue('db_user'),
    'admin_name' => postValue('admin_name'),
    'admin_username' => postValue('admin_username', 'admin'),
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Installkeun CRM Akademik</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #15803d;
            --danger: #b91c1c;
            --warning: #a16207;
            --ink: #172033;
            --muted: #64748b;
            --line: #dbe3ef;
            --surface: #ffffff;
            --background: #f4f7fb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--background);
        }
        .page { width: min(920px, calc(100% - 32px)); margin: 42px auto; }
        .header { margin-bottom: 22px; }
        .eyebrow { margin: 0 0 8px; color: var(--primary); font-size: 13px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(28px, 4vw, 42px); line-height: 1.12; }
        .lead { max-width: 720px; margin: 12px 0 0; color: var(--muted); line-height: 1.7; }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, .08); overflow: hidden; }
        .card-body { padding: 26px; }
        .section + .section { margin-top: 28px; padding-top: 26px; border-top: 1px solid var(--line); }
        .section-title { margin: 0 0 6px; font-size: 19px; }
        .section-help { margin: 0 0 18px; color: var(--muted); font-size: 14px; line-height: 1.55; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 750; }
        input[type="text"], input[type="number"], input[type="password"] {
            width: 100%; min-height: 46px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; color: var(--ink); background: #fff; font: inherit; outline: none;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .13); }
        .check { display: flex; align-items: flex-start; gap: 10px; padding: 13px; border: 1px solid var(--line); border-radius: 10px; background: #f8fafc; }
        .check input { margin-top: 3px; }
        .check label { margin: 0; font-weight: 650; }
        .small { display: block; margin-top: 4px; color: var(--muted); font-size: 12px; font-weight: 400; line-height: 1.45; }
        .requirements { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 0 0 20px; }
        .requirement { padding: 11px 12px; border-radius: 10px; font-size: 12px; font-weight: 800; text-align: center; }
        .requirement.ok { color: #166534; background: #dcfce7; }
        .requirement.bad { color: #991b1b; background: #fee2e2; }
        .alert { margin-bottom: 18px; padding: 14px 16px; border-radius: 12px; line-height: 1.55; }
        .alert-danger { color: #7f1d1d; background: #fef2f2; border: 1px solid #fecaca; }
        .alert-success { color: #14532d; background: #f0fdf4; border: 1px solid #bbf7d0; }
        .alert-warning { color: #713f12; background: #fffbeb; border: 1px solid #fde68a; }
        .alert h2 { margin: 0 0 8px; font-size: 21px; }
        .alert p { margin: 6px 0; }
        .alert ul { margin: 8px 0 0; padding-left: 20px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 26px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 11px 18px; border: 0; border-radius: 10px; color: #fff; background: var(--primary); font: inherit; font-weight: 800; text-decoration: none; cursor: pointer; }
        .button:hover { background: var(--primary-dark); }
        .button.secondary { color: var(--primary); background: #eff6ff; border: 1px solid #bfdbfe; }
        .footer { margin-top: 16px; color: var(--muted); font-size: 12px; text-align: center; }
        code { padding: 2px 6px; border-radius: 6px; background: rgba(15, 23, 42, .07); }
        @media (max-width: 720px) {
            .page { margin: 24px auto; }
            .card-body { padding: 19px; }
            .grid, .requirements { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="header">
        <p class="eyebrow">Installer versi <?= e(INSTALLER_VERSION) ?></p>
        <h1>Installkeun CRM Akademik</h1>
        <p class="lead">Installer ini menyiapkan database, akun Admin, konfigurasi koneksi, folder unggahan, dan pengamanan dasar aplikasi.</p>
    </header>

    <section class="card">
        <div class="card-body">
            <div class="requirements">
                <div class="requirement <?= $requirements['php'] ? 'ok' : 'bad' ?>">PHP <?= e(PHP_VERSION) ?></div>
                <div class="requirement <?= $requirements['pdo'] ? 'ok' : 'bad' ?>">PDO <?= $requirements['pdo'] ? 'Aktif' : 'Tidak aktif' ?></div>
                <div class="requirement <?= $requirements['pdo_mysql'] ? 'ok' : 'bad' ?>">PDO MySQL <?= $requirements['pdo_mysql'] ? 'Aktif' : 'Tidak aktif' ?></div>
                <div class="requirement <?= $requirements['root_writable'] ? 'ok' : 'bad' ?>">Folder <?= $requirements['root_writable'] ? 'Writable' : 'Tidak writable' ?></div>
            </div>

            <?php if ($isLocked && !$success): ?>
                <div class="alert alert-warning">
                    <h2>Website sudah diinstal</h2>
                    <p>File pengunci <code>.installkeun.lock</code> telah ditemukan. Installer tidak dapat dijalankan ulang.</p>
                    <p>Hapus file pengunci secara manual hanya jika Anda benar-benar perlu melakukan instalasi ulang.</p>
                </div>
                <div class="actions">
                    <a class="button" href="login.php">Buka halaman login</a>
                    <a class="button secondary" href="index.php">Buka website</a>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <h2>Instalasi berhasil</h2>
                    <p>Website sudah terhubung ke database dan akun Admin sudah siap digunakan.</p>
                    <ul>
                        <?php foreach ($steps as $step): ?>
                            <li><?= e($step) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($backupName !== null): ?>
                        <p>Konfigurasi lama dicadangkan sebagai <code><?= e($backupName) ?></code>.</p>
                    <?php endif; ?>
                </div>
                <div class="alert alert-warning">
                    <strong>Tindakan wajib:</strong> hapus file <code>installkeun.php</code> dari hosting setelah memastikan login berhasil.
                </div>
                <div class="actions">
                    <a class="button" href="login.php">Login sebagai Admin</a>
                    <a class="button secondary" href="index.php">Buka website</a>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <strong>Instalasi belum berhasil:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['installkeun_csrf']) ?>">

                    <div class="section">
                        <h2 class="section-title">1. Database hosting</h2>
                        <p class="section-help">Gunakan data database dari cPanel atau panel hosting. Pada shared hosting, database biasanya harus dibuat lebih dahulu.</p>
                        <div class="grid">
                            <div>
                                <label for="db_host">Host database</label>
                                <input id="db_host" type="text" name="db_host" value="<?= e($currentValues['db_host']) ?>" required>
                            </div>
                            <div>
                                <label for="db_port">Port database</label>
                                <input id="db_port" type="number" name="db_port" value="<?= e($currentValues['db_port']) ?>" min="1" max="65535" required>
                            </div>
                            <div>
                                <label for="db_name">Nama database</label>
                                <input id="db_name" type="text" name="db_name" value="<?= e($currentValues['db_name']) ?>" placeholder="contoh: akun_crm" required>
                            </div>
                            <div>
                                <label for="db_user">Username database</label>
                                <input id="db_user" type="text" name="db_user" value="<?= e($currentValues['db_user']) ?>" placeholder="contoh: akun_userdb" required>
                            </div>
                            <div class="full">
                                <label for="db_password">Password database</label>
                                <input id="db_password" type="password" name="db_password" placeholder="Masukkan password database">
                            </div>
                            <div class="full check">
                                <input id="create_database" type="checkbox" name="create_database" value="1" <?= isset($_POST['create_database']) ? 'checked' : '' ?>>
                                <label for="create_database">
                                    Coba buat database secara otomatis
                                    <span class="small">Centang hanya jika pengguna database memiliki izin CREATE DATABASE. Pada cPanel biasanya opsi ini tidak perlu dicentang.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">2. Akun Admin pertama</h2>
                        <p class="section-help">Akun ini digunakan untuk masuk ke sistem dan membuat akun Staff atau Admin lain.</p>
                        <div class="grid">
                            <div class="full">
                                <label for="admin_name">Nama lengkap Admin</label>
                                <input id="admin_name" type="text" name="admin_name" value="<?= e($currentValues['admin_name']) ?>" required>
                            </div>
                            <div>
                                <label for="admin_username">Username Admin</label>
                                <input id="admin_username" type="text" name="admin_username" value="<?= e($currentValues['admin_username']) ?>" required>
                            </div>
                            <div></div>
                            <div>
                                <label for="admin_password">Password Admin</label>
                                <input id="admin_password" type="password" name="admin_password" minlength="8" required>
                                <span class="small">Gunakan minimal 8 karakter.</span>
                            </div>
                            <div>
                                <label for="admin_password_confirmation">Ulangi password Admin</label>
                                <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" minlength="8" required>
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="button" type="submit">Mulai instalasi</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <p class="footer">Pastikan file aplikasi berada dalam folder yang sama dengan installer ini.</p>
</main>
</body>
</html>
