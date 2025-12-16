<?php
session_start();
include("connection.php");

if (!isset($_GET['id'])) {
  die("Servis tidak ditemui.");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
SELECT 
  s.*, 
  u.nama AS nama_tukang,
  u.perniagaan,
  u.jenis,
  u.telefon,
  u.avatar,
  u.tarikh_daftar
FROM servis s
LEFT JOIN usahawan u ON s.usahawan_id = u.id
WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
  die("Servis tidak ditemui.");
}

/* total customer */
$total_customer = 0;
$check = $conn->query("SHOW TABLES LIKE 'servis_booking'");
if ($check->num_rows > 0) {
  $stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM servis_booking WHERE service_id = ?");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $total_customer = (int)$stmt2->get_result()->fetch_assoc()['total'];
}

/* ✅ GALLERY */
$gallery = false;
$check2 = $conn->query("SHOW TABLES LIKE 'servis_gallery'");
if ($check2->num_rows > 0) {
  $stmt3 = $conn->prepare("SELECT * FROM servis_gallery WHERE service_id = ?");
  $stmt3->bind_param("i", $id);
  $stmt3->execute();
  $gallery = $stmt3->get_result();
}
?>


<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($service['nama']) ?> - Butiran Servis</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  font-family: Arial;
  margin-top: 90px;
  line-height: 1.7; 
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

.container { 
  max-width: 1100px; 
  background: white; 
  margin: 30px auto; 
  padding: 30px;          
  border-radius: 14px; 
  border: 2px solid #003366; 
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}


.cover img { width: 100%; height: 380px; object-fit: cover; border-radius: 12px; }

.tukang-card {
  display: flex; 
  gap: 20px; 
  background: #eef4ff;
  padding: 25px;              
  border-radius: 14px; 
  margin-top: 25px;
  border: 2px solid #007bff;   
}

.tukang-card img {
  width: 90px; height: 90px; border-radius: 50%;
  object-fit: cover; border: 3px solid #007bff;
}

.stats {
  margin-top: 25px; display: grid;
  grid-template-columns: repeat(3,1fr); gap: 15px;
}

.stat-box {
  background: #fafafa; 
  padding: 18px;                 
  border-radius: 12px; 
  text-align: center;
  font-weight: bold;
  border: 1.5px solid #ccc;    
}


.gallery-grid {
  margin-top: 10px;
  display: grid; grid-template-columns: repeat(4,1fr); gap: 12px;
}
.gallery-grid img {
  width: 100%; height: 180px;
  object-fit: cover; border-radius: 10px;
}

.btn-tempah, .btn-chat {
  width: 100%; margin-top: 15px;
  padding: 14px; border-radius: 8px;
  border: none; font-size: 16px; cursor: pointer;
}
.btn-tempah { background: #007bff; color: white; }
.btn-chat { background: #25D366; color: white; }

@media(max-width:768px){
  .gallery-grid { grid-template-columns: repeat(2,1fr); }
  .stats { grid-template-columns: repeat(2,1fr); }
  .tukang-card { flex-direction: column; text-align: center; }
}
</style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php" class="active"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
  </nav>
</header>

<div class="container">

<!-- ✅ GAMBAR SERVIS -->
<?php
$gambar = $service['gambar_servis_url'];
if (!empty($gambar) && strpos($gambar, 'uploads/') === false) {
  $gambar = "uploads/" . $gambar;
}
?>
<div class="cover">
  <img src="<?= htmlspecialchars($gambar) ?>">
</div>

<h1><?= htmlspecialchars($service['nama']) ?></h1>
<p>📍<strong> <?= htmlspecialchars($service['lokasi']) ?></p>

<div class="deskripsi">
<?= nl2br(htmlspecialchars($service['deskripsi'])) ?>
</div>

<!-- ✅ PROFIL USAHAWAN -->
<div class="tukang-card">
<?php
$avatar = $service['avatar'];
if (empty($avatar)) $avatar = "default.png";
if (strpos($avatar, 'uploads/') === false) $avatar = "uploads/$avatar";
?>
<img src="<?= htmlspecialchars($avatar) ?>">

<div>
  <p><strong><?= htmlspecialchars($service['nama_tukang']) ?></strong></p>
  <p>Perniagaan: <?= htmlspecialchars($service['perniagaan']) ?></p>
  <p>Jenis: <?= htmlspecialchars($service['jenis']) ?></p>
  <p>Telefon: <?= htmlspecialchars($service['telefon']) ?></p>
  <p>Daftar: <?= date("d M Y", strtotime($service['tarikh_daftar'])) ?></p>
</div>
</div>

<!-- ✅ STATISTIK -->
<div class="stats">
  <div class="stat-box">👥 <?= $total_customer ?> Pelanggan</div>
  <div class="stat-box">💼 Servis Aktif</div>
  <div class="stat-box">✅ Disahkan</div>
</div>

<!-- ✅ GALLERY -->
<div class="gallery">
<h3>Contoh Kerja</h3>

<div class="gallery-grid">
<?php if ($gallery && $gallery->num_rows > 0): ?>
  <?php while ($g = $gallery->fetch_assoc()): ?>
    <?php
      $gbr = $g['gambar'];
      if (strpos($gbr, 'uploads/') === false) $gbr = "uploads/$gbr";
    ?>
    <img src="<?= htmlspecialchars($gbr) ?>">
  <?php endwhile; ?>
<?php else: ?>
  <p>Tiada gambar kerja dimuat naik.</p>
<?php endif; ?>
</div>
</div>

<!-- Butang -->
<button class="btn-tempah"
onclick="window.location.href='tempah_servis.php?id=<?= $service['id'] ?>'">
Tempah Servis
</button>

<button class="btn-chat"
onclick="window.location.href='create_chat.php?servis_id=<?= $service['id'] ?>'">
💬 Chat Dengan Tukang
</button>

</button>

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
  function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
  }
  

</script>

</body>
</html>

<?php $conn->close(); ?>
