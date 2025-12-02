<?php
session_start();

// ===== Sambungan ke Pangkalan Data =====
include "connection.php"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// 🔹 Semak sama ada user sudah login
$is_logged_in = isset($_SESSION['usahawan_id']);
$user_id = $is_logged_in ? $_SESSION['usahawan_id'] : null;

// Dapatkan data usahawan
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$usahawan = $conn->query("SELECT * FROM usahawan WHERE id=$id")->fetch_assoc();

// Dapatkan produk usahawan
$produk = $conn->query("SELECT * FROM produk WHERE usahawan_id=$id");

// Dapatkan semua permohonan ikut IC
$ic = $usahawan['ic'];
$sql_permohonan = "
    SELECT 'Agro' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status 
    FROM permohonan_agro WHERE ic='$ic'
    UNION
    SELECT 'iPush' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status
    FROM permohonan_ipush WHERE ic='$ic'
    UNION
    SELECT 'iTekad' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status
    FROM permohonan_itekad WHERE ic='$ic'
    ORDER BY tarikh_permohonan DESC
";
$permohonan = $conn->query($sql_permohonan);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Usahawan - <?= htmlspecialchars($usahawan['nama']) ?></title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box;}

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
/* ==
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
== */

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


    /* ===== Container & Cards ===== */
    .container { max-width: 1100px; margin: auto; padding: 20px; }
    /* ========== General Card Styling ========== */
.card {
  background: #ffffff;
  border-radius: 20px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  max-width: auto;
  margin: 30px auto;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border: 2px solid #003366;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
}

/* ========== Profile Header ========== */
.profile-header {
  display: flex;
  align-items: center;
  gap: 20px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 20px;
  flex-wrap: wrap;
}

.profile-header img {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #003366;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}
.profile-header img:hover {
  transform: scale(1.05);
}

.profile-header h2 {
  margin: 0;
  font-size: 1.8rem;
  color: #2c3e50;
  font-weight: 700;
}

/* ========== Upload Form ========== */
.upload-form {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}
.upload-form input[type="file"] {
  font-size: 0.9rem;
  background: #f9f9f9;
  padding: 5px 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
.upload-form button {
  background: #003366;
  border: none;
  color: #45a2ffff;
  font-weight: 600;
  border-radius: 6px;
  padding: 6px 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}
.upload-form button:hover {
  background: #001236ff;
  color: #ffffffff;
}

/* ========== Profile Info Section ========== */
.profile-info {
  margin-top: 25px;
}
.profile-info p {
  font-size: 1rem;
  color: #333;
  margin: 10px 0;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  border-bottom: 1px dashed #eee;
  padding-bottom: 8px;
}
.label {
  font-weight: 600;
  color: #555;
}

/* ========== Button Style ========== */
.btn-add {
  display: inline-block;
  background: #003366;
  color: white;
  font-weight: 600;
  border-radius: 8px;
  padding: 10px 20px;
  text-decoration: none;
  margin-top: 25px;
  transition: all 0.3s ease;
}
.btn-add:hover {
  background: #001236ff;
  transform: translateY(-2px);
}

/* ========== Responsive ========== */
@media (max-width: 600px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }
  .profile-info p {
    flex-direction: column;
    align-items: flex-start;
  }
  .btn-add {
    display: block;
    text-align: center;
  }
}

    /* ===== Table Permohonan (Desktop) ===== */
.permohonan-section { 
  margin-top: 40px; 
}
.permohonan-section h2 { 
  color: #003366; 
  margin-bottom: 15px; 
}

.table-permohonan {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}
.table-permohonan th {
  background: #003366;
  color: #fff;
  padding: 12px;
  text-align: left;
  font-size: 0.95rem;
}
.table-permohonan td {
  padding: 12px;
  border-bottom: 1px solid #eee;
  font-size: 0.95rem;
  color: #333;
}
.table-permohonan tr:hover td { 
  background: #f9f9f9; 
}
.table-permohonan a { 
  color: #2196F3; 
  font-weight: 500; 
  text-decoration: none; 
}
.table-permohonan a:hover { 
  text-decoration: underline; 
}

/* ===== Wrapper untuk scroll bila skrin sempit ===== */
.permohonan-wrapper {
  width: 100%;
  overflow-x: auto;
}

/* ===== Responsive Stacked Card Style ===== */
@media (max-width: 768px) {
  .table-permohonan,
  .table-permohonan thead,
  .table-permohonan tbody,
  .table-permohonan th,
  .table-permohonan td,
  .table-permohonan tr {
    display: block;
    width: 100%;
  }

  .table-permohonan thead {
    display: none; /* sembunyikan header */
  }

  .table-permohonan tr {
    margin-bottom: 15px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    padding: 12px;
  }

  .table-permohonan td {
    border: none;
    display: flex;
    justify-content: space-between;
    padding: 8px 6px;
    font-size: 0.9rem;
    border-bottom: 1px solid #f0f0f0;
  }

  .table-permohonan td:last-child {
    border-bottom: none;
  }

  .table-permohonan td::before {
    content: attr(data-label);
    font-weight: bold;
    color: #003366;
    flex-basis: 40%;
    text-align: left;
  }
}

@media (max-width: 480px) {
  .table-permohonan td {
    font-size: 0.85rem;
    padding: 6px;
  }
}

@media (max-width: 768px) {
  .table-permohonan thead {
    display: none !important;   /* sembunyikan semua thead */
    visibility: hidden;
    height: 0;
  }

  .table-permohonan tr th {
    display: none !important;   /* pastikan semua th tak muncul */
  }
}

  .produk-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr);  /* 1/3 layout tetap */
    gap: 20px; 
  }

    /* ===== Produk Section ===== */
    .produk-section { margin-top: 40px; }
    .produk-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
    .produk-card img { width: 100%; height: 180px; object-fit: cover; }
    .produk-card-content { padding: 15px; }
    .produk-card h3 { margin: 0; font-size: 1.2rem; color: #333; }
    .produk-card p { margin: 6px 0; font-size: 0.95rem; color: #555; }
    .produk-card .harga { font-weight: bold; color: #000; margin-top: 10px; }
    .produk-actions { margin-top: 10px; display: flex; gap: 10px; }
    .produk-actions a {
      flex: 1; text-align: center; padding: 8px; border-radius: 6px;
      font-size: 0.9rem; font-weight: bold; text-decoration: none;
    }
    .btn-edit { background: #2196F3; color: white; }
    .btn-edit:hover { background: #0b7dda; }
    .btn-delete { background: #f44336; color: white; }
    .btn-delete:hover { background: #c62828; }

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

@media (max-width: 992px) {
  .produk-grid {
    grid-template-columns: repeat(2, 1fr); /* Tablet = 2 kolum */
  }
}

@media (max-width: 576px) {
  .produk-grid {
    grid-template-columns: repeat(1, 1fr); /* Phone = 1 kolum */
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
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="logout.php"><strong>Log Keluar</strong></a>
  </nav>
</header>

<div class="container">
  <div class="card">
    <div class="profile-header">
        <?php if (!empty($usahawan['avatar'])): ?>
        <img src="<?= htmlspecialchars($usahawan['avatar']) ?>" alt="Avatar">
        <?php else: ?>
        <img src="https://via.placeholder.com/100" alt="Avatar">
        <?php endif; ?>

        <div>
          <h2><?= htmlspecialchars($usahawan['nama']) ?></h2>
          <form class="upload-form" action="upload_avatar.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?= $usahawan['id'] ?>">
              <input type="file" name="avatar" accept="image/*" required>
              <button type="submit">Update Gambar</button>
          </form>
        </div>
    </div>

    <div class="profile-info">
      <p><span class="label">Alamat:</span> <?= htmlspecialchars($usahawan['alamat']) ?></p>
      <p><span class="label">No KP:</span> <?= htmlspecialchars($usahawan['ic']) ?></p>
      <p><span class="label">No. Telefon:</span> <?= htmlspecialchars($usahawan['telefon']) ?></p>
      <p><span class="label">Jenis Perniagaan:</span> <?= htmlspecialchars($usahawan['jenis']) ?></p>
      <p><span class="label">Nama Perniagaan:</span> <?= htmlspecialchars($usahawan['perniagaan']) ?></p>
      <p><span class="label">Tarikh Daftar:</span> <?= htmlspecialchars($usahawan['tarikh_daftar']) ?></p>
    </div>

    <a class="btn-add" href="tambah_produk.php?id=<?= $usahawan['id'] ?>">+ Tambah Produk</a>
    <a class="btn-add" href="pesanan_detail.php?id=<?= $usahawan['id'] ?>">Pesanan Saya</a>
    <a class="btn-add" href="pesanan_masuk.php?id=<?= $usahawan['id'] ?>">Pesanan Masuk</a>
  </div>

  <!-- ===== Permohonan Section ===== -->
<div class="permohonan-section">
  <h2>Senarai Permohonan</h2>

  <?php if ($permohonan->num_rows > 0): ?>
    <div class="permohonan-wrapper">
      <table class="table-permohonan">
        <tr>
          <th>Program</th>
          <th>Kategori</th>
          <th>Jumlah (RM)</th>
          <th>Tujuan</th>
          <th>Tarikh Permohonan</th>
          <th>Status</th>
        </tr>
        <?php while($row = $permohonan->fetch_assoc()): ?>
          <tr>
  <td data-label="Program"><?= htmlspecialchars($row['program']) ?></td>
  <td data-label="Kategori"><?= htmlspecialchars($row['kategori']) ?></td>
  <td data-label="Jumlah (RM)"><?= number_format($row['jumlah'], 2) ?></td>
  <td data-label="Tujuan"><?= htmlspecialchars($row['tujuan']) ?></td>
  <td data-label="Tarikh Permohonan"><?= htmlspecialchars($row['tarikh_permohonan']) ?></td>
  <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
</tr>

        <?php endwhile; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Tiada rekod permohonan.</p>
  <?php endif; ?>
</div>


  <!-- ===== Produk Section ===== -->
  <div class="produk-section">
    <h2>Produk</h2>
    <div class="produk-grid">
      <?php foreach ($produk as $p): ?>
        <div class="produk-card">
          <?php
$gambarPath = $p['gambar_url'];
if (strpos($gambarPath, 'uploads/') === false) {
    $gambarPath = 'uploads/' . $gambarPath;
}
?>
<img src="<?= htmlspecialchars($gambarPath) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">

          <div class="produk-card-content">
            <h3><?= htmlspecialchars($p['nama']) ?></h3>
            <p><?= htmlspecialchars($p['deskripsi']) ?></p>
            <p class="harga"><?= htmlspecialchars($p['harga']) ?></p>
            <div class="produk-actions">
              <a class="btn-edit" href="edit_produk.php?id=<?= $p['id'] ?>">Edit</a>
              <a class="btn-delete" href="delete_produk.php?id=<?= $p['id'] ?>&usahawan_id=<?= $usahawan['id'] ?>" onclick="return confirm('Padam produk ini?')">Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
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
  document.getElementById("navMenu").classList.toggle("show");
}
</script>

</body>
</html>
