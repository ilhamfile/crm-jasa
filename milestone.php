<?php
// milestone.php
require_once 'config.php';

$pageTitle = 'Progress Pengerjaan';
$namaLengkap = trim(
    (string) $klien['nama_lengkap']
);

// Cek autentikasi (Bisa diakses oleh Staff/Admin ATAU Klien)
$is_staff = isset($_SESSION['user_id']);
$is_client = isset($_SESSION['klien_id']);

if (!$is_staff && !$is_client) {
    header("Location: login.php");
    exit();
}

$id_pesanan = filter_input(INPUT_GET, 'id_pesanan', FILTER_VALIDATE_INT);
if (!$id_pesanan) die('ID Pesanan tidak valid.');

// Ambil Data Pesanan & Klien
$stmt = $pdo->prepare("SELECT p.*, k.nama_lengkap, k.no_telp, k.id_klien FROM pesanan_layanan p JOIN klien k ON p.id_klien = k.id_klien WHERE p.id_pesanan = ?");
$stmt->execute([$id_pesanan]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pesanan) die('Pesanan tidak ditemukan.');

// Validasi Keamanan: Klien hanya boleh melihat pesanannya sendiri
if ($is_client && $_SESSION['klien_id'] != $pesanan['id_klien']) {
    die('Akses Ditolak. Ini bukan pesanan Anda.');
}

$pengirim_id = $is_staff ? $_SESSION['user_id'] : $_SESSION['klien_id'];
$pengirim_role = $is_staff ? 'Staff' : 'Klien';

// --- PROSES SUBMIT FORM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah Milestone (Hanya Staff)
    if ($action === 'add_milestone' && $is_staff) {
        $judul = trim($_POST['judul_tahapan']);
        $deskripsi = trim($_POST['deskripsi']);
        if (!empty($judul)) {
            $stmt = $pdo->prepare("INSERT INTO pesanan_milestone (id_pesanan, judul_tahapan, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$id_pesanan, $judul, $deskripsi]);
        }
    }
    
    // Update Status Milestone (Hanya Staff)
    if ($action === 'update_status' && $is_staff) {
        $id_milestone = (int)$_POST['id_milestone'];
        $status = $_POST['status_pengerjaan'];
        $stmt = $pdo->prepare("UPDATE pesanan_milestone SET status_pengerjaan = ? WHERE id_milestone = ? AND id_pesanan = ?");
        $stmt->execute([$status, $id_milestone, $id_pesanan]);
    }

    // Kirim Pesan / Revisi & Upload Lampiran (Staff & Klien)
    if ($action === 'send_chat') {
        $id_milestone = (int)$_POST['id_milestone'];
        $pesan = trim($_POST['pesan'] ?? '');
        $file_name = null;

        // --- SISTEM KEAMANAN ANTI-HACKER UNTUK UPLOAD ---
        if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['file_lampiran']['tmp_name'];
            $size     = $_FILES['file_lampiran']['size'];
            $ext      = strtolower(pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION));

            // 1. Whitelist Ekstensi File
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'];
            
            // 2. Validasi MIME Type (Cek DNA File Asli)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp_name);
            $allowed_mimes = [
                'image/jpeg', 'image/png', 'image/webp', 
                'application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            // 3. Batas Ukuran (5 MB) & Validasi Akhir
            if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_mimes) && $size <= 5242880) {
                $upload_dir = 'uploads/milestone/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                // 4. Acak Nama File (Cegah Eksekusi Shell)
                $file_name = 'lampiran_' . bin2hex(random_bytes(8)) . '.' . $ext;
                move_uploaded_file($tmp_name, $upload_dir . $file_name);
            } else {
                die('Keamanan Sistem: File tidak valid atau ukuran melebihi 5MB!');
            }
        }

        // Simpan ke database jika ada pesan ATAU ada file yang diupload
        if (!empty($pesan) || $file_name !== null) {
            $stmt = $pdo->prepare("INSERT INTO milestone_chat (id_milestone, id_pengirim, role_pengirim, pesan, file_lampiran) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_milestone, $pengirim_id, $pengirim_role, $pesan, $file_name]);
        }
    }
        // Ajukan Buka Diskusi (Khusus Klien saat status Selesai)
    if ($action === 'request_reopen' && $is_client) {
        $id_milestone = (int)$_POST['id_milestone'];
        
        // Ubah status menjadi Menunggu Konfirmasi (Bukan langsung Revisi)
        $stmt = $pdo->prepare("UPDATE pesanan_milestone SET status_pengerjaan = 'Menunggu Konfirmasi' WHERE id_milestone = ? AND id_pesanan = ?");
        $stmt->execute([$id_milestone, $id_pesanan]);

        // Kirim pesan otomatis dari Sistem
        $pesan_sistem = "⚠️ *Pemberitahuan Sistem:* Klien mengajukan pembukaan ulang ruang diskusi. Menunggu persetujuan Staff.";
        $stmtChat = $pdo->prepare("INSERT INTO milestone_chat (id_milestone, id_pengirim, role_pengirim, pesan) VALUES (?, ?, ?, ?)");
        $stmtChat->execute([$id_milestone, $pengirim_id, $pengirim_role, $pesan_sistem]);
    }
        // Redirect agar tidak tersubmit ulang saat di-refresh
    header("Location: milestone.php?id_pesanan=" . $id_pesanan);
    exit();
}

// Ambil Data Milestone & Chat
$stmtMilestone = $pdo->prepare("SELECT * FROM pesanan_milestone WHERE id_pesanan = ? ORDER BY id_milestone ASC");
$stmtMilestone->execute([$id_pesanan]);
$milestones = $stmtMilestone->fetchAll(PDO::FETCH_ASSOC);

// Cek apakah semua progress sudah berstatus Selesai
$total_ms = count($milestones);
$selesai_ms = 0;
foreach ($milestones as $m) {
    if ($m['status_pengerjaan'] === 'Selesai') $selesai_ms++;
}
$semua_selesai = ($total_ms > 0 && $total_ms === $selesai_ms);

// Format Nomor WA untuk tombol Selesai
$no_wa_klien = preg_replace('/[^0-9]/', '', $pesanan['no_telp']);
if (strpos($no_wa_klien, '0') === 0) {
    $no_wa_klien = '62' . substr($no_wa_klien, 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Pengerjaan <?= htmlspecialchars($pesanan['jenis_layanan']) ?> - Klien <?= htmlspecialchars($pesanan['nama_lengkap']) ?></title>
    <link rel="icon" type="image/webp" href="assets/img/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; margin: 0; padding: 0; overflow-x: hidden; } /* Warna bg WA Web */
        
        .timeline { border-left: 3px solid #dee2e6; margin-left: 20px; padding-left: 25px; position: relative; }
        .timeline-item { margin-bottom: 25px; position: relative; }
        .timeline-item::before {
            content: ""; position: absolute; left: -34px; top: 0; width: 16px; height: 16px;
            border-radius: 50%; background-color: #0d6efd; border: 3px solid #f0f2f5; box-shadow: 0 0 0 2px #0d6efd;
        }

        /* WA Style Chat Container */
        .chat-container { 
            max-height: 55vh; 
            overflow-y: auto; 
            background-color: #efeae2; /* Warna Latar Obrolan WA */
            padding: 15px; 
            border-radius: 12px;
        }
        
        /* WA Style Bubbles */
        .chat-bubble { max-width: 85%; padding: 8px 12px; border-radius: 10px; font-size: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .chat-me { background-color: #d9fdd3; color: #111; border-top-right-radius: 0; } /* Hijau Khas WA */
        .chat-other { background-color: #ffffff; color: #111; border-top-left-radius: 0; } /* Putih Khas WA */

        /* Mode HP: Fullscreen & Sticky Header layaknya Aplikasi Native */
        @media (max-width: 768px) {
            .container-fluid { padding: 0 !important; }
            .card { border-radius: 0 !important; border-left: 0; border-right: 0; }
            .app-header { position: sticky; top: 0; z-index: 1030; margin-bottom: 0 !important; border-bottom: 1px solid #dee2e6; }
            .timeline { margin-left: 15px; padding-left: 15px; padding-top: 15px; border-left: 2px solid #dee2e6; margin-bottom: 0; }
            .timeline-item::before { left: -24px; width: 14px; height: 14px; top: 2px; box-shadow: 0 0 0 2px #0d6efd; }
            .chat-container { border-radius: 0; padding: 10px; max-height: 65vh; }
            .card-body { padding: 15px !important; }
            .timeline-item .card { margin-bottom: 0 !important; border-bottom: 5px solid #f0f2f5; }
            /* TAMBAHKAN KODE INI UNTUK MENGECILKAN TEKS HEADER */
            .info-layanan { font-size: 11px; line-height: 1.4; margin-top: 2px; }
            .app-header h4 { font-size: 18px; } /* Opsional: Sedikit mengecilkan judul "Progress Pengerjaan" agar lebih proporsional */
        }
    </style>
</head>
<body>
<div class="container-fluid p-0 p-md-4">
    
    <!-- Header Summary -->
    <div class="card shadow-sm border-0 mb-4 rounded-0 rounded-md-4 app-header">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">Progress Pengerjaan</h4>
                <p class="text-muted mb-0 info-layanan">
                    Layanan: <strong><?= htmlspecialchars($pesanan['jenis_layanan']) ?></strong> 
                    <span class="d-none d-md-inline">|</span><br class="d-block d-md-none"> 
                    Klien: <?= htmlspecialchars($pesanan['nama_lengkap']) ?>
                </p>
            </div>
            <div>
                <a href="detail_klien.php?id=<?= $pesanan['id_klien'] ?>" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Area Tambah Milestone (Hanya Staff) -->
    <?php if ($is_staff): ?>
    <div class="card shadow-sm border-0 mb-4 rounded-0 rounded-md-4">
        <div class="card-body">
            <h6 class="fw-bold"><i class="bi bi-plus-circle me-1"></i> Buat Tahapan Baru</h6>
            <form method="POST" class="row g-2 align-items-center mt-2">
                <input type="hidden" name="action" value="add_milestone">
                <div class="col-md-5">
                    <input type="text" name="judul_tahapan" class="form-control" placeholder="Contoh: Pembuatan Draft Bab 1" required>
                </div>
                <div class="col-md-5">
                    <input type="text" name="deskripsi" class="form-control" placeholder="Deskripsi singkat...">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary fw-bold">Tambah</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Banner Proyek Selesai (Otomatis Muncul Jika Semua Selesai) -->
    <?php if ($semua_selesai): ?>
    <div class="card shadow-sm border-0 mb-4 rounded-0 rounded-md-4" style="background: linear-gradient(135deg, #15803d, #22c55e); color: white;">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
            <div>
                <h5 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-2"></i>Semua Pekerjaan Tuntas!</h5>
                <p class="mb-0 small">Seluruh tahapan progress untuk layanan ini telah diselesaikan.</p>
            </div>
            
            <?php if ($is_staff): 
                $pesan_final = urlencode("Halo Kak " . $pesanan['nama_lengkap'] . ".\n\nKabar gembira! Seluruh tahapan pengerjaan untuk layanan *" . $pesanan['jenis_layanan'] . "* telah *SELESAI SEPENUHNYA*. ðŸŽ‰\n\nTerima kasih telah mempercayakan layanan ini kepada PT Valtekindo Global Intertek.");
            ?>
            <a href="https://wa.me/<?= $no_wa_klien ?>?text=<?= $pesan_final ?>" target="_blank" class="btn btn-light text-success fw-bold mt-3 mt-md-0 rounded-pill px-4" style="box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                <i class="bi bi-whatsapp"></i> Beritahu Klien (Proyek Selesai)
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timeline Milestone -->
    <div class="timeline mt-4">
        <?php if (empty($milestones)): ?>
            <p class="text-muted fst-italic">Belum ada tahapan pekerjaan yang dibuat.</p>
        <?php else: ?>
            <?php foreach ($milestones as $ms): 
                // Ambil Chat untuk milestone ini
                $stmtChat = $pdo->prepare("SELECT * FROM milestone_chat WHERE id_milestone = ? ORDER BY waktu_kirim ASC");
                $stmtChat->execute([$ms['id_milestone']]);
                $chats = $stmtChat->fetchAll(PDO::FETCH_ASSOC);

                // Warna status
                $status_color = match($ms['status_pengerjaan']) {
                    'Selesai' => 'success',
                    'Revisi' => 'danger',
                    'Dikerjakan' => 'warning',
                    'Menunggu Konfirmasi' => 'info',
                    default => 'secondary'
                };
            ?>
            <div class="timeline-item">
                <div class="card shadow-sm border-0 rounded-0 rounded-md-4">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($ms['judul_tahapan']) ?></h5>
                        <span class="badge bg-<?= $status_color ?> rounded-pill px-3 py-2"><?= $ms['status_pengerjaan'] ?></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small"><?= nl2br(htmlspecialchars($ms['deskripsi'])) ?></p>
                        
                        <!-- Kontrol Status (Hanya Staff) -->
                        <?php if ($is_staff): ?>
                        <form method="POST" class="d-flex align-items-center gap-2 mb-3 bg-light p-2 rounded">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_milestone" value="<?= $ms['id_milestone'] ?>">
                            <span class="small fw-bold">Update Status:</span>
                            <select name="status_pengerjaan" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="Menunggu" <?= $ms['status_pengerjaan'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="Dikerjakan" <?= $ms['status_pengerjaan'] == 'Dikerjakan' ? 'selected' : '' ?>>Dikerjakan</option>
                                <option value="Revisi" <?= $ms['status_pengerjaan'] == 'Revisi' ? 'selected' : '' ?>>Revisi</option>
                                <option value="Selesai" <?= $ms['status_pengerjaan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <?php if ($ms['status_pengerjaan'] == 'Menunggu Konfirmasi'): ?>
                                    <option value="Menunggu Konfirmasi" selected disabled>Menunggu Konfirmasi</option>
                                <?php endif; ?>
                            </select>
                            
                            <?php if ($ms['status_pengerjaan'] == 'Selesai'): 
                                $pesan_wa = urlencode("Halo Kak " . $pesanan['nama_lengkap'] . ".\n\nKami dari PT Valtekindo Global Intertek menginformasikan bahwa tahapan *" . $ms['judul_tahapan'] . "* telah *SELESAI*. Silakan cek portal untuk melihat detailnya. Terima kasih!");
                            ?>
                                <a href="https://wa.me/<?= $no_wa_klien ?>?text=<?= $pesan_wa ?>" target="_blank" class="btn btn-sm btn-success ms-auto">
                                    <i class="bi bi-whatsapp"></i> Kabari Klien
                                </a>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>

                        <!-- Area Chat / Revisi -->
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-bold small text-secondary mb-3"><i class="bi bi-chat-dots"></i> Ruang Diskusi & Revisi</h6>
                            
                            <div class="chat-container mb-3" id="chat-box-<?= $ms['id_milestone'] ?>">
                                <!-- Chat akan dimuat otomatis oleh JavaScript di sini -->
                                <div class="text-center text-muted small my-4">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Memuat diskusi...
                                </div>
                            </div>
                            <!-- Logika Kunci Chat Jika Selesai atau Menunggu Konfirmasi -->
                            <?php if (($ms['status_pengerjaan'] === 'Selesai' || $ms['status_pengerjaan'] === 'Menunggu Konfirmasi') && $is_client): ?>
                                
                                <div class="alert alert-secondary mt-3 mb-0 text-center rounded-3 border-0" style="background-color: #e2e8f0;">
                                    
                                    <?php if ($ms['status_pengerjaan'] === 'Menunggu Konfirmasi'): ?>
                                        <!-- Tampilan Saat Sudah Mengajukan -->
                                        <i class="bi bi-hourglass-split text-warning fs-4 d-block mb-1"></i>
                                        <span class="small text-dark d-block fw-bold mb-1">Pengajuan Sedang Diproses...</span>
                                        <span class="small text-muted d-block">Mohon tunggu, Staff meninjau permintaan Anda. Refresh halaman secara berkala!</span>
                                    <?php else: ?>
                                        <!-- Tampilan Gembok Awal -->
                                        <i class="bi bi-lock-fill text-muted fs-4 d-block mb-1"></i>
                                        <span class="small text-muted d-block mb-2">Tahapan ini telah selesai. Ruang diskusi ditutup.</span>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="action" value="request_reopen">
                                            <input type="hidden" name="id_milestone" value="<?= $ms['id_milestone'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                                                <i class="bi bi-arrow-counterclockwise"></i> Ajukan Buka Diskusi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                </div>

                            <?php else: ?>
                            <!-- Form Input Pesan & Lampiran -->
                            <form method="POST" enctype="multipart/form-data" class="mt-3 chat-form">
                                <input type="hidden" name="action" value="send_chat">
                                <input type="hidden" name="id_milestone" value="<?= $ms['id_milestone'] ?>">
                                
                                <!-- Indikator File Terpilih (Awalnya disembunyikan) -->
                                <div id="file_indicator_<?= $ms['id_milestone'] ?>" class="d-none mb-2">
                                    <span class="badge bg-secondary text-wrap text-start d-inline-flex align-items-center gap-2 p-2 rounded-pill">
                                        <i class="bi bi-file-earmark-check text-white"></i> 
                                        <span id="file_name_<?= $ms['id_milestone'] ?>" class="text-truncate" style="max-width: 200px;">filename.jpg</span>
                                        <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.65rem;" onclick="clearFile(<?= $ms['id_milestone'] ?>)" aria-label="Batal"></button>
                                    </span>
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    <div class="position-relative w-100">
                                        <input type="text" name="pesan" class="form-control rounded-pill bg-light" style="padding-right: 45px;" placeholder="Ketik pesan atau revisi..." autocomplete="off">
                                        
                                        <!-- Tombol Upload File -->
                                        <label for="upload_<?= $ms['id_milestone'] ?>" class="position-absolute text-muted" style="right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                            <i class="bi bi-paperclip fs-5" id="icon_clip_<?= $ms['id_milestone'] ?>" title="Upload Bukti (Maks 5MB)"></i>
                                        </label>
                                        <!-- Menghapus onchange alert dan memanggil fungsi JS -->
                                        <input type="file" name="file_lampiran" id="upload_<?= $ms['id_milestone'] ?>" class="d-none" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" onchange="showFileIndicator(this, <?= $ms['id_milestone'] ?>)">
                                    </div>

                                    <!-- Tombol Kirim dengan Animasi Loading -->
                                    <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center btn-submit" style="width: 45px; height: 42px; flex-shrink: 0;">
                                        <i class="bi bi-send-fill icon-send"></i>
                                        <span class="spinner-border spinner-border-sm d-none icon-spinner" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-scroll chat ke paling bawah
    document.querySelectorAll('.chat-container').forEach(box => {
        box.scrollTop = box.scrollHeight;
    });
</script>
<script>
    // 1. FUNGSI UNTUK MENAMPILKAN INDIKATOR FILE
    function showFileIndicator(input, milestoneId) {
        const indicator = document.getElementById('file_indicator_' + milestoneId);
        const nameSpan = document.getElementById('file_name_' + milestoneId);
        const iconClip = document.getElementById('icon_clip_' + milestoneId);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if(file.size > 5242880) { alert('Ukuran file terlalu besar! Maksimal 5 MB.'); clearFile(milestoneId); return; }
            nameSpan.textContent = file.name;
            indicator.classList.remove('d-none');
            iconClip.classList.remove('text-muted');
            iconClip.classList.add('text-primary');
        } else { clearFile(milestoneId); }
    }

    function clearFile(milestoneId) {
        const input = document.getElementById('upload_' + milestoneId);
        const indicator = document.getElementById('file_indicator_' + milestoneId);
        const iconClip = document.getElementById('icon_clip_' + milestoneId);
        if(input) input.value = ''; 
        if(indicator) indicator.classList.add('d-none');
        if(iconClip) { iconClip.classList.add('text-muted'); iconClip.classList.remove('text-primary'); }
    }

    // 2. MENGIRIM PESAN TANPA REFRESH HALAMAN (AJAX)
    document.querySelectorAll('.chat-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const btn = this.querySelector('.btn-submit');
            const iconSend = this.querySelector('.icon-send');
            const iconSpinner = this.querySelector('.icon-spinner');
            const milestoneId = this.querySelector('input[name="id_milestone"]').value;
            
            btn.disabled = true;
            if(iconSend) iconSend.classList.add('d-none');
            if(iconSpinner) iconSpinner.classList.remove('d-none');

            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(() => {
                this.reset(); 
                clearFile(milestoneId); 
                
                // Paksa scroll ke bawah (true) karena user baru saja mengirim pesan
                loadChats(true); 

                btn.disabled = false;
                if(iconSend) iconSend.classList.remove('d-none');
                if(iconSpinner) iconSpinner.classList.add('d-none');
            })
            .catch(error => {
                alert('Gagal mengirim pesan, periksa koneksi Anda.');
                btn.disabled = false;
                if(iconSend) iconSend.classList.remove('d-none');
                if(iconSpinner) iconSpinner.classList.add('d-none');
            });
        });
    });

    // 3. MENARIK DATA CHAT SECARA OTOMATIS DENGAN SCROLL CERDAS
    function loadChats(forceScroll = false) {
        document.querySelectorAll('.chat-container').forEach(container => {
            const milestoneId = container.id.split('-')[2]; 
            
            fetch(`ajax_chat.php?id_milestone=${milestoneId}`)
            .then(response => response.text())
            .then(html => {
                if (container.innerHTML.trim() !== html.trim()) {
                    
                    // Cek apakah posisi scroll saat ini berada di paling bawah (toleransi 50px)
                    const isAtBottom = (container.scrollTop + container.clientHeight) >= (container.scrollHeight - 50);
                    
                    container.innerHTML = html;
                    
                    // Auto-scroll HANYA jika sedang di bawah, ATAU jika forceScroll aktif (habis kirim pesan)
                    if (isAtBottom || forceScroll) {
                        container.scrollTop = container.scrollHeight; 
                    }
                }
            });
        });
    }

    // Tarik chat pertama kali saat halaman baru dimuat (paksa scroll ke bawah)
    loadChats(true);
    
    // Periksa chat baru setiap 3 detik (tanpa memaksa scroll)
    setInterval(() => loadChats(false), 3000);
</script>
</body>
</html>