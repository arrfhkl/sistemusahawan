<?php
session_start();

// ===== Semak login (jika sistem guna login usahawan) =====
if (!isset($_SESSION['usahawan_id'])) {
  // Jika belum login, boleh redirect atau biar open untuk awam
  // header("Location: komuniti_login.php");
  // exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Latihan & Panduan - Sistem Usahawan Pahang</title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <style>
  /* =====================================================
     🌟 GAYA GLOBAL DARI DESIGN ANDA
  ===================================================== */
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
    background-attachment: fixed;
    color: #111;
    overflow-x: hidden;
    position: relative;
    margin-top: 90px;
  }

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

  body::after {
    content: "";
    position: fixed;
    inset: 0;
    background-color: transparent;
    background-image: url("assets/img/jatapahang.png");
    background-repeat: repeat;
    background-size: 180px 180px;
    background-position: center;
    opacity: 0.15;
    filter: grayscale(5%) brightness(1.3) contrast(1.1);
    animation: watermarkFloat 40s linear infinite;
    z-index: -2;
  }

  @keyframes watermarkFloat {
    0% { background-position: 0 0; opacity: 0.14; }
    50% { background-position: 80px 60px; opacity: 0.18; }
    100% { background-position: 0 0; opacity: 0.14; }
  }

  @keyframes royalWave {
    0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
    100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
  }

  .card {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 215, 0, 0.4);
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    padding: 25px;
    margin-bottom: 30px;
    backdrop-filter: blur(8px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12); }

  /* ===== Header ===== */
  header {
    background: linear-gradient(
      135deg,
      #001F3F 0%, #003399 15%, #0066FF 40%, #99CCFF 60%, #003399 80%, #001F3F 100%
    );
    animation: metalshine 6s linear infinite;
    padding: 15px 20px;
    position: fixed; top: 0; left: 0; width: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  header img.jata { height: 55px; }
  .menu-toggle {
    display: none;
    font-size: 1.8rem;
    cursor: pointer;
    background: none;
    border: none;
    color: #fff;
  }

  .title {
    position: relative;
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    text-align: center;
    background: linear-gradient(90deg, #e6e6e6 0%, #bfbfbf 50%, #f2f2f2 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow:
      0 1px 0 #b3b3b3,
      0 2px 0 #999,
      0 3px 0 #777,
      0 4px 0 #555,
      0 5px 8px rgba(0,0,0,0.6);
  }

  .title::after {
    content: "";
    position: absolute;
    top: 0; left: -75%;
    width: 50%; height: 100%;
    background: linear-gradient(120deg,
      rgba(255,255,255,0) 0%,
      rgba(255,255,255,0.6) 50%,
      rgba(255,255,255,0) 100%);
    animation: textshine 4s linear infinite;
  }

  @keyframes textshine {
    0% { left: -75%; }
    100% { left: 125%; }
  }

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

  /* ===== Footer ===== */
  footer {
    background: linear-gradient(
      135deg,
      #001F3F 0%, #003399 15%, #0066FF 40%, #99CCFF 60%, #003399 80%, #001F3F 100%
    );
    animation: metalshine 6s linear infinite;
    color: #fff;
    padding: 30px 20px;
    margin-top: 50px;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
  }

  footer img { height: 60px; margin-bottom: 15px; filter: drop-shadow(0 3px 5px rgba(0,0,0,0.4)); }

  footer p, footer strong, footer .copyright {
    text-shadow: 0 2px 4px rgba(0,0,0,0.6);
  }

  footer .copyright {
    margin-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.3);
    padding-top: 8px;
    font-size: 0.85rem;
  }

  @keyframes metalshine {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  /* ===== Responsive ===== */
  @media (max-width: 768px) {
    .menu-toggle { display: block; }
    nav { display: none; flex-direction: column; width: 100%; text-align: center; }
    nav.show { display: flex; }
    nav a { padding: 10px; }
  }

  /* ===== Kandungan Latihan ===== */
  .container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 20px;
  }

  h2 {
    color: #003399;
    border-bottom: 3px solid #e0e0e0;
    margin-bottom: 20px;
    padding-bottom: 8px;
  }

  .video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
  }

  iframe {
    width: 100%;
    height: 220px;
    border-radius: 10px;
    border: none;
  }

  .download-section a {
    display: inline-block;
    background: #003399;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
  }
  .download-section a:hover { background: #002266; }

  .faq {
    background: #f9f9f9;
    border-left: 5px solid #003399;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 6px;
  }

  ul { line-height: 1.8; }
  </style>
</head>
<body>

<!-- ===== Navbar ===== -->
<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="latihan_panduan.php" class="active"><strong>Latihan & Panduan</strong></a>
  </nav>
</header>

<!-- ===== Kandungan Utama ===== -->
<div class="container">
  <div class="card">
    <h2>🎥 Video Panduan Penggunaan</h2>
    <p>Tonton video berikut untuk memahami cara menggunakan sistem ini dengan mudah.</p>
    <div class="video-grid">
      <iframe src="https://www.youtube.com/embed/xxxxxx" title="Panduan Daftar Akaun"></iframe>
      <iframe src="https://www.youtube.com/embed/yyyyyy" title="Panduan Kemas Kini Profil"></iframe>
    </div>
  </div>

  <div class="card">
    <h2>📘 Manual & Panduan Muat Turun</h2>
    <p>Muat turun panduan rasmi Sistem Usahawan Pahang untuk rujukan luar talian.</p>
    <div class="download-section">
      <a href="manual/sistem_usahawan_pahang.pdf" download>📄 Muat Turun PDF Panduan</a>
    </div>
  </div>

  <div class="card">
    <h2>❓ Soalan Lazim (FAQ)</h2>
    <div class="faq"><b>1. Bagaimana cara mendaftar sebagai usahawan?</b><br> Klik menu "Daftar Usahawan" dan isi maklumat lengkap anda.</div>
    <div class="faq"><b>2. Saya terlupa kata laluan. Apa perlu saya buat?</b><br> Klik "Lupa Kata Laluan" dan ikut langkah penetapan semula.</div>
    <div class="faq"><b>3. Siapa yang layak menggunakan sistem ini?</b><br> Semua usahawan berdaftar di bawah Kerajaan Negeri Pahang layak menggunakan sistem ini.</div>
  </div>

  <div class="card">
    <h2>📞 Bantuan & Sokongan</h2>
    <p>Jika anda menghadapi sebarang masalah atau pertanyaan, sila hubungi kami:</p>
    <ul>
      <li>📧 Emel: <a href="mailto:sokongan@usahawanpahang.gov.my">sokongan@usahawanpahang.gov.my</a></li>
      <li>📞 Telefon: 09-123 4567</li>
      <li>🏢 Alamat: Pejabat Usahawan Negeri Pahang, Kompleks SUK, Kuantan</li>
    </ul>
  </div>
</div>

<!-- ===== Footer ===== -->
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
