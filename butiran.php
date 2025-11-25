<?php
/*******************************************************
 *  Butiran Produk – Sistem Usahawan Pahang
 *******************************************************/

// DB config
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "sistem_usahawan_pahang";

$hasDb = true;
$conn  = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_errno) $hasDb = false;

// Get product ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($hasDb && $id > 0) {
    $sql = "SELECT p.id, p.nama, p.harga, p.deskripsi, p.gambar_url, p.lokasi, p.stok,
                   u.nama AS usahawan, u.telefon,
                   k.nama AS kategori
            FROM produk p
            LEFT JOIN usahawan u ON u.id = p.usahawan_id
            LEFT JOIN kategori k ON k.id = p.kategori_id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

// Demo fallback
if (!$product) {
    $product = [
        'id'=>1,
        'nama'=>"Produk Demo",
        'harga'=>29.90,
        'deskripsi'=>"Deskripsi ringkas produk demo.",
        'gambar_url'=>"https://picsum.photos/800/600?random=1",
        'lokasi'=>"Kuantan",
        'stok'=>12,
        'usahawan'=>"Usahawan Demo",
        'telefon'=>"01112345678",
        'kategori'=>"Makanan & Minuman"
    ];
}

// WhatsApp link
function waLink($phone, $msg) {
  $phone = preg_replace('/\D+/', '', $phone);
  if ($phone && $phone[0]==='0') $phone = '6'.$phone;
  $text = urlencode($msg);
  return "https://wa.me/$phone?text=$text";
}
$msg = "Hai ".$product['usahawan']."! Saya berminat dengan '".$product['nama']."' (RM ".number_format((float)$product['harga'],2)."). Boleh info lanjut?";
$wa  = waLink($product['telefon'], $msg);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Butiran Produk – <?= htmlspecialchars($product['nama']) ?></title>
  <style>
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
    /* ===== Container ===== */
    .container{max-width:1000px;margin:auto;padding:20px}

    /* ===== Card Produk ===== */
    .card{
      background:var(--card);
      border-radius:16px;
      box-shadow:0 6px 20px rgba(0,0,0,.1);
      padding:20px;
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:24px;
    }
    @media(max-width:768px){.card{grid-template-columns:1fr}}

    .card img{
      width:100%; border-radius:12px; object-fit:cover;
    }
    .pname{font-size:28px;font-weight:800;margin:0 0 12px;color:var(--primary)}
    .price{font-size:22px;font-weight:700;color:var(--secondary);margin-bottom:12px}
    .muted{color:var(--muted);margin-bottom:8px}
    .desc{line-height:1.6;margin-top:10px}

    /* ===== Button ===== */
    .btn{
      padding:12px 18px;
      border-radius:30px;
      border:0;
      cursor:pointer;
      font-weight:600;
      display:inline-block;
      text-align:center;
      transition:.3s;
      margin-right:8px;
    }
    .btn-wa{background:#22c55e;color:#fff}
    .btn-wa:hover{background:#1da851}
    .btn-back{background:#e5e7eb;color:#111}
    .btn-back:hover{background:#d1d5db}

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
  </style>
</head>
<body>
<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="promosi-pasaran.php" class="active"><strong>Pasaran</strong></a>
  </nav>
</header>

<div class="container">
  <div class="card">
    <div>
      <img src="<?= htmlspecialchars('uploads/' . $product['gambar_url']) ?>" alt="<?= htmlspecialchars($product['nama']) ?>">
    </div>
    <div>
      <h2 class="pname"><?= htmlspecialchars($product['nama']) ?></h2>
      <div class="price">RM <?= number_format((float)$product['harga'],2) ?></div>
      <div class="muted">Kategori: <?= htmlspecialchars($product['kategori']) ?></div>
      <div class="muted">Lokasi: <?= htmlspecialchars($product['lokasi']) ?> · Stok: <?= (int)$product['stok'] ?></div>
      <p class="desc"><?= nl2br(htmlspecialchars($product['deskripsi'])) ?></p>
      <div class="muted" style="margin-top:10px;">Oleh: <b><?= htmlspecialchars($product['usahawan']) ?></b></div>
      <div style="margin-top:20px;">
        <a class="btn btn-wa" href="<?= $wa ?>" target="_blank">📲 WhatsApp Usahawan</a>
        <a class="btn btn-back" href="promosi-pasaran.php">← Kembali</a>
      </div>
    </div>
  </div>
</div>

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
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("show");
}
</script>
</body>
</html>
