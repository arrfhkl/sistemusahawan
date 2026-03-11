<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$user_id = $_SESSION['usahawan_id'];

/*
  Ambil semua chat + last message
*/
$stmt = $conn->prepare("
SELECT
  cr.id AS chat_id,

  u.nama AS partner_name,
  u.avatar AS partner_avatar,
  s.nama AS servis_nama,

  cm.message AS last_message,
  cm.created_at AS last_time

FROM chat_rooms cr
JOIN servis s ON s.id = cr.servis_id

JOIN usahawan u
  ON u.id = CASE
      WHEN cr.user_a = ? THEN cr.user_b
      ELSE cr.user_a
    END

LEFT JOIN chat_messages cm
  ON cm.id = (
    SELECT id
    FROM chat_messages
    WHERE chat_id = cr.id
      AND is_deleted = 0
    ORDER BY created_at DESC
    LIMIT 1
  )

WHERE cr.user_a = ? OR cr.user_b = ?
ORDER BY last_time DESC
");

$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Inbox Mesej</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ================================
   PAGE LAYOUT FIX
================================ */
body{
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

/* MAIN CONTENT (PUSH FOOTER DOWN) */
.page-wrapper{
  flex:1;
}

/* ================================
   INBOX UI
================================ */
.inbox-wrap{
  max-width:1100px;
  margin:24px auto 40px; /* 👈 SEDIKIT SPACE ATAS */
  padding:0 20px;
}

.inbox-card{
  background:rgba(255,255,255,.7);
  backdrop-filter:blur(12px);
  border-radius:18px;
  box-shadow:0 18px 45px rgba(0,0,0,.12);
  overflow:hidden;
}

/* HEADER */
.inbox-title{
  padding:18px 22px;
  font-weight:600;
  border-bottom:1px solid rgba(0,0,0,.08);
  background:linear-gradient(135deg,#f7e7c6,#e6d3a3);
}

/* CHAT ROW */
.chat-row{
  display:flex;
  align-items:center;
  gap:14px;
  padding:14px 20px;
  border-bottom:1px solid rgba(0,0,0,.05);
  text-decoration:none;
  color:#333;
  transition:.25s;
}

.chat-row:hover{
  background:rgba(0,0,0,.04);
}

/* AVATAR */
.chat-row img{
  width:46px;
  height:46px;
  border-radius:50%;
  object-fit:cover;
}

/* INFO */
.chat-info{
  flex:1;
  min-width:0;
}

.chat-info strong{
  display:block;
  font-size:14px;
}

.chat-info .servis{
  font-size:12px;
  color:#777;
}

.chat-info .preview{
  font-size:13px;
  color:#444;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  margin-top:2px;
}

/* TIME */
.chat-time{
  font-size:11px;
  color:#888;
  white-space:nowrap;
}

/* EMPTY STATE */
.empty{
  padding:40px;
  text-align:center;
  color:#777;
}
</style>
</head>

<body>

<main class="page-wrapper">

  <div class="inbox-wrap">

    <div class="inbox-card">

      <div class="inbox-title">
        📩 Inbox Mesej
      </div>

      <?php if($result->num_rows === 0): ?>
        <div class="empty">
          Tiada mesej daripada pelanggan
        </div>
      <?php endif; ?>

      <?php while($row = $result->fetch_assoc()): ?>
        <a href="chat_room.php?chat_id=<?= $row['chat_id'] ?>" class="chat-row">

          <img src="<?= htmlspecialchars($avatar) ?>" onerror="this.src='assets/img/default_avatar.jpg'">


          <div class="chat-info">
            <strong><?= htmlspecialchars($row['partner_name']) ?></strong>
            <span class="servis"><?= htmlspecialchars($row['servis_nama']) ?></span>
            <div class="preview">
              <?= htmlspecialchars($row['last_message'] ?? 'Tiada mesej') ?>
            </div>
          </div>

          <div class="chat-time">
            <?= $row['last_time'] ? date("H:i", strtotime($row['last_time'])) : '' ?>
          </div>

        </a>
      <?php endwhile; ?>

    </div>

  </div>

</main>

<?php include "footer.php"; ?>

</body>
</html>
