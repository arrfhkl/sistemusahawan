<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("Login dahulu");
}

$user_id = (int)$_SESSION['usahawan_id'];

$chat_id    = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
$partner_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$produk_id = isset($_GET['produk_id']) ? (int)$_GET['produk_id'] : 0;

if ($chat_id === 0 && $partner_id === 0) {
    $no_chat_selected = true;
} else {
    $no_chat_selected = false;
}

if (!$no_chat_selected) {
/* ===============================
   UPDATE ONLINE STATUS
================================ */
$conn->query("
  INSERT INTO user_online_status (user_id, last_active)
  VALUES ($user_id, NOW())
  ON DUPLICATE KEY UPDATE last_active = NOW()
");

/* ===============================
   LOAD CHAT BASED ON chat_id
================================ */
if ($chat_id > 0) {
    $stmt = $conn->prepare("
        SELECT user_low, user_high, produk_id
        FROM chat_rooms
        WHERE id = ?
          AND (user_low = ? OR user_high = ?)
        LIMIT 1
    ");

    $stmt->bind_param("iii", $chat_id, $user_id, $user_id);
    $stmt->execute();
    $chat = $stmt->get_result()->fetch_assoc();

    if (!$chat) {
        die("Chat tidak dijumpai");
    }

    $partner_id = ($user_id == $chat['user_low'])
        ? $chat['user_high']
        : $chat['user_low'];

    // ✅ Set produk_id if not already from URL
    if ($produk_id === 0 && isset($chat['produk_id']) && $chat['produk_id'] > 0) {
    $produk_id = (int)$chat['produk_id'];
}
}

/* ===============================
   CHECK EXISTING CHAT IF OPEN VIA user_id
================================ */
else {

    $low  = min($user_id, $partner_id);
    $high = max($user_id, $partner_id);

    $stmt = $conn->prepare("
        SELECT id
        FROM chat_rooms
        WHERE user_low = ? AND user_high = ?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $low, $high);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        header("Location: chat_room.php?chat_id=".$existing['id']."&produk_id=".$produk_id);
        exit;
    }

    // Chat belum wujud – akan create bila mesej pertama dihantar
}

/* ===============================
   LOAD PARTNER INFO
================================ */
$stmt2 = $conn->prepare("
    SELECT nama, avatar
    FROM usahawan
    WHERE id = ?
    LIMIT 1
");

$stmt2->bind_param("i", $partner_id);
$stmt2->execute();
$partner = $stmt2->get_result()->fetch_assoc();

if (!$partner) {
    die("User tidak dijumpai");
}

$header_name   = $partner['nama'];
$header_avatar = !empty($partner['avatar'])
    ? $partner['avatar']
    : 'assets/img/default_avatar.jpg';
}

$produk_chat = null;

// Fallback: if produk_id not in URL, get it from chat_rooms
if ($produk_id === 0 && isset($chat['produk_id']) && $chat['produk_id'] > 0) {
    $produk_id = (int)$chat['produk_id'];
}

if ($produk_id > 0) {
    $stmtProduk = $conn->prepare("
        SELECT id, nama, harga, gambar_url, stok
        FROM produk
        WHERE id = ?
        LIMIT 1
    ");
    $stmtProduk->bind_param("i", $produk_id);
    $stmtProduk->execute();
    $produk_chat = $stmtProduk->get_result()->fetch_assoc();
}

include "header.php";
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat Room</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
    * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}


@keyframes floatLight{
  from{transform:scale(1)}
  to{transform:scale(1.06)}
}

/* ===== LAYOUT ===== */
.chat-layout{
  width: calc(100vw - 60px);      /* full desktop */
  height: calc(100vh - 140px);    /* full tinggi skrin */
  margin: 110px auto 30px;
  display: flex;
  background: rgba(255,255,255,.55);
  backdrop-filter: blur(14px);
  border-radius: 18px;
  box-shadow: 0 18px 50px rgba(0,0,0,.12);
  overflow: hidden;
}

/* ===== SIDEBAR ===== */
.chat-sidebar{
  flex: 0 0 33.333%;
  max-width: 360px; /* elak terlalu besar */
  background: rgba(245,242,235,.92);
  border-right: 1px solid rgba(0,0,0,.08);
  overflow-y: auto;
}


.chat-item{
  padding:14px;
  display:flex;
  gap:12px;
  text-decoration:none;
  color:#333;
  border-bottom:1px solid rgba(0,0,0,.06);
  transition:.25s;
}
.chat-item:hover{background:rgba(0,0,0,.04)}
.chat-item.active{
  background:linear-gradient(90deg,#f5e6c8,#ffffff);
  border-left:4px solid #c9a14a;
}

.chat-item img{
  width:44px;height:44px;border-radius:50%;
}

/* ===== CHAT MAIN ===== */
.chat-main{
  flex: 1; /* auto jadi 2/3 */
  display: flex;
  flex-direction: column;
  background: rgba(255,255,255,.65);
}
.chat-item{
  padding: 16px;
  display: flex;
  gap: 14px;
  align-items: center;
  text-decoration: none;
  color: #333;
  border-bottom: 1px solid rgba(0,0,0,.05);
  transition: background .25s, transform .2s;
}

.chat-item:hover{
  background: rgba(0,0,0,.04);
  transform: translateX(3px);
}

.chat-item.active{
  background: linear-gradient(90deg,#f3e1b8,#ffffff);
  border-left: 5px solid #c9a14a;
}

.chat-item img{
  width: 46px;
  height: 46px;
  border-radius: 50%;
  object-fit: cover;
}


/* HEADER */
.chat-header{
  padding:18px;
  display:flex;
  gap:14px;
  align-items:center;
  background:
    linear-gradient(135deg,#f7e7c6,#e6d3a3);
  border-bottom:1px solid rgba(0,0,0,.1);
}

.chat-header img{
  width:48px;height:48px;border-radius:50%;
}

#status{
  font-size:12px;
  color:#555;
}

/* SERVIS CARD */
.servis-card{
  display:flex;
  align-items:center;      
  gap:20px;
  padding:8px 12px;       
  background:rgba(255,255,255,.75);
  border-bottom:1px solid rgba(0,0,0,.08);
}

.servis-card img{
  width:50px;            
  height:50px;
  border-radius:6px;
  object-fit:cover;
  flex-shrink:0;
}

.servis-card div{
  line-height:1.7;
  font-size:14px;
}

/* CHAT BOX */
.chat-box{
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background:
    linear-gradient(
      rgba(255,255,255,.6),
      rgba(255,255,255,.8)
    );
}


/* MESSAGE */
.msg{
  max-width:72%;
  padding:14px 18px;
  margin-bottom:12px;
  border-radius:16px;
  font-size:15px;
  line-height:1.5;
}

.msg.system {
  text-align: center;
  background: #f7f5ef;
  color: #555;
  font-size: 14px;
}

.msg.system a {
  color: #b89544;
  text-decoration: underline;
}

@keyframes fadeUp{
  from{opacity:0;transform:translateY(6px)}
  to{opacity:1}
}

.me{
  background:linear-gradient(135deg,#e8d4a4,#f6ebcf);
  margin-left:auto;
  text-align:left;
}
.other{
  background:#ffffff;
  border:1px solid rgba(0,0,0,.08);
}

.meta{
  font-size:12px;
  color:#777;
  margin-top:6px;
  text-align:right;
}

/* INPUT */
.chat-input{
  display:flex;
  gap:10px;
  padding:16px;
  background:rgba(250,250,250,.95);
  border-top:1px solid rgba(0,0,0,.08);
}
.chat-input input{
  flex:1;
  padding:12px;
  border-radius:8px;
  border:1px solid rgba(0,0,0,.15);
}
.chat-input button{
  background:linear-gradient(135deg,#d4b26a,#b89544);
  color:#fff;
  border:none;
  padding:12px 22px;
  border-radius:8px;
  cursor:pointer;
}
.chat-input button:hover{opacity:.9}
/* ================================
   CHAT SIZE IMPROVEMENT (DESKTOP)- change this to resize to chat length
================================ */
.chat-layout{
  max-width: 1400px;          /* LEBIH BESAR */
  height: calc(100vh - 160px);/* Full tinggi skrin */
}

/* CHAT BOX LEBIH BESAR */
.chat-box{
  font-size: 15px;
  line-height: 1.5;
}

.meta{
  font-size: 12px;
}

/* INPUT BESAR & SELESA */
.chat-input{
  padding: 18px;
}

.chat-input input{
  font-size: 15px;
  padding: 14px;
}

.chat-input button{
  font-size: 15px;
  padding: 14px 26px;
}

/* HEADER BESAR */
.chat-header{
  padding: 20px;
}

.chat-header strong{
  font-size: 16px;  
}

/* SERVIS CARD BESAR */
.servis-card img{
  width: 80px;
  height: 80px;
}

@media (max-width: 900px){
  .chat-layout{
    width: 100vw;
    margin: 90px 0 0;
    border-radius: 0;
  }

  .chat-sidebar{
    display: none;
  }
}

/* SYSTEM MESSAGE */
.msg.system {
  max-width: 100%;
  margin: 18px auto;
  padding: 10px 16px;
  background: transparent;
  color: #888;
  font-size: 13px;
  text-align: center;
  font-style: italic;
}

.msg.system a {
  color: #b89544;
  text-decoration: underline;
  font-weight: 600;
}
.msg.system a:hover {
  opacity: 0.8;
}

.btn-quotation{
  background: linear-gradient(135deg,#d4b26a,#b89544);
  color:#fff;
  padding:14px 26px;
  border-radius:8px;
  text-decoration:none;
  font-size:15px;
}
.btn-quotation:hover{opacity:.9}

</style>
</head>

<body>

<div class="chat-layout">

    <!-- SIDEBAR -->
    <div class="chat-sidebar">
        <?php include "chat_list.php"; ?>
    </div>

    <!-- MAIN CHAT -->
    <div class="chat-main">

        <!-- HEADER -->
         <?php if ($no_chat_selected): ?>
              <div class="chat-header">
                  <div>
                      <strong>Tiada perbualan dipilih</strong><br>
                  </div>
              </div>
          <?php else: ?>
              <div class="chat-header">
                  <img src="<?= htmlspecialchars($header_avatar) ?>"
                      onerror="this.src='assets/img/default_avatar.jpg'">
                  <div>
                      <strong><?= htmlspecialchars($header_name) ?></strong><br>
                      <span id="status">Menyemak status...</span>
                  </div>
              </div>
          <?php endif; ?>

<?php if (!$no_chat_selected && $produk_chat): ?>
<div class="servis-card">
    <img src="<?= htmlspecialchars(
        strpos($produk_chat['gambar_url'], 'uploads/') === false
        ? 'uploads/'.$produk_chat['gambar_url']
        : $produk_chat['gambar_url']
    ) ?>" onerror="this.src='assets/img/default_avatar.jpg'">

    <div>
        <strong><?= htmlspecialchars($produk_chat['nama']) ?></strong><br>
        RM <?= number_format($produk_chat['harga'],2) ?><br>

        <span style="font-size:13px; color:<?= $produk_chat['stok'] > 0 ? '#2e7d32' : '#c62828' ?>;">
            <?= $produk_chat['stok'] > 0 
                ? $produk_chat['stok']." unit tersedia" 
                : "Stok habis" ?>
        </span>
    </div>
</div>
<?php endif; ?>

        <!-- MESSAGE AREA -->
        <?php if ($no_chat_selected): ?>
            <div class="chat-box">
                <div class="msg system">
                    Anda belum memilih sebarang perbualan.
                </div>
            </div>
        <?php else: ?>
            <div class="chat-box" id="chat-box"></div>
        <?php endif; ?>

        <!-- INPUT -->
        <?php if (!$no_chat_selected): ?>
        <div class="chat-input">
            <input type="text" id="msg" placeholder="Taip mesej...">
            <button onclick="sendMsg()">Hantar</button>
        </div>
        <?php endif; ?>

    </div>

</div>

<script>
const chatId = <?= $no_chat_selected ? 0 : $chat_id ?>;


//old file
let lastMessageId = 0;
let renderedMessageIds = new Set();

let shouldAutoScroll = true;

function loadMsg(){
  fetch("load_messages.php?chat_id="+chatId+"&last_id="+lastMessageId)
    .then(r=>r.json())
    .then(messages=>{
      if(messages.length === 0) return;

      messages.forEach(m => {
        if (renderedMessageIds.has(m.id)) return; // ⛔ STOP duplicate

        renderedMessageIds.add(m.id);

        const div = document.createElement("div");
        if (m.sender_id == 0) {
          div.className = "msg system";
        } else {
          div.className = "msg " + (m.is_me ? "me" : "other");
        }

        div.innerHTML = `
          ${m.message.replace(/\n/g,"<br>")}
          <div class="meta">${m.time}</div>
        `;

        document.getElementById("chat-box").appendChild(div);

        lastMessageId = Math.max(lastMessageId, m.id);
      });


      document.getElementById("chat-box").scrollTop =
        document.getElementById("chat-box").scrollHeight;
    });
}

/* SEND MESSAGE */
function sendMsg(){
  const input = document.getElementById("msg");
  const msg = input.value.trim();
  if(!msg) return;

  if (chatId === 0) {
    // FIRST MESSAGE → CREATE CHAT
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "create_chat.php";

form.innerHTML = `
  <input name="partner_id" value="<?= $partner_id ?>">
  <input name="produk_id" value="<?= $produk_id ?>">
  <input name="message" value="${msg.replace(/"/g,'&quot;')}">
`;
    document.body.appendChild(form);
    form.submit();
    return;
  }

  // chat dah ada
  fetch("send_message.php",{
    method:"POST",
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:"chat_id="+chatId+"&message="+encodeURIComponent(msg)
  }).then(()=>{
    input.value="";
    loadMsg();
  });
}

/* STATUS ONLINE */
setInterval(()=>{
  fetch("check_status.php?chat_id="+chatId)
    .then(r=>r.text())
    .then(s=>{
      document.getElementById("status").innerHTML =
        s==="online"
        ? "🟢 Online"
        : "⚫ Offline";
    });
},5000);

if (chatId > 0) {

  // LOAD MESSAGE TERUS MASA PAGE LOAD
  loadMsg();

  // CHECK STATUS TERUS MASA PAGE LOAD
  fetch("check_status.php?chat_id="+chatId)
    .then(r=>r.text())
    .then(s=>{
      document.getElementById("status").innerHTML =
        s==="online"
        ? "🟢 Online"
        : "⚫ Offline";
    });

  // POLLING MESSAGE SETIAP 2 SAAT
  setInterval(loadMsg, 2000);

  // POLLING STATUS SETIAP 5 SAAT
  setInterval(()=>{
    fetch("check_status.php?chat_id="+chatId)
      .then(r=>r.text())
      .then(s=>{
        document.getElementById("status").innerHTML =
          s==="online"
          ? "🟢 Online"
          : "⚫ Offline";
      });
  }, 5000);

  // TYPING EVENT
  document.getElementById("msg").addEventListener("input", () => {
    fetch("update_typing.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "chat_id=" + chatId
    });
  });
}

  setInterval(() => {
    fetch("check_typing.php?chat_id="+chatId)
      .then(r=>r.text())
      .then(status => {
        document.getElementById("typing").style.display =
          (status === "typing") ? "block" : "none";
      });
  }, 1500);

</script>

<?php include "footer.php"; ?>
</body>
</html>