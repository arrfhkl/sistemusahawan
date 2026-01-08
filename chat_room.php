<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$chat_id = (int)($_GET['chat_id'] ?? 0);
$user_id = $_SESSION['usahawan_id'];

if ($chat_id === 0) {
  die("Chat tidak sah");
}

/* ===============================
   INFO CHAT + SERVIS
================================ */
$stmt = $conn->prepare("
  SELECT 
    cr.id AS chat_id,
    cr.servis_id,
    s.nama AS servis_nama,
    s.lokasi,
    s.gambar_servis_url,
    u.id AS tukang_id,
    u.nama AS nama_tukang,
    u.avatar
  FROM chat_rooms cr
  JOIN servis s ON s.id = cr.servis_id
  JOIN usahawan u ON u.id = s.usahawan_id
  WHERE cr.id = ?
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
  die("Chat tidak dijumpai");
}

$servis_id = $info['servis_id'];
$tukang_id = $info['tukang_id'];
$namaServis = $info['servis_nama'];

/* AUTO MESSAGE – hanya untuk pelanggan */
$sendAutoMessage = ($user_id != $tukang_id);

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
  animation:fadeUp .3s ease;
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
   CHAT SIZE IMPROVEMENT (DESKTOP)
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

/* MESSAGE BUBBLE BESAR */
.msg{
  max-width: 72%;
  padding: 14px 18px;
  border-radius: 16px;
  font-size: 15px;
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

</style>
</head>

<body>

<div class="chat-layout">

  <!-- SIDEBAR -->
  <div class="chat-sidebar">
    <?php include "chat_list.php"; ?>
  </div>

  <!-- CHAT -->
  <div class="chat-main">

    <!-- HEADER -->
    <div class="chat-header">
      <img src="<?= htmlspecialchars($info['avatar']) ?>">
      <div>
        <strong><?= htmlspecialchars($info['nama_tukang']) ?></strong><br>
        <span id="status">Menyemak status...</span>
        <div id="typing" style="font-size:12px;color:#666;display:none;">
  ✍️ Sedang menaip…
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
    </div>

  </div>
</div>

<script>
const chatId   = <?= $chat_id ?>;
const tukangId = <?= $tukang_id ?>;

let shouldAutoScroll = true;

function loadMsg(){
  const box = document.getElementById("chat-box");

  // Check kalau user dekat bawah
  const isNearBottom =
    box.scrollTop + box.clientHeight >= box.scrollHeight - 100;

  fetch("load_messages.php?chat_id="+chatId)
    .then(r=>r.text())
    .then(d=>{
      box.innerHTML = d;

      if (isNearBottom || shouldAutoScroll) {
        box.scrollTop = box.scrollHeight;
        shouldAutoScroll = false;
      }
    });
}


/* SEND MESSAGE */
function sendMsg(){
  const input = document.getElementById("msg");
  const msg = input.value.trim();
  if(!msg) return;


  shouldAutoScroll = true; // paksa scroll lepas send

  fetch("send_message.php",{
    method:"POST",
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:
      "chat_id="+chatId+
      "&message="+encodeURIComponent(msg)
  }).then(()=>{
    input.value="";
    loadMsg();
  });
}


/* AUTO MESSAGE – SEKALI SAHAJA */
<?php if($sendAutoMessage): ?>
window.addEventListener("load",()=>{
  fetch("send_message.php",{
    method:"POST",
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:
      "chat_id=<?= $chat_id ?>"+
      "&message=<?= urlencode("Hi, saya berminat dengan servis $namaServis") ?>"
  }).then(loadMsg);
});
<?php endif; ?>

/* STATUS ONLINE */
setInterval(()=>{
  fetch("check_status.php?user_id="+tukangId)
    .then(r=>r.text())
    .then(s=>{
      document.getElementById("status").innerHTML =
        s==="online"
        ? "🟢 Usahawan sedang online"
        : "⚪ Usahawan offline";
    });
},5000);

setInterval(loadMsg,2000);
loadMsg();

let typingTimer;
let isTyping = false;

document.getElementById("msg").addEventListener("input", () => {

  if (isTyping) return;

  isTyping = true;

  fetch("update_typing.php");

  clearTimeout(typingTimer);

  typingTimer = setTimeout(() => {
    isTyping = false;
  }, 3000);
});

setInterval(() => {
  fetch("check_typing.php?user_id="+tukangId)
    .then(r=>r.text())
    .then(status => {
      const el = document.getElementById("typing");
      el.style.display = (status === "typing") ? "block" : "none";
    });
}, 1500);


</script>

<?php
include 'footer.php';
?>

</body>
</html>
