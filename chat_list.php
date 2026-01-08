<?php
if (!isset($_SESSION)) session_start();
include "connection.php";

$user_id = $_SESSION['usahawan_id'];

/*
  Ambil SEMUA chat yang user terlibat
*/
$stmt = $conn->prepare("
SELECT 
  cr.id AS chat_id,

  CASE 
    WHEN cr.user_a = ? THEN cr.user_b
    ELSE cr.user_a
  END AS partner_id,

  u.nama AS partner_name,
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

<?php while ($row = $result->fetch_assoc()): ?>
<a href="chat_room.php?chat_id=<?= $row['chat_id'] ?>"
   class="chat-item <?= (($_GET['chat_id'] ?? '') == $row['chat_id']) ? 'active' : '' ?>">

  <img src="<?= htmlspecialchars($row['partner_avatar']) ?>">

  <div>
    <strong><?= htmlspecialchars($row['partner_name']) ?></strong><br>
    <small><?= htmlspecialchars($row['servis_nama']) ?></small>
  </div>
</a>
<?php endwhile; ?>
