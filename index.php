<?php
// ====== Sambungan Database ======
session_start();

include "connection.php";

if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// 🔹 Semak sama ada user sudah login
$is_logged_in = isset($_SESSION['usahawan_id']);
$user_id = $is_logged_in ? $_SESSION['usahawan_id'] : null;

// ====== 1. Jumlah Usahawan ======
$usahawan = $conn->query("SELECT COUNT(*) AS total FROM usahawan");
$total_usahawan = ($usahawan->num_rows > 0) ? $usahawan->fetch_assoc()['total'] : 0;

// ====== 2. Jumlah Geran Selesai (Semua Jenis Permohonan) ======
$geran_selesai = $conn->query("
    SELECT COUNT(*) AS total FROM (
        SELECT id FROM permohonan_ipush WHERE status = 'selesai'
        UNION ALL
        SELECT id FROM permohonan_agro WHERE status = 'selesai'
        UNION ALL
        SELECT id FROM permohonan_itekad WHERE status = 'selesai'
    ) AS semua
");
$total_geran_selesai = ($geran_selesai->num_rows > 0) ? $geran_selesai->fetch_assoc()['total'] : 0;

// ====== 3. Nilai Geran Selesai (Jumlah Keseluruhan RM) ======
$nilai_geran = $conn->query("
    SELECT SUM(jumlah) AS jumlah FROM (
        SELECT jumlah FROM permohonan_ipush WHERE status = 'selesai'
        UNION ALL
        SELECT jumlah FROM permohonan_agro WHERE status = 'selesai'
        UNION ALL
        SELECT jumlah FROM permohonan_itekad WHERE status = 'selesai'
    ) AS semua
");
$total_nilai_geran = ($nilai_geran->num_rows > 0)
    ? number_format($nilai_geran->fetch_assoc()['jumlah'] ?? 0, 2)
    : "0.00";


// ====== 4. Jumlah Pelawat ======
$conn->query("CREATE TABLE IF NOT EXISTS statistik_pelawat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page VARCHAR(100),
  jumlah INT DEFAULT 0
)");
$page = "index";
$check = $conn->query("SELECT jumlah FROM statistik_pelawat WHERE page='$page'");
if ($check->num_rows > 0) {
  $conn->query("UPDATE statistik_pelawat SET jumlah = jumlah + 1 WHERE page='$page'");
} else {
  $conn->query("INSERT INTO statistik_pelawat (page, jumlah) VALUES ('$page', 1)");
}
$pelawat = $conn->query("SELECT jumlah FROM statistik_pelawat WHERE page='$page'");
$total_pelawat = ($pelawat->num_rows > 0) ? $pelawat->fetch_assoc()['jumlah'] : 1;

// ====== Ambil berita dari database ======
$sql = "SELECT * FROM berita ORDER BY tarikh DESC";
$result = $conn->query($sql);
$berita_list = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $berita_list[] = $row;
  }
}

?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Usahawan Pahang</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">

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

/* ===== Container & Cards (Parallelogram Metallic Style) ===== */
.container {
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}

/* Parallelogram metallic cards */
.card {
  position: relative;
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  background-size: 200% 200%;
  animation: metalshine 6s linear infinite;
  padding: 40px 25px;
  margin: 30px auto;
  border-radius: 15px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  text-align: center;
  color: #fff;
  overflow: hidden;
  transform: skew(-10deg);
  transition: all 0.4s ease;
  width: 100%;
  max-width: 1200px;
}

/* Shine animation for the metallic gradient */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Inner text alignment fix (straight text inside skewed box) */
.card > * {
  transform: skew(10deg);
  position: relative;
  z-index: 2;
}

/* Optional shine overlay for more metallic effect */
.card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
      120deg,
      rgba(255,255,255,0.2) 0%,
      rgba(255,255,255,0) 60%
  );
  transform: translateX(-100%) skew(10deg);
  transition: transform 0.6s ease;
  z-index: 1;
}

.card:hover::after {
  transform: translateX(100%) skew(10deg);
}

.card:hover {
  transform: skew(-10deg) scale(1.03);
  box-shadow: 0 8px 22px rgba(0,0,0,0.3), 0 0 15px rgba(0,128,255,0.4);
}


/* ===== Stylish Parallelogram Slideshow ===== */
.slideshow-container {
  position: relative;
  width: 100%;
  height: 400px;
  overflow: hidden;
  margin: 25px auto;
  transform: skew(-10deg);
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
}

.slides {
  display: none;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: skew(10deg);
  animation: fade 1.2s ease-in-out;
}

@keyframes fade {
  from { opacity: 0.6; }
  to { opacity: 1; }
}

.dots {
  text-align: center;
  position: absolute;
  bottom: 15px;
  width: 100%;
  transform: skew(10deg);
}

.dot {
  cursor: pointer;
  height: 12px; width: 12px;
  margin: 0 4px;
  background: #ccc;
  border-radius: 50%;
  display: inline-block;
  transition: background 0.4s ease;
}
.dot.active, .dot:hover { background: #ffd700; }

/* ===== Parallelogram Function Buttons ===== */
.function-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 25px;
  margin-top: 30px;
}

/* ===== Parallelogram Function Buttons with Image Background ===== */
.function-btn {
  position: relative;
  background: url('assets/img/fbbg.png') center/cover no-repeat;
  color: #FFD700; /* gold for contrast */
  text-align: center;
  font-weight: 700;
  text-decoration: none;
  padding: 25px 15px;
  border-radius: 12px;
  min-height: 140px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  transform: skew(-15deg);
  transition: all 0.4s ease;
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.25);
}

.function-btn i,
.function-btn span {
  transform: skew(15deg);
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
}

.function-btn i {
  font-size: 2.3rem;
  margin-bottom: 10px;
}

.function-btn:hover {
  transform: skew(-15deg) scale(1.05);
  box-shadow: 0 8px 22px rgba(0,0,0,0.3), 0 0 10px rgba(0, 0, 0, 0.4);
}

.function-btn::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.15);
  transform: translateX(-100%) skew(15deg);
  transition: transform 0.5s ease;
}

.function-btn:hover::after {
  transform: translateX(100%) skew(15deg);
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

/* ====== Struktur Umum ====== */
.berita-section {
  max-width: 1100px;
  margin: 80px auto;
  padding: 20px;
  font-family: "Poppins", sans-serif;
  text-align: center;
}
.headline-utama h1 {
  font-size: 1.8rem;
  color: #003366;
  margin-bottom: 5px;
}
.headline-sub {
  color: #555;
  font-size: 1rem;
  margin-bottom: 30px;
}

/* ====== Paparan Biasa ====== */
.berita-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 25px;
}
.berita-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}
.berita-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.berita-card img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}
.berita-content {
  padding: 15px;
  text-align: left;
}
.berita-content h3 {
  color: #003366;
  font-size: 1.1rem;
  margin-bottom: 8px;
}
.berita-content .tarikh {
  font-size: 0.85rem;
  color: #777;
  margin-bottom: 8px;
}
.berita-content p {
  font-size: 0.9rem;
  color: #444;
  line-height: 1.5;
}
.baca-lanjut {
  display: inline-block;
  margin-top: 10px;
  color: #0056b3;
  font-weight: 600;
  text-decoration: none;
}
.baca-lanjut:hover {
  color: #002b5c;
}

/* ====== 3D SLIDER DESIGN ====== */
.berita-slider {
  position: relative;
  width: 100%;
  height: 420px;
  perspective: 1200px;
  margin-top: 30px;
}
.berita-slider .berita-card {
  position: absolute;
  top: 0;
  left: 50%;
  transform-style: preserve-3d;
  transform-origin: center;
  width: 70%;
  opacity: 0;
  transition: all 1s ease;
  border-radius: 15px;
}
.berita-slider .berita-card img {
  height: 220px;
  border-radius: 10px 10px 0 0;
}
.berita-slider .berita-card.active {
  opacity: 1;
  transform: translateX(-50%) rotateY(0deg) scale(1);
  z-index: 2;
}
.berita-slider .berita-card.prev {
  opacity: 0.5;
  transform: translateX(-110%) rotateY(30deg) scale(0.9);
  z-index: 1;
}
.berita-slider .berita-card.next {
  opacity: 0.5;
  transform: translateX(10%) rotateY(-30deg) scale(0.9);
  z-index: 1;
}

/* ====== Responsif ====== */
@media (max-width: 768px) {
  .berita-slider .berita-card {
    width: 90%;
  }
}

/* ===== LED Ticker Statistik Section ===== */
.led-ticker {
  position: relative;
  overflow: hidden;
  width: 100%;
  background: linear-gradient(90deg, #001F3F, #003399, #0066FF, #001F3F);
  color: #fff;
  padding: 12px 0;
  font-family: 'Poppins', sans-serif;
  border-top: 3px solid #FFD700;
  border-bottom: 3px solid #FFD700;
  box-shadow: inset 0 0 10px #000, 0 -2px 10px rgba(0, 0, 0, 0.5);
}

.led-track {
  display: flex;
  width: max-content;
  animation: ledScroll 30s linear infinite;
}

.led-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1rem;
  padding: 0 40px;
  white-space: nowrap;
  color: #fff;
  text-shadow: 0 0 6px #68665cff, 0 0 12px #00FFFF;
  letter-spacing: 0.5px;
  transition: transform 0.3s;
}

.led-item i {
  color: #000000ff;
  font-size: 1.3rem;
}

.led-item span {
  font-weight: 700;
  color: #00FFFF;
}

@keyframes ledScroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .led-item {
    font-size: 0.9rem;
    padding: 0 25px;
  }
  .led-item i {
    font-size: 1.1rem;
  }
}

@media (max-width: 480px) {
  .led-item {
    font-size: 0.8rem;
    padding: 0 15px;
  }
}


  </style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>

  <nav id="navMenu">
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><strong>Laman Utama</strong></a>
    <a href="daftar.php" class="<?= basename($_SERVER['PHP_SELF']) == 'daftar.php' ? 'active' : '' ?>"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php" class="<?= basename($_SERVER['PHP_SELF']) == 'senarai.php' ? 'active' : '' ?>"><strong>Senarai Usahawan</strong></a>

    <?php if (isset($_SESSION['usahawan_id'])): ?>
      <a href="logout.php"><strong>Log Keluar</strong></a>
    <?php else: ?>
      <a href="login.php"><strong>Log Masuk</strong></a>
    <?php endif; ?>
  </nav>
</header>

<div class="container">
  <div class="card">
    <h2 style="color: white"><strong>Selamat Datang</strong></h2>
    <p style="color: white"><strong>Sistem ini membolehkan pendaftaran dan carian maklumat usahawan di Pahang.</strong></p>
  </div>

  <!-- Slideshow -->
  <div class="slideshow-container">
    <img class="slides" src="assets/img/slide1.jpg" alt="Slide 1">
    <img class="slides" src="assets/img/slide2.png" alt="Slide 2">
    <img class="slides" src="assets/img/slide3.jpeg" alt="Slide 3">

    <div class="dots">
      <span class="dot" onclick="currentSlide(1)"></span>
      <span class="dot" onclick="currentSlide(2)"></span>
      <span class="dot" onclick="currentSlide(3)"></span>
    </div>
  </div>
</div>

<!-- Function Buttons -->
<div class="container">
  <div class="function-grid">
    <a href="login.php" class="function-btn" style="color:white;">
      <i class="fas fa-user-plus" style="color:black;"></i>
      <span>Profil Usahawan</span>
    </a>
    <a href="latihan_panduan.php" class="function-btn" style="color:white;">
      <i class="fas fa-chalkboard-teacher" style="color:black;"></i>
      <span>Latihan & Panduan</span>
    </a>
    <a href="akses-geran.php" class="function-btn" style="color:white;">
      <i class="fas fa-hand-holding-usd" style="color:black;"></i>
      <span>Akses Geran</span>
    </a>
    <a href="ruang_fizikal.php" class="function-btn" style="color:white;">
      <i class="fas fa-building" style="color:black;"></i>
      <span>Ruang Fizikal</span>
    </a>
    <a href="promosi-pasaran.php" class="function-btn" style="color:white;">
      <i class="fas fa-bullhorn" style="color:black;"></i>
      <span>Promosi & Pasaran</span>
    </a>
    <a href="komuniti.php" class="function-btn" style="color:white;">
      <i class="fas fa-users" style="color:black;"></i>
      <span>Komuniti & Jejari</span>
    </a>
    <a href="admin_dashboard.php" class="function-btn" style="color:white;">
      <i class="fas fa-chart-line" style="color:black;"></i>
      <span>Dashboard</span>
    </a>
  </div>
</div>


<!-- ===== Berita Section ===== -->
<section class="berita-section">

  <?php if (count($berita_list) > 3): ?>
    <!-- ====== 3D SLIDER ====== -->
    <div class="berita-slider">
      <?php foreach ($berita_list as $index => $berita): ?>
        <article class="berita-card" style="--i:<?= $index ?>">
          <img src="<?= htmlspecialchars($berita['imej']) ?>" alt="<?= htmlspecialchars($berita['tajuk']) ?>">
          <div class="berita-content">
            <h3><?= htmlspecialchars($berita['tajuk']) ?></h3>
            <p class="tarikh"><?= date('d F Y', strtotime($berita['tarikh'])) ?></p>
            <p><?= htmlspecialchars($berita['kandungan']) ?></p>
            <a href="<?= htmlspecialchars($berita['pautan']) ?>" class="baca-lanjut">Baca Lanjut →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <!-- ====== PAPARAN BIASA ====== -->
    <div class="berita-container">
      <?php foreach ($berita_list as $berita): ?>
        <article class="berita-card">
          <img src="<?= htmlspecialchars($berita['imej']) ?>" alt="<?= htmlspecialchars($berita['tajuk']) ?>">
          <div class="berita-content">
            <h3><?= htmlspecialchars($berita['tajuk']) ?></h3>
            <p class="tarikh"><?= date('d F Y', strtotime($berita['tarikh'])) ?></p>
            <p><?= htmlspecialchars($berita['kandungan']) ?></p>
            <a href="<?= htmlspecialchars($berita['pautan']) ?>" class="baca-lanjut">Baca Lanjut →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ===== Kisah Kejayaan Usahawan ===== -->

<!-- ===== Bahagian Statistik Sistem(bilangan usahawan, jumlah program dianjurkan, jumlah geran disalurkan) ===== -->
 <!-- ===== Statistik Section ===== -->
<!-- ===== Statistik LED Ticker Section ===== -->
<section class="led-ticker">
  <div class="led-track">
    <div class="led-item"><i class="fas fa-users"></i> Usahawan Berdaftar: <span><?= $total_usahawan ?></span></div>
    <div class="led-item"><i class="fas fa-hand-holding-usd"></i> Geran Diagihkan: <span><?= $total_geran_selesai ?></span></div>
    <div class="led-item"><i class="fas fa-coins"></i> Nilai Geran Diagihkan: <span>RM <?= $total_nilai_geran ?></span></div>
    <div class="led-item"><i class="fas fa-chart-line"></i> Jumlah Pelawat: <span><?= $total_pelawat ?></span></div>

    <!-- Duplicate content for smooth infinite loop -->
    <div class="led-item"><i class="fas fa-users"></i> Usahawan Berdaftar: <span><?= $total_usahawan ?></span></div>
    <div class="led-item"><i class="fas fa-hand-holding-usd"></i> Geran Diagihkan: <span><?= $total_geran_selesai ?></span></div>
    <div class="led-item"><i class="fas fa-coins"></i> Nilai Geran Diagihkan: <span>RM <?= $total_nilai_geran ?></span></div>
    <div class="led-item"><i class="fas fa-chart-line"></i> Jumlah Pelawat: <span><?= $total_pelawat ?></span></div>
  </div>
</section>



<!-- ===== Peta lokasi ===== -->

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

  let slideIndex = 0;
  showSlides();

  function showSlides() {
    let slides = document.getElementsByClassName("slides");
    let dots = document.getElementsByClassName("dot");
    for (let i = 0; i < slides.length; i++) { slides[i].style.display = "none"; }
    slideIndex++;
    if (slideIndex > slides.length) { slideIndex = 1 }
    for (let i = 0; i < dots.length; i++) { dots[i].className = dots[i].className.replace(" active", ""); }
    slides[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " active";
    setTimeout(showSlides, 5000);
  }

  function currentSlide(n) {
    slideIndex = n - 1;
    showSlides();
  }
</script>

<script>
let current = 0;
const cards = document.querySelectorAll('.berita-slider .berita-card');
if (cards.length > 0) {
  function showSlide(index) {
    cards.forEach((card, i) => {
      card.classList.remove('active', 'prev', 'next');
      if (i === index) card.classList.add('active');
      else if (i === (index - 1 + cards.length) % cards.length) card.classList.add('prev');
      else if (i === (index + 1) % cards.length) card.classList.add('next');
    });
  }
  showSlide(current);
  setInterval(() => {
    current = (current + 1) % cards.length;
    showSlide(current);
  }, 6000);
}
</script>

</body>
</html>