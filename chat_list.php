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

  CASE
    WHEN cr.user_low = ? THEN cr.user_high
    ELSE cr.user_low
  END AS partner_id,

  u.nama   AS partner_name,
  u.avatar AS partner_avatar,

  lm.message AS last_message,
  lm.created_at AS last_message_time

FROM chat_rooms cr

JOIN usahawan u
  ON u.id = CASE
      WHEN cr.user_low = ? THEN cr.user_high
      ELSE cr.user_low
    END

LEFT JOIN chat_messages lm
  ON lm.id = (
      SELECT id
      FROM chat_messages
      WHERE chat_id = cr.id
        AND is_deleted = 0
      ORDER BY id DESC
      LIMIT 1
  )

WHERE cr.user_low = ? OR cr.user_high = ?

ORDER BY lm.created_at DESC, cr.created_at DESC
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

    <span style="font-size:13px;color:#777;">
        <?= !empty($row['last_message']) 
            ? htmlspecialchars($row['last_message']) 
            : 'Belum ada mesej' ?>
    </span>
  </div>
</a>

<?php endwhile; ?>
