<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "connection.php";

/* =====================================================
   1. LOGIN CHECK
===================================================== */
if (!isset($_SESSION['usahawan_id'])) {
    return;
}

$user_id = (int) $_SESSION['usahawan_id'];

/* =====================================================
   2. CURRENT CHAT (ACTIVE STATE)
===================================================== */
$current_chat_id = isset($_GET['chat_id']) ? (int) $_GET['chat_id'] : 0;

/* =====================================================
   3. GET CHAT LIST WITH UNREAD COUNT
===================================================== */
$stmt = $conn->prepare("
SELECT
  cr.id AS chat_id,

  CASE
    WHEN cr.user_low = ? THEN cr.user_high
    ELSE cr.user_low
  END AS partner_id,

  u.nama   AS partner_name,
  u.avatar AS partner_avatar,

  lm.message       AS last_message,
  lm.message_type  AS last_message_type,
  lm.file_name     AS last_file_name,
  lm.sender_id     AS last_sender_id,
  lm.created_at    AS last_message_time,

  (
    SELECT COUNT(*)
    FROM chat_messages
    WHERE chat_id = cr.id
      AND sender_id != ?
      AND is_deleted = 0
      AND is_read = 0
  ) AS unread_count

FROM chat_rooms cr

JOIN usahawan u
  ON u.id = CASE
      WHEN cr.user_low = ? THEN cr.user_high
      ELSE cr.user_low
    END

LEFT JOIN chat_messages lm
  ON lm.id = (
      SELECT id FROM chat_messages
      WHERE chat_id = cr.id
        AND is_deleted = 0
      ORDER BY id DESC
      LIMIT 1
  )

WHERE cr.user_low = ? OR cr.user_high = ?

ORDER BY lm.created_at DESC, cr.created_at DESC
");

$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

/* =====================================================
   HELPER: Format timestamp WhatsApp-style
===================================================== */
function formatChatTime($timestamp) {
    if (empty($timestamp)) return '';
    $now  = new DateTime();
    $then = new DateTime($timestamp);
    $diff = $now->diff($then);

    if ($diff->days === 0)      return $then->format('H:i');
    if ($diff->days === 1)      return 'Semalam';
    if ($diff->days < 7)        return $then->format('D'); // Mon, Tue...
    return $then->format('d/m/y');
}

/* =====================================================
   HELPER: Preview text (WhatsApp style, short)
===================================================== */
function previewText($message, $type, $fileName, $isMine) {
    $prefix = $isMine ? 'Anda: ' : '';

    switch ($type) {
        case 'image':
            return $prefix . '📷 Gambar';
        case 'file':
            $ext = strtolower(pathinfo($fileName ?? '', PATHINFO_EXTENSION));
            $icons = [
                'pdf'  => '📄 PDF',
                'doc'  => '📝 Word', 'docx' => '📝 Word',
                'xls'  => '📊 Excel','xlsx' => '📊 Excel',
                'zip'  => '🗜 ZIP',  'rar'  => '🗜 RAR',
                'mp4'  => '🎥 Video','mp3'  => '🎵 Audio',
            ];
            return $prefix . ($icons[$ext] ?? '📎 Fail');
        case 'card':
        case 'servis':
            return $prefix . '🏷 Kad produk';
        case 'system':
            return '<em style="color:#a0a0a0">' . htmlspecialchars(mb_strimwidth($message ?? '', 0, 30, '...')) . '</em>';
        default:
            if (empty($message)) return '<span style="color:#b0b0b0">Belum ada mesej</span>';
            // Trim to ~35 chars
            $clean = strip_tags($message);
            return $prefix . htmlspecialchars(mb_strimwidth($clean, 0, 35, '…'));
    }
}
?>

<style>
/* ===== SIDEBAR HEADER ===== */
.sidebar-header {
  padding: 16px 16px 10px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  background: rgba(247,231,198,0.5);
}
.sidebar-header h4 {
  font-size: 0.82rem;
  font-weight: 800;
  color: #7a5c1e;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.sidebar-header h4 i { font-size: 0.75rem; }

/* ===== EMPTY STATE ===== */
.sidebar-empty {
  padding: 40px 16px;
  text-align: center;
  color: #b0a898;
}
.sidebar-empty i { font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 8px; }
.sidebar-empty p { font-size: 0.8rem; }

/* ===== CHAT ITEM ===== */
.chat-item {
  padding: 11px 14px;
  display: flex;
  gap: 11px;
  align-items: center;
  text-decoration: none;
  color: #1e1e1e;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  transition: background 0.18s, transform 0.15s;
  position: relative;
  cursor: pointer;
}
.chat-item:hover {
  background: rgba(201,161,74,0.07);
  transform: translateX(2px);
}
.chat-item.active {
  background: linear-gradient(90deg, rgba(201,161,74,0.2), rgba(255,255,255,0.5));
  border-left: 4px solid #c9a14a;
}
.chat-item.unread-item {
  background: rgba(201,161,74,0.05);
}

/* Avatar */
.ci-avatar {
  position: relative;
  flex-shrink: 0;
}
.ci-avatar img {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(201,161,74,0.25);
  display: block;
}
.ci-online {
  position: absolute;
  bottom: 1px; right: 1px;
  width: 11px; height: 11px;
  background: #22c55e;
  border-radius: 50%;
  border: 2px solid #fff;
}

/* Text block */
.ci-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.ci-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}
.ci-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.ci-time {
  font-size: 0.68rem;
  color: #94a3b8;
  flex-shrink: 0;
  white-space: nowrap;
}
.ci-time.has-unread { color: #c9a14a; font-weight: 700; }

.ci-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}
.ci-preview {
  font-size: 0.77rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  line-height: 1.35;
}
.ci-preview.bold-preview {
  color: #334155;
  font-weight: 600;
}

/* Unread badge */
.unread-badge {
  background: linear-gradient(135deg, #d4b26a, #c9a14a);
  color: #fff;
  border-radius: 20px;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  font-size: 0.68rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(201,161,74,0.4);
  line-height: 1;
}

.chat-list-wrapper{
  margin-top:40px;
}
</style>

<div class="chat-list-wrapper">
<div class="sidebar-header">
  <h4><i class="fas fa-comments"></i> Perbualan</h4>
</div>

<?php if ($result->num_rows === 0): ?>
  <div class="sidebar-empty">
    <i class="fas fa-comment-slash"></i>
    <p>Tiada perbualan lagi</p>
  </div>
<?php endif; ?>

<?php while ($row = $result->fetch_assoc()):

  /* ---- AVATAR ---- */
  $avatar = $row['partner_avatar'];
  if (!empty($avatar)) {
      if (strpos($avatar, 'uploads/') === false) $avatar = 'uploads/' . $avatar;
      if (!file_exists($avatar)) $avatar = 'assets/img/default_avatar.jpg';
  } else {
      $avatar = 'assets/img/default_avatar.jpg';
  }

  $isActive  = ($current_chat_id === (int)$row['chat_id']);
  $unread    = (int)$row['unread_count'];
  $isMine    = ((int)$row['last_sender_id'] === $user_id);
  $timeLabel = formatChatTime($row['last_message_time']);
  $preview   = previewText(
      $row['last_message'],
      $row['last_message_type'] ?? 'text',
      $row['last_file_name']    ?? '',
      $isMine
  );
?>

<a href="chat_room.php?chat_id=<?= (int)$row['chat_id'] ?>"
   class="chat-item <?= $isActive ? 'active' : '' ?> <?= ($unread > 0 && !$isActive) ? 'unread-item' : '' ?>">

  <div class="ci-avatar">
    <img src="<?= htmlspecialchars($avatar) ?>"
         alt="<?= htmlspecialchars($row['partner_name']) ?>"
         onerror="this.src='assets/img/default_avatar.jpg'">
  </div>

  <div class="ci-body">
    <div class="ci-top">
      <span class="ci-name"><?= htmlspecialchars($row['partner_name']) ?></span>
      <?php if ($timeLabel): ?>
        <span class="ci-time <?= $unread > 0 ? 'has-unread' : '' ?>"><?= $timeLabel ?></span>
      <?php endif; ?>
    </div>

    <div class="ci-bottom">
      <span class="ci-preview <?= ($unread > 0 && !$isActive) ? 'bold-preview' : '' ?>">
        <?= $preview ?>
      </span>
      <?php if ($unread > 0 && !$isActive): ?>
        <span class="unread-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
      <?php endif; ?>
    </div>
  </div>

</a>

<?php endwhile; ?>
</div>