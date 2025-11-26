<?php
session_start();

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sistem_usahawan_pahang";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// 🔹 Semak sama ada user sudah login
$is_logged_in = isset($_SESSION['usahawan_id']);
$user_id = $is_logged_in ? $_SESSION['usahawan_id'] : null;

// 🔹 Dapatkan jumlah cart jika sudah login
$cart_count = 0;
if ($is_logged_in) {
  $result_cart = $conn->query("SELECT COUNT(*) AS total FROM cart WHERE usahawan_id = '$user_id'");
  $cart_count = $result_cart ? $result_cart->fetch_assoc()['total'] : 0;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($search)) {
  $sql = "SELECT * FROM produk 
          WHERE nama LIKE '%$search%' 
          ORDER BY id DESC";
} else {
  $sql = "SELECT * FROM produk ORDER BY id DESC";
}

$result = $conn->query($sql);

?>




<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senarai Produk - Usahawan Pahang</title>
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">
<style>
/* ====== GLOBAL ====== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ===== Background Premium dengan Watermark Jata Pahang ===== */
body {
  margin: 0;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  margin-top: 90px;
}

/* ✨ Cahaya lembut keemasan & hitam bergerak */
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 25% 30%, rgba(0, 0, 0, 0.05), transparent 70%),
    radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.15), transparent 70%);
  background-repeat: no-repeat;
  animation: royalWave 25s ease-in-out infinite alternate;
  z-index: -3;
  mix-blend-mode: overlay;
}

/* 🏛️ Multiple Watermark Jata Pahang - lebih jelas */
body::after {
  content: "";
  position: fixed;
  inset: 0;
  background-color: transparent;
  background-image: url("assets/img/jatapahang.png");
  background-repeat: repeat;
  background-size: 180px 180px;
  background-position: center;
  opacity: 0.15; /* 🔆 Naikkan dari 0.07 → 0.15 supaya lebih nampak */
  filter: grayscale(5%) brightness(1.3) contrast(1.1);
  animation: watermarkFloat 40s linear infinite;
  z-index: -2;
}

/* ===== SEARCH BAR ===== */
.search-wrapper {
  max-width: 1200px;
  margin: 20px auto 10px auto;
  padding: 0 20px;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-box input {
  width: 100%;
  padding: 12px 15px 12px 45px;
  border-radius: 30px;
  border: 1px solid #ccc;
  font-size: 15px;
  outline: none;
  transition: 0.3s;
}

.search-box input:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0,123,255,0.3);
}

.search-icon {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #777;
}


/* 🌫️ Animasi lembut watermark */
@keyframes watermarkFloat {
  0% { background-position: 0 0; opacity: 0.14; }
  50% { background-position: 80px 60px; opacity: 0.18; }
  100% { background-position: 0 0; opacity: 0.14; }
}

/* 🪄 Efek cahaya bergerak lembut */
@keyframes royalWave {
  0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
  100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
}

/* ===== Kad (card) Optional ===== */
.card {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 215, 0, 0.4);
  border-radius: 14px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 25px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}


/* ===== Header ===== */
header {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  padding: 15px 20px;
  position: fixed;
  top: 0; left: 0; width: 100%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  flex-wrap: wrap;
}

header img.jata { height: 55px; }
.title { color: #fff; font-size: 1.4rem; font-weight: 700; }

.menu-toggle {
  display: none;
  font-size: 1.8rem;
  cursor: pointer;
  background: none;
  border: none;
  color: #fff;
}

/* ===== Navbar ===== */
nav {
  display: flex;
  gap: 15px;
}

nav a {
  color: #fff;
  padding: 8px 12px;
  font-weight: 500;
  text-decoration: none;
  transition: 0.3s;
}
nav a:hover, nav a.active { color: #ffd700; }

/* ===== 3D Metallic Title ===== */
header .title {
  position: relative;
  color: #ffffffff;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-align: center;
  text-shadow:
    0 1px 0 #b3b3b3,
    0 2px 0 #999,
    0 3px 0 #777,
    0 4px 0 #555,
    0 5px 8px rgba(0,0,0,0.6);
  background: linear-gradient(90deg, #e6e6e6 0%, #bfbfbf 50%, #f2f2f2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
  overflow: hidden;
}

/* Subtle animated shine */
header .title::after {
  content: "";
  position: absolute;
  top: 0; left: -75%;
  width: 50%; height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.6) 50%,
    rgba(255,255,255,0) 100%
  );
  animation: textshine 4s linear infinite;
}

@keyframes textshine {
  0% { left: -75%; }
  100% { left: 125%; }
}

/* ===== Metallic Shine Animation ===== */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* ===== Cart Icon di Header ===== */
.cart-icon {
  font-size: 1.8rem;
  color: #fff;
  cursor: pointer;
  margin-left: auto;
  margin-right: 10px;
  transition: transform 0.2s ease, color 0.2s ease;
}

.cart-icon:hover {
  transform: scale(1.15);
  color: #ffd700;
}

/* ===== PRODUK GRID ===== */
.produk-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap:20px;
  padding:20px;
  max-width:1200px;
  margin:auto;
}

.produk-card {
  background:#fff;
  border-radius:10px;
  overflow:hidden;
  box-shadow:0 2px 10px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
  cursor: pointer;
}
.produk-card:hover { transform:translateY(-5px); }
.produk-card img { width:100%; height:200px; object-fit:cover; }
.produk-info { padding:15px; }
.produk-info h3 { font-size:1.1em; color:#222; }
.harga { font-weight:bold; color:#e67e22; margin:6px 0; }
.lokasi { color:#666; font-size:13px; margin-bottom:8px; }

/* ===== BUTTONS ===== */
.btn-group { display:flex; gap:8px; }
.btn {
  flex:1;
  text-align:center;
  padding:8px 10px;
  border:none;
  border-radius:5px;
  font-size:14px;
  cursor:pointer;
  transition:0.3s;
}
.btn-cart { background:#007bff; color:#fff; }
.btn-cart:hover { background:#0056b3; }
.btn-chat { background:#25D366; color:#fff; }
.btn-chat:hover { background:#1eb255; }

/* ===== Footer ===== */
footer {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  color: #fff;
  padding: 30px 20px;
  margin-top: 40px;
  text-align: center;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

footer .footer-content {
  max-width: 1100px;
  margin: auto;
  position: relative;
  z-index: 1;
}

footer img {
  height: 60px;
  margin-bottom: 15px;
  filter: drop-shadow(0 3px 5px rgba(0,0,0,0.4));
}

/* ===== 3D Metallic Text ===== */
footer p,
footer .copyright,
footer strong {
  color: #f8f8f8;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-shadow:
    0 1px 0 #ccc,
    0 2px 0 #aaa,
    0 3px 0 #888,
    0 4px 0 #666,
    0 5px 0 #444,
    0 6px 6px rgba(0,0,0,0.6);
  transition: transform 0.3s ease, text-shadow 0.3s ease;
}

/* Glow and depth on hover */
footer p:hover,
footer strong:hover {
  transform: translateY(-2px);
  text-shadow:
    0 1px 0 #fff,
    0 2px 0 #ddd,
    0 3px 0 #bbb,
    0 4px 0 #999,
    0 5px 0 #777,
    0 8px 12px rgba(0, 0, 0, 0.7),
    0 0 10px rgba(255, 255, 255, 0.3);
}

/* Copyright (subtle) */
footer .copyright {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.2);
  font-size: 0.85rem;
  color: #ddd;
  text-shadow:
    0 1px 0 #999,
    0 2px 0 #666,
    0 3px 3px rgba(0,0,0,0.6);
}

/* Metallic Shine Animation */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .slideshow-container { height: 300px; }
  .function-btn { min-height: 130px; }
  .function-btn i { font-size: 2rem; }
}

@media (max-width: 768px) {
  .menu-toggle { display: block; }
  nav {
    display: none;
    flex-direction: column;
    background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    width: 100%;
  }
  nav.show { display: flex; }
  nav a { text-align: center; padding: 10px; font-size: 1rem; }
  .title { font-size: 1.2rem; }
  .slideshow-container { height: 220px; }
  .function-grid { gap: 18px; }
  .function-btn { min-height: 110px; padding: 18px; }
  .function-btn i { font-size: 1.8rem; }
  .function-btn span { font-size: 0.9rem; }
}

@media (max-width: 480px) {
  .slideshow-container { height: 180px; }
  .function-btn { padding: 15px; }
}


/* ===== MODAL (POPUP) ===== */
.modal {
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.6);
  align-items:center;
  justify-content:center;
  z-index:2000;
}
.modal-content {
  background:#fff;
  border-radius:10px;
  max-width:600px;
  width:90%;
  padding:20px;
  box-shadow:0 5px 25px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
}
.modal-content img {
  width:100%;
  border-radius:10px;
  height:300px;
  object-fit:cover;
}
.modal-details {
  margin-top:15px;
}
.modal-details h2 {
  font-size:1.4rem;
  margin-bottom:5px;
}
.modal-details .harga {
  color:#e67e22;
  font-weight:bold;
  margin-bottom:10px;
}
.modal-buttons {
  display:flex;
  gap:10px;
  margin-top:15px;
}
.modal-close {
  position:absolute;
  top:15px; right:20px;
  background:none;
  border:none;
  font-size:24px;
  color:#333;
  cursor:pointer;
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>

  <!-- Butang menu (mobile) -->
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>

  <!-- Navigation -->
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <!-- 🛒 Ikon Troli -->
  <div class="cart-icon" onclick="bukaCart()" title="Lihat Troli">
  🛒<?= $cart_count > 0 ? "[$cart_count]" : "" ?>
</div>

  </nav>

  
</header>

<!-- ===== PRODUK LIST ===== -->
<main>
    <!-- ===== SEARCH BAR ===== -->
  <div class="search-wrapper">
    <div class="search-box">
      <span class="search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari produk..." autocomplete="off">
    </div>
  </div>

  <div class="produk-container" id="produkContainer">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="produk-card" onclick="bukaPopup(<?= htmlspecialchars(json_encode($row)) ?>)">
          <img src="<?= htmlspecialchars('uploads/'.$row['gambar_url']) ?>" alt="<?= htmlspecialchars($row['nama']) ?>">
          <div class="produk-info">
            <h3><?= htmlspecialchars($row['nama']) ?></h3>
            <p class="harga">RM <?= number_format($row['harga'], 2) ?></p>
            <p class="lokasi">📍 <?= htmlspecialchars($row['lokasi']) ?></p>
            <div class="btn-group">
              <button class="btn btn-cart"
                onclick="event.stopPropagation(); tambahKeCart(
                  <?= (int)$row['id'] ?>,
                  '<?= htmlspecialchars(addslashes($row['nama'])) ?>',
                  <?= (float)$row['harga'] ?>,
                  '<?= htmlspecialchars(addslashes($row['gambar_url'])) ?>'
                )">🛒 Add to Cart</button>

              <button class="btn btn-chat"
                onclick="event.stopPropagation(); bukaChat('<?= urlencode($row['nama']) ?>')">💬 Chat</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center; margin-top:50px;">Tiada produk ditemui.</p>
    <?php endif; ?>
  </div>
</main>

<!-- ===== MODAL (Popup Detail) ===== -->
<div class="modal" id="produkModal">
  <div class="modal-content">
    <button class="modal-close" onclick="tutupPopup()"><strong>×</strong></button>
    <img id="modalGambar" src="" alt="">
    <div class="modal-details">
      <h2 id="modalNama"></h2>
      <p class="harga" id="modalHarga"></p>
      <p id="modalDeskripsi"></p>
      <p id="modalLokasi"></p>
      <div class="modal-buttons">
        <button class="btn btn-cart" onclick="tambahKeCart(document.getElementById('modalNama').innerText)">🛒 Add to Cart</button>
        <button class="btn btn-chat" onclick="bukaChat(document.getElementById('modalNama').innerText)">💬 Chat</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Footer Rasmi ===== -->
<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | Emel: info@pahang.gov.my</p>
    <div class="copyright">
      © <?= date("Y") ?> Kerajaan Negeri Pahang. Hak cipta terpelihara.
    </div>
  </div>
</footer>

<script>
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}

// ========== SEARCH BAR ========== //
const searchInput = document.getElementById("searchInput");
const produkContainer = document.getElementById("produkContainer");

searchInput.addEventListener("keyup", function() {
  const value = this.value;

  fetch("?search=" + value)
    .then(res => res.text())
    .then(data => {
      // Ambil hanya bahagian produk sahaja
      const parser = new DOMParser();
      const html = parser.parseFromString(data, "text/html");
      const newProduk = html.querySelector("#produkContainer").innerHTML;

      produkContainer.innerHTML = newProduk;
    });
});

// ========== FUNGSI TAMBAH KE CART ========== //
async function tambahKeCart(produk_id, nama, harga, gambar_url) {
  console.log('🟢 START - Data yang dihantar:', {
    produk_id: produk_id,
    nama: nama,
    harga: harga,
    gambar_url: gambar_url
  });

  try {
    const formData = new URLSearchParams({
      produk_id: produk_id,
      nama: nama,
      harga: harga,
      gambar_url: gambar_url,
      kuantiti: 1
    });

    console.log('🟡 FormData:', formData.toString());

    const response = await fetch('add_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });

    console.log('🟠 Response status:', response.status);

    const text = await response.text();
    console.log('🔵 Response text:', text);

    const data = JSON.parse(text);
    console.log('🟣 Parsed JSON:', data);

    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('⚠️ ' + data.message);
    }
  } catch (error) {
    console.error('🔴 ERROR:', error);
    alert('❌ Error: ' + error.message);
  }
}

function bukaChat(nama){
  const url = "https://wa.me/60123456789?text=Hai,%20saya%20berminat%20dengan%20produk%20" + encodeURIComponent(nama);
  window.open(url, "_blank");
}

// ===== Popup Produk =====
function bukaPopup(data){
  document.getElementById("modalGambar").src = "uploads/" + data.gambar_url;
  document.getElementById("modalNama").innerText = data.nama;
  document.getElementById("modalHarga").innerText = "RM " + parseFloat(data.harga).toFixed(2);
  document.getElementById("modalDeskripsi").innerText = data.deskripsi;
  document.getElementById("modalLokasi").innerText = "📍 " + data.lokasi;
  document.getElementById("produkModal").style.display = "flex";
}

function tutupPopup(){
  document.getElementById("produkModal").style.display = "none";
}

function bukaCart(){
  window.location.href = "cart.php";
}

</script>

</body>
</html>

<?php $conn->close(); ?>
