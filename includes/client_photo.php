<?php

/**
 * Upload foto klien.
 *
 * @return string|null Path relatif foto atau null jika tidak ada upload.
 * @throws RuntimeException
 */
function uploadClientPhoto(?array $file): ?string
{
    if (
        !$file ||
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Upload foto gagal. Kode error: ' . $file['error']
        );
    }

    $maxSize = 2 * 1024 * 1024;

    if (($file['size'] ?? 0) > $maxSize) {
        throw new RuntimeException(
            'Ukuran foto maksimal 2 MB.'
        );
    }

    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        throw new RuntimeException(
            'File foto tidak valid.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException(
            'Format foto harus JPG, PNG, atau WEBP.'
        );
    }

    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException(
            'File yang diunggah bukan gambar yang valid.'
        );
    }

    $uploadDirectory =
        dirname(__DIR__) . '/uploads/foto_klien';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0775, true)
    ) {
        throw new RuntimeException(
            'Folder penyimpanan foto tidak dapat dibuat.'
        );
    }

    if (!is_writable($uploadDirectory)) {
        throw new RuntimeException(
            'Folder uploads/foto_klien tidak dapat ditulis.'
        );
    }

    $extension = $allowedMime[$mime];

    $fileName =
        'klien_' .
        bin2hex(random_bytes(12)) .
        '.' .
        $extension;

    $destination =
        $uploadDirectory . '/' . $fileName;

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {
        throw new RuntimeException(
            'Foto gagal dipindahkan ke folder upload.'
        );
    }

    return 'uploads/foto_klien/' . $fileName;
}


/**
 * Menghapus foto klien dengan pengamanan path.
 */
function deleteClientPhoto(?string $relativePath): void
{
    if (empty($relativePath)) {
        return;
    }

    $relativePath = ltrim(
        str_replace('\\', '/', $relativePath),
        '/'
    );

    if (
        strpos(
            $relativePath,
            'uploads/foto_klien/'
        ) !== 0
    ) {
        return;
    }

    $fullPath =
        dirname(__DIR__) . '/' . $relativePath;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}