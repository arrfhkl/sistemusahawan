<?php
// ===== Sambungan ke Pangkkalan Data =====
include "connection.php";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Sambungan gagal: " . $conn->connect_error); 
}

// ===== Dapatkan ID servis =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Ralat: Servis tidak dijumpai.");
}

// ===== Dapatkan maklumat servis =====
$sql = "SELECT * FROM servis WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Ralat: Servis tidak dijumpai.");
}
$servis = $result->fetch_assoc();

// ===== Proses kemaskini data =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama       = $conn->real_escape_string($_POST['nama']);
    $deskripsi  = $conn->real_escape_string($_POST['deskripsi']);
    $lokasi     = $conn->real_escape_string($_POST['lokasi']);
    $harga      = $conn->real_escape_string($_POST['harga']);

    // Kekalkan gambar lama jika tiada upload baru
    $gambar_baru = $servis['gambar_servis_url'];

    // ===== Jika gambar baharu dimuat naik =====
    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Hanya benarkan imej
        $allowTypes = ['jpg','jpeg','png','gif','webp'];
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
                $gambar_baru = $fileName;
            }
        }
    }

    // ===== Kemaskini servis =====
    $update = $conn->query("
        UPDATE servis 
        SET 
            nama='$nama',
            deskripsi='$deskripsi',
            lokasi='$lokasi',
            harga='$harga',
            gambar_servis_url='$gambar_baru'
        WHERE id=$id
    ");

    if ($update) {
        echo "<script>
            alert('✅ Servis berjaya dikemaskini!');
            window.location.href='profil_usahawan.php?id=" . $servis['usahawan_id'] . "';
        </script>";
        exit;
    } else {
        echo "<script>alert('❌ Ralat semasa mengemaskini servis!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Servis - Sistem Usahawan Pahang</title>
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
      max-width: 600px; 
      margin: 50px auto; 
      background: #fff; 
      border-radius: 15px;
      padding: 30px; 
      box-shadow: 0 6px 25px rgba(0,0,0,0.1); 
      border: 2px solid #003366; 
      position: relative;
    }

    h2 { 
      text-align: center; 
      color: #003366; 
      margin-bottom: 20px; 
    }

    label {
      font-weight: 600;
      margin-top: 15px;
      display: block;
    }

    input, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    button {
      background: #003366;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      margin-top: 25px;
      font-weight: bold;
      width: 100%;
      cursor: pointer;
    }

    button:hover {
      background: #001236;
    }

    .preview img {
      width: 200px;
      border-radius: 10px;
      border: 2px solid #003366;
      margin-top: 10px;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      text-decoration: none;
      font-weight: 600;
      color: #003366;
      border: 2px solid #003366;
      padding: 6px 14px;
      border-radius: 20px;
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

  <a href="profil_usahawan.php?id=<?= $servis['usahawan_id'] ?>" class="back-btn">← Kembali</a>

  <h2>Edit Servis</h2>

  <form method="post" enctype="multipart/form-data">

    <label>Nama Servis</label>
    <input type="text" name="nama" value="<?= htmlspecialchars($servis['nama']) ?>" required>

    <label>Deskripsi</label>
    <textarea name="deskripsi" rows="4" required><?= htmlspecialchars($servis['deskripsi']) ?></textarea>

    <label>Lokasi</label>
    <input type="text" name="lokasi" value="<?= htmlspecialchars($servis['lokasi']) ?>" required>

    <label>Harga (RM)</label>
    <input type="number" step="0.01" name="harga" value="<?= htmlspecialchars($servis['harga']) ?>" required>

    <label>Tukar Gambar Servis</label>
    <input type="file" name="gambar" accept="image/*" onchange="previewImage(event)">

    <!-- Preview gambar baru -->
    <div class="preview" id="previewWrap" style="display:none;">
      <p>Preview gambar baharu:</p>
      <img id="preview">
    </div>

    <!-- Gambar sedia ada -->
    <div class="preview">
      <p>Gambar sedia ada:</p>
      <?php
        $gambarPath = $servis['gambar_servis_url'];
        if (!empty($gambarPath) && strpos($gambarPath, 'uploads/') === false) {
            $gambarPath = 'uploads/' . $gambarPath;
        }
      ?>
      <img src="<?= htmlspecialchars($gambarPath) ?>">
    </div>

    <button type="submit">Kemaskini Servis</button>
  </form>
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

  function previewImage(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('preview').src = e.target.result;
    document.getElementById('previewWrap').style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>

</body>
</html>
