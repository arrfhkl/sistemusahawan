<?php
include "connection.php";
include "header.php";

$conn->query("SET time_zone = '+08:00'");

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$user_id = $_SESSION['usahawan_id'];
$isSeller = false;
$isBuyer  = false;

$chat_id   = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
$servis_id = isset($_GET['servis_id']) ? (int)$_GET['servis_id'] : 0;

if ($chat_id === 0 && $servis_id === 0) {
  die("Chat tidak sah");
}


/* ===============================
   UPDATE ONLINE STATUS
================================ */
$conn->query("
  INSERT INTO user_online_status (user_id, last_active)
  VALUES ($user_id, NOW())
  ON DUPLICATE KEY UPDATE last_active = NOW()
");

if ($chat_id === 0 && $servis_id > 0) {

  $stmt = $conn->prepare("
    SELECT 
      s.id AS servis_id,
      s.nama AS servis_nama,
      s.lokasi,
      s.gambar_servis_url,
      u.id AS tukang_id,
      u.nama AS nama_tukang,
      u.avatar AS avatar_tukang
    FROM servis s
    JOIN usahawan u ON u.id = s.usahawan_id
    WHERE s.id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $servis_id);
  $stmt->execute();
  $info = $stmt->get_result()->fetch_assoc();

  if (!$info) {
    die("Servis tidak dijumpai");
  }

  $tukang_id = $info['tukang_id'];
  $isSeller  = ($user_id == $tukang_id);
  $isBuyer   = !$isSeller;

  $header_name   = $info['nama_tukang'];
  $header_avatar = $info['avatar_tukang'] ?? 'assets/img/default_avatar.jpg';

  $namaServis = $info['servis_nama'];
}

if ($chat_id > 0) {

/* ===============================
   INFO CHAT + SERVIS
================================ */
$stmt = $conn->prepare("
SELECT 
    cr.id AS chat_id,
    cr.user_a,
    cr.user_b,
    cr.servis_id,
    s.nama AS servis_nama,
    s.lokasi,
    s.gambar_servis_url,
    u.id AS tukang_id,
    u.nama AS nama_tukang,
    u.avatar AS avatar_tukang
  FROM chat_rooms cr
  JOIN servis s ON s.id = cr.servis_id
  JOIN usahawan u ON u.id = s.usahawan_id
  WHERE cr.id = ?
    AND (cr.user_a = ? OR cr.user_b = ?)
  LIMIT 1
");
$stmt->bind_param("iii", $chat_id, $user_id, $user_id);
$stmt->execute();

$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
  die("Chat tidak dijumpai");
}
$other_user_id = ($user_id == $info['user_a'])
  ? $info['user_b']
  : $info['user_a'];

}

if (!isset($info)) {
  die("Data tidak lengkap");
}

$tukang_id = $info['tukang_id'];
$isSeller  = ($user_id == $tukang_id);


if ($isSeller && $chat_id > 0) {

  // seller login → lawan = buyer (juga usahawan)
  $stmt2 = $conn->prepare("
    SELECT nama, avatar
    FROM usahawan
    WHERE id = ?
    LIMIT 1
  ");
  $stmt2->bind_param("i", $other_user_id);
  $stmt2->execute();
  $other = $stmt2->get_result()->fetch_assoc();

  $header_name   = $other['nama'] ?? 'Pengguna';
  $header_avatar = $other['avatar'] ?? 'assets/img/default_avatar.jpg';

} else {
  // buyer login → lawan = seller (owner servis)
  $header_name   = $info['nama_tukang'];
  $header_avatar = $info['avatar_tukang'];
}

$isBuyer   = !$isSeller;
$servis_id = $info['servis_id'];
$namaServis = $info['servis_nama'];

/* ===============================
   CHECK REQUESTED QUOTATION (SELLER)
================================ */
$requestedQuotationId = null;

if ($isSeller) {
  $q = $conn->prepare("
    SELECT id
    FROM quotation
    WHERE chat_id = ?
      AND seller_id = ?
      AND status = 'requested'
    LIMIT 1
  ");
  $q->bind_param("ii", $chat_id, $user_id);
  $q->execute();
  $res = $q->get_result()->fetch_assoc();

  if ($res) {
    $requestedQuotationId = (int)$res['id'];
  }
}


/* AUTO MESSAGE – hanya untuk pelanggan */
$sendAutoMessage = ($chat_id > 0 && $user_id != $tukang_id);

/* GAMBAR SERVIS */
$gambar = $info['gambar_servis_url']
  ? (strpos($info['gambar_servis_url'], 'uploads/') === false
      ? "uploads/" . $info['gambar_servis_url']
      : $info['gambar_servis_url'])
  : "assets/img/no-image.png";
  
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
  gap:12px;
  padding:14px;
  background:rgba(255,255,255,.75);
  border-bottom:1px solid rgba(0,0,0,.08);
}
.servis-card img{
  width:72px;height:72px;
  border-radius:10px;
  object-fit:cover;
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

<div class="chat-layout">

  <!-- SIDEBAR -->
  <div class="chat-sidebar">
    <?php include "chat_list.php"; ?>
  </div>

  <!-- CHAT -->
  <div class="chat-main">
  
    <!-- HEADER -->
    <div class="chat-header">
      <img src="<?= htmlspecialchars($header_avatar) ?>"
     onerror="this.src='assets/img/default_avatar.jpg'">
      <div>
        <strong><?= htmlspecialchars($header_name) ?></strong><br>
        <span id="status">Menyemak status...</span>
        <div id="typing" style="font-size:12px;color:#666;display:none;">
   Sedang menaip…✍️
</div>

      </div>
    </div>

    <!-- SERVIS -->
    <div class="servis-card">
      <img src="<?= htmlspecialchars($gambar) ?>">
      <div>
        <strong><?= htmlspecialchars($info['servis_nama']) ?></strong><br>
        <small><?= htmlspecialchars($info['lokasi']) ?></small>
      </div>
    </div>

    <!-- MESSAGE -->
    <div class="chat-box" id="chat-box"></div>
    
    <!-- INPUT -->
    <div class="chat-input">
      <input type="text" id="msg" placeholder="Taip mesej...">
      <button onclick="sendMsg()">Hantar</button>

      <?php if ($isBuyer): ?>
      <button id="btn-request-quotation">
        📄 Minta Quotation Rasmi
        </button>
      <?php endif; ?>

      <?php if ($isSeller && $requestedQuotationId): ?>
        <a
          href="quotation_form.php?quotation_id=<?= $requestedQuotationId ?>&chat_id=<?= $chat_id ?>"
          class="btn-quotation"
        >
          📤 Hantar Quotation
        </a>
      <?php endif; ?>

        </div>

  </div>
</div>

<script>
let lastMessageId = 0;
let renderedMessageIds = new Set();
const chatId = <?= $chat_id ?>;
const tukangId = <?= $tukang_id ?>;

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
      <input name="servis_id" value="<?= $servis_id ?>">
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

  setInterval(()=>{ /* check_status */ }, 5000);
  setInterval(loadMsg, 2000);

  document.getElementById("msg").addEventListener("input", () => {
    fetch("update_typing.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "chat_id=" + chatId
    });
  });

  setInterval(() => {
    fetch("check_typing.php?chat_id="+chatId)
      .then(r=>r.text())
      .then(status => {
        document.getElementById("typing").style.display =
          (status === "typing") ? "block" : "none";
      });
  }, 1500);

}
</script>

<script>
const btn = document.getElementById("btn-request-quotation");

btn?.addEventListener("click", ()=>{
  if(!confirm("Hantar permintaan quotation rasmi?")) return;

  // 🔒 lock + tukar teks
  btn.disabled = true;
  btn.textContent = "⏳ Menghantar...";

  fetch("request_quotation.php",{
    method:"POST",
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:"chat_id="+chatId
  })
  .then(r=>r.text())
  .then(res=>{
    if(res==="OK"){
      alert("Permintaan quotation dihantar");
      // optional: hide button selepas berjaya
      btn.style.display = "none";
    }else{
      alert(res);
      btn.disabled = false;
      btn.textContent = "📄 Minta Quotation Rasmi";
    }
  })
  .catch(()=>{
    btn.disabled = false;
    btn.textContent = "📄 Minta Quotation Rasmi";
  });
});

</script>
  
<?php
include 'footer.php';
?>

</body>
</html>
