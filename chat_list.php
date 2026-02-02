<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    return; // sidebar kosong kalau tak login
}

$user_id = $_SESSION['usahawan_id'];

$current_chat_id   = $_GET['chat_id']   ?? null;
$current_servis_id = $_GET['servis_id'] ?? null;

/*
  Ambil SEMUA chat yang user terlibat
  (hanya chat yang benar-benar wujud)
*/
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

  s.nama AS servis_nama

FROM chat_rooms cr
JOIN usahawan u 
  ON u.id = CASE 
      WHEN cr.user_a = ? THEN cr.user_b
      ELSE cr.user_a
    END
JOIN servis s ON s.id = cr.servis_id

WHERE cr.user_a = ? OR cr.user_b = ?
ORDER BY cr.id DESC
");

$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
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
  /* ===== AVATAR SAFETY ===== */
  $avatar = $row['partner_avatar'] ?: 'assets/img/default_avatar.jpg';
  if (strpos($avatar, 'uploads/') === false) {
      $avatar = 'uploads/' . $avatar;
  }

  /* ===== ACTIVE STATE ===== */
  $isActive = false;

  if ($current_chat_id && $current_chat_id == $row['chat_id']) {
      $isActive = true;
  }

  // optional: highlight servis yang sama (preview mode)
  if (!$current_chat_id && $current_servis_id == $row['servis_id']) {
      $isActive = true;
  }
?>

<a href="chat_room.php?chat_id=<?= $row['chat_id'] ?>"
   class="chat-item <?= $isActive ? 'active' : '' ?>">

  <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">

  <div>
    <strong><?= htmlspecialchars($row['partner_name']) ?></strong><br>
    <small><?= htmlspecialchars($row['servis_nama']) ?></small>
  </div>
</a>

<?php endwhile; ?>
