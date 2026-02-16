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
   3. GET CHAT LIST (ORDER BY LAST MESSAGE)
   - 1 servis + 2 user = 1 chat
   - Order ikut mesej terakhir (UX betul)
===================================================== */
$stmt = $conn->prepare("
SELECT
  cr.id AS chat_id,
  cr.servis_id,

  CASE
    WHEN cr.user_a = ? THEN cr.user_b
    ELSE cr.user_a
  END AS partner_id,

  u.nama   AS partner_name,
  u.avatar AS partner_avatar,

  s.nama AS servis_nama,

  MAX(cm.created_at) AS last_message_time

FROM chat_rooms cr

JOIN usahawan u
  ON u.id = CASE
      WHEN cr.user_a = ? THEN cr.user_b
      ELSE cr.user_a
    END

JOIN servis s
  ON s.id = cr.servis_id

LEFT JOIN chat_messages cm
  ON cm.chat_id = cr.id

WHERE cr.user_a = ? OR cr.user_b = ?

GROUP BY cr.id

ORDER BY last_message_time DESC, cr.created_at DESC
");

$stmt->bind_param(
    "iiii",
    $user_id,
    $user_id,
    $user_id,
    $user_id
);

$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($result->num_rows === 0): ?>
  <div style="padding:16px;color:#777;font-size:14px;">
    Tiada perbualan lagi
  </div>
<?php endif; ?>

<?php while ($row = $result->fetch_assoc()): ?>

<?php
/* =====================================================
   4. AVATAR SAFETY
===================================================== */
$avatar = $row['partner_avatar'];

if (!empty($avatar)) {

    if (strpos($avatar, 'uploads/') === false) {
        $avatar = 'uploads/' . $avatar;
    }

    if (!file_exists($avatar)) {
        $avatar = 'assets/img/default_avatar.jpg';
    }

} else {
    $avatar = 'assets/img/default_avatar.jpg';
}


/* =====================================================
   5. ACTIVE STATE
===================================================== */
$isActive = ($current_chat_id === (int)$row['chat_id']);
?>

<a href="chat_room.php?chat_id=<?= (int)$row['chat_id'] ?>"
   class="chat-item <?= $isActive ? 'active' : '' ?>">

 <img src="<?= htmlspecialchars($avatar) ?>"
     alt="Avatar"
     onerror="this.src='assets/img/default_avatar.jpg'">


  <div>
    <strong><?= htmlspecialchars($row['partner_name']) ?></strong><br>
    <small><?= htmlspecialchars($row['servis_nama']) ?></small>
  </div>
</a>

<?php endwhile; ?>
