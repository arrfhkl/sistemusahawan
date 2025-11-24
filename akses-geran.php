<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akses Geran & Pembiayaan - Sistem Usahawan Pahang</title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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


    /* ===== Content Section ===== */
.container {
  max-width: 1200px;
  margin: 60px auto;
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 25px;
}

/* ===== Card Style (Matching Grant Buttons) ===== */
.card {
  background: linear-gradient(145deg, #ffffff, #dfe6f0);
  border-radius: 20px;
  padding: 35px 30px;
  box-shadow:
    6px 6px 18px rgba(0, 0, 0, 0.15),
    -6px -6px 18px rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

/* Metallic shine animation */
.card::before {
  content: "";
  position: absolute;
  top: -120%;
  left: -120%;
  width: 300%;
  height: 300%;
  background: linear-gradient(
    120deg,
    transparent 0%,
    rgba(255, 255, 255, 0.5) 50%,
    transparent 100%
  );
  transform: rotate(25deg);
  animation: cardShine 8s linear infinite;
}

@keyframes cardShine {
  0% { transform: translateX(-100%) rotate(25deg); }
  100% { transform: translateX(100%) rotate(25deg); }
}

/* Hover effect - lift and glow */
.card:hover {
  transform: translateY(-8px);
  box-shadow:
    0 10px 25px rgba(0, 0, 0, 0.2),
    0 0 20px rgba(0, 102, 255, 0.1);
}

/* ===== Typography ===== */
.card h2 {
  font-size: 1.8rem;
  color: #002b5b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 15px;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.15);
}

.card p {
  color: #333;
  font-size: 1rem;
  line-height: 1.7;
  margin-bottom: 15px;
}

/* ===== Grid Buttons ===== */
.grant-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
}

.grant-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  padding: 35px 25px;
  font-weight: 600;
  font-size: 1rem;
  text-align: center;
  color: #fff;
  transition: all 0.35s ease;
  min-height: 150px;
  box-shadow:
    4px 4px 10px rgba(0, 0, 0, 0.15),
    -4px -4px 10px rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.grant-btn::before {
  content: "";
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(120deg, rgba(255,255,255,0.4), transparent);
  transform: rotate(25deg);
  animation: btnShine 6s linear infinite;
  opacity: 0.5;
}

@keyframes btnShine {
  0% { transform: translateX(-100%) rotate(25deg); }
  100% { transform: translateX(100%) rotate(25deg); }
}

.grant-btn i {
  font-size: 2rem;
  background: rgba(255,255,255,0.2);
  padding: 14px;
  border-radius: 50%;
  margin-bottom: 12px;
}

.grant-btn:hover {
  transform: translateY(-6px);
  box-shadow:
    0 12px 20px rgba(0,0,0,0.25),
    0 0 25px rgba(255,255,255,0.15);
}

/* Individual button colors */
.btn-ipush { background: linear-gradient(135deg, #0066ff, #003399); }
.btn-itekad { background: linear-gradient(135deg, #0066ff, #003399); }
.btn-gam { background: linear-gradient(135deg, #0066ff, #003399); }
.btn-cup { background: linear-gradient(135deg, #0066ff, #003399); }
.btn-k3p { background: linear-gradient(135deg, #0066ff, #003399); }



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
  </nav>
</header>

<div class="container">
  <div class="card">
    <h2><i class="fas fa-hand-holding-usd"></i> Akses Geran & Pembiayaan</h2>
    <p>Pilih salah satu geran atau pembiayaan untuk maklumat lanjut:</p>

    <div class="grant-buttons">
      <a href="permohonan-ipush.php" class="grant-btn btn-ipush" onclick="showInfo('I-PUSH: Pinjaman mikro tanpa faedah RM3k–RM10k, moratorium 1 tahun.')">
        <i class="fas fa-sack-dollar"></i>
        I-PUSH
      </a>
      <a href="permohonan-itekad.php" class="grant-btn btn-itekad" onclick="showInfo('Geran i-TEKAD: Untuk B40 & asnaf, sehingga RM5k + latihan intensif.')">
        <i class="fas fa-hands-helping"></i>
        Geran i-TEKAD
      </a>
      <a href="permohonan-agro.php" class="grant-btn btn-gam" onclick="showInfo('Geran Agropreneur Muda (GAM): Hingga RM50k untuk projek pertanian.')">
        <i class="fas fa-seedling"></i>
        Agropreneur Muda
      </a>
      <a href="permohonan-cup.php" class="grant-btn btn-cup" onclick="showInfo('Program CUP & Rise-UP: Geran pemadanan hingga RM70k.')">
        <i class="fas fa-arrow-up"></i>
        CUP & Rise-UP
      </a>
      <a href="permohonan-k3p.php" class="grant-btn btn-k3p" onclick="showInfo('Skim K3P / TPUP: Pinjaman mudah hingga RM5 juta atau RM100k kadar rendah.')">
        <i class="fas fa-landmark"></i>
        Skim K3P / TPUP
      </a>
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
  function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
  }
  function showInfo(text) {
    alert(text);
  }
</script>

</body>
</html>
