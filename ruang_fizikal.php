<?php
// ===== Sambungan Database =====
include "connection.php";
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// ===== Proses Tempahan =====
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nama_ruang     = $_POST['nama_ruang'];
  $nama_pemohon   = $_POST['nama_pemohon'];
  $no_ic          = $_POST['no_ic'];
  $telefon        = $_POST['telefon'];
  $tarikh_tempah  = $_POST['tarikh_tempah'];

  // 🔍 Semak jika ruang ini sudah ditempah pada tarikh sama
  $check = $conn->prepare("SELECT * FROM tempahan_ruang WHERE nama_ruang = ? AND tarikh_tempah = ?");
  $check->bind_param("ss", $nama_ruang, $tarikh_tempah);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    // ❌ Ruang sudah ditempah
    echo "<script>alert('Maaf, ruang \"$nama_ruang\" telah ditempah pada tarikh $tarikh_tempah. Sila pilih tarikh lain.');</script>";
  } else {
    // ✅ Teruskan tempahan
    $sql = $conn->prepare("INSERT INTO tempahan_ruang (nama_ruang, nama_pemohon, no_ic, telefon, tarikh_tempah) VALUES (?, ?, ?, ?, ?)");
    $sql->bind_param("sssss", $nama_ruang, $nama_pemohon, $no_ic, $telefon, $tarikh_tempah);

    if ($sql->execute()) {
      echo "<script>alert('Tempahan berjaya dihantar!');</script>";
    } else {
      echo "<script>alert('Ralat semasa menghantar tempahan: " . $conn->error . "');</script>";
    }
  }
}

// ===== Ambil Data Ruang =====
$result = $conn->query("SELECT * FROM ruang_fizikal");
?>


<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tempahan Ruang - Sistem Usahawan Pahang</title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">

  <!-- ===== Gaya Premium Kerajaan ===== -->
  <style>
  <?php include("style_tempahan.css"); ?>
  </style>

  <!-- ===== Gaya Dalaman untuk Tempahan Ruang ===== -->
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




    .container {
      width: 90%;
      max-width: 1100px;
      margin: 50px auto;
      z-index: 10;
      position: relative;
    }

    .container h2 {
      color: #001F3F;
      margin-bottom: 20px;
      font-size: 1.8rem;
      text-align: center;
      text-shadow: 0 1px 0 #ccc, 0 2px 0 #999;
    }

    .ruang-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    .ruang-card {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      border: 1px solid rgba(255,215,0,0.4);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.2s ease;
    }

    .ruang-card:hover {
      transform: translateY(-5px);
    }

    .ruang-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-bottom: 1px solid #ccc;
    }

    .ruang-card .content {
      padding: 15px;
    }

    .ruang-card h3 {
      color: #003399;
      font-size: 18px;
      margin-bottom: 8px;
    }

    .ruang-card p {
      margin-bottom: 6px;
      font-size: 14px;
      color: #333;
    }

    .ruang-card button {
      background: #003399;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.3s;
    }

    .ruang-card button:hover {
      background: #001F3F;
    }

    /* ===== Popup Form ===== */
    .popup {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      display: none;
      justify-content: center;
      align-items: center;
      background: rgba(0,0,0,0.6);
      z-index: 999;
    }

    .popup-content {
      background: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 450px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      position: relative;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: scale(0.9);}
      to {opacity: 1; transform: scale(1);}
    }

    .popup-content h2 {
      margin-bottom: 15px;
      color: #003399;
      text-align: center;
    }

    .popup-content label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #111;
    }

    .popup-content input {
      width: 100%;
      padding: 8px;
      margin-bottom: 12px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .popup-content button[type="submit"] {
      width: 100%;
      background: #003399;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }

    .popup-content button[type="submit"]:hover {
      background: #001F3F;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 15px;
      background: none;
      border: none;
      font-size: 22px;
      cursor: pointer;
      color: #555;
    }

    .close-btn:hover {
      color: red;
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
  </style>
</head>
<body>

  <!-- ===== Header Rasmi Kerajaan ===== -->
  <header>
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
    <h1 class="title">Sistem Usahawan Pahang</h1>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    <nav id="navMenu">
      <a href="index.php"><strong>Laman Utama</strong></a>
      <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
      <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
      <a href="tempahan_ruang.php" class="active"><strong>Tempahan Ruang</strong></a>
    </nav>
  </header>

  <!-- ===== Kandungan Utama ===== -->
  <div class="container">
    <h2  style="margin-top: 10%;">Senarai Ruang Tersedia untuk Tempahan</h2>
    <div class="ruang-list">
      <?php while ($row = $result->fetch_assoc()) { ?>
        <div class="ruang-card">
          <img src="uploads/<?php echo $row['gambar']; ?>" alt="<?php echo $row['nama_ruang']; ?>">
          <div class="content">
            <h3><?php echo $row['nama_ruang']; ?></h3>
            <p><strong>Lokasi:</strong> <?php echo $row['lokasi']; ?></p>
            <p><strong>Kadar Sewa:</strong> RM <?php echo $row['kadar_sewa']; ?></p>
            <p><strong>Deskripsi:</strong> <?php echo $row['kemudahan']; ?></p>
            <button onclick="bukaPopup('<?php echo $row['nama_ruang']; ?>')">Buat Tempahan</button>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>

  <!-- ===== Popup Form Tempahan ===== -->
  <div class="popup" id="popupForm">
    <div class="popup-content">
      <button class="close-btn" onclick="tutupPopup()">&times;</button>
      <h2>Borang Tempahan Ruang</h2>
      <form method="POST">
        <label>Nama Ruang</label>
        <input type="text" id="nama_ruang" name="nama_ruang" readonly>

        <label>Nama Pemohon</label>
        <input type="text" name="nama_pemohon" required>

        <label>No. Kad Pengenalan</label>
        <input type="text" name="no_ic" required>

        <label>No. Telefon</label>
        <input type="text" name="telefon" required>

        <label>Tarikh Tempahan</label>
        <input type="date" id="tarikh_tempah" name="tarikh_tempah" required>

        <button type="submit">Hantar Tempahan</button>
      </form>
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

  <!-- ===== JavaScript ===== -->
  <script>

    function bukaPopup(namaRuang) {
      document.getElementById('popupForm').style.display = 'flex';
      document.getElementById('nama_ruang').value = namaRuang;

      // Set tarikh minimum 1 hari dari sekarang ikut waktu KL
      const sekarang = new Date();
      const utc = sekarang.getTime() + sekarang.getTimezoneOffset() * 60000;
      const klDate = new Date(utc + 8*60*60*1000); // UTC+8
      klDate.setDate(klDate.getDate() + 1);

      const yyyy = klDate.getFullYear();
      const mm = String(klDate.getMonth() + 1).padStart(2, '0');
      const dd = String(klDate.getDate()).padStart(2, '0');
      const tarikhMin = `${yyyy}-${mm}-${dd}`;

      const tarikhInput = document.getElementById('tarikh_tempah');
      tarikhInput.min = tarikhMin;
      tarikhInput.value = tarikhMin; // default value 1 hari ke depan
    }
  

    function tutupPopup() {
      document.getElementById('popupForm').style.display = 'none';
    }

    function toggleMenu() {
      document.getElementById("navMenu").classList.toggle("show");
    }
  </script>

</body>
</html>
