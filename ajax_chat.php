<?php
// ajax_chat.php
require_once 'config.php';

// Validasi Keamanan (Hanya Staff dan Klien yang login yang boleh mengakses)
$is_staff = isset($_SESSION['user_id']);
$is_client = isset($_SESSION['klien_id']);

if (!$is_staff && !$is_client) {
    die('Akses ditolak.');
}

$id_milestone = filter_input(INPUT_GET, 'id_milestone', FILTER_VALIDATE_INT);
if (!$id_milestone) die('ID Milestone tidak valid.');

$pengirim_role = $is_staff ? 'Staff' : 'Klien';

// Ambil data chat dari database
$stmtChat = $pdo->prepare("SELECT * FROM milestone_chat WHERE id_milestone = ? ORDER BY waktu_kirim ASC");
$stmtChat->execute([$id_milestone]);
$chats = $stmtChat->fetchAll(PDO::FETCH_ASSOC);

if (empty($chats)) {
    echo '<div class="text-center text-muted small my-4">Belum ada diskusi di tahapan ini.</div>';
    exit;
}

// Render tampilan HTML Chat
foreach ($chats as $chat) {
    $is_me = ($chat['role_pengirim'] == $pengirim_role);
    $bubble_class = $is_me ? 'chat-me' : 'chat-other';
    $sender_name = ($chat['role_pengirim'] == 'Staff') ? 'Tim Support' : 'Klien';
    ?>
    <div class="d-flex flex-column mb-3 <?= $is_me ? 'align-items-end' : 'align-items-start' ?>">
        <small class="text-muted mb-1" style="font-size: 11px;"><?= $sender_name ?> • <?= date('H:i', strtotime($chat['waktu_kirim'])) ?></small>
        <div class="chat-bubble <?= $bubble_class ?> shadow-sm">
            
            <?php if (!empty($chat['pesan'])): ?>
                <div class="<?= !empty($chat['file_lampiran']) ? 'mb-2' : '' ?>"><?= nl2br(htmlspecialchars($chat['pesan'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($chat['file_lampiran'])): 
                $ext = strtolower(pathinfo($chat['file_lampiran'], PATHINFO_EXTENSION));
                $img_exts = ['jpg', 'jpeg', 'png', 'webp'];
            ?>
                <div>
                    <?php if (in_array($ext, $img_exts)): ?>
                        <a href="uploads/milestone/<?= $chat['file_lampiran'] ?>" target="_blank">
                            <img src="uploads/milestone/<?= $chat['file_lampiran'] ?>" alt="Bukti Revisi" class="img-fluid rounded" style="max-height: 200px; border: 1px solid #dee2e6;">
                        </a>
                    <?php else: ?>
                        <a href="uploads/milestone/<?= $chat['file_lampiran'] ?>" target="_blank" class="btn btn-sm btn-light border text-dark text-decoration-none">
                            <i class="bi bi-file-earmark-arrow-down text-primary"></i> Unduh File (<?= strtoupper($ext) ?>)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    <?php
}
?>