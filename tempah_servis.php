<?php
session_start();
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

/* ✅ BETULKAN SESSION */
if (!isset($_SESSION['usahawan_id'])) {
  echo "<script>
    alert('Sila login dahulu untuk menempah servis');
    window.location='login.php';
  </script>";
  exit;
}

$user_id = $_SESSION['usahawan_id'];
$service_id = (int)$_GET['id'];

/* ✅ DATA USER */
$user = $conn->query("
  SELECT nama, telefon, email, alamat 
  FROM usahawan 
  WHERE id = $user_id
")->fetch_assoc();

if (!$user) die("Maklumat pengguna tidak ditemui.");

/* ✅ DATA SERVIS */
$servis = $conn->query("
  SELECT s.*, u.nama AS nama_usahawan
  FROM servis s
  JOIN usahawan u ON s.usahawan_id = u.id
  WHERE s.id = $service_id
")->fetch_assoc();

if (!$servis) die("Servis tidak ditemui.");

/*  PROSES TEMPAHAN */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  $nama    = $conn->real_escape_string($_POST['nama_pelanggan']);
  $telefon = $conn->real_escape_string($_POST['telefon']);
  $alamat  = $conn->real_escape_string($_POST['alamat']);
  $tarikh  = $_POST['tarikh'];
  $masa    = $conn->real_escape_string($_POST['masa']);
  $masalah = $conn->real_escape_string($_POST['masalah']);

  /* ✅ UPLOAD GAMBAR */
  $imej = NULL;

  if (!empty($_FILES['imej']['name'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = time() . "_" . basename($_FILES["imej"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowTypes = ['jpg','jpeg','png','webp'];
    if (in_array($imageFileType, $allowTypes)) {
      if (move_uploaded_file($_FILES["imej"]["tmp_name"], $target_file)) {
        $imej = $file_name;
      }
    }
  }

  /* ✅ INSERT KE SERVIS_BOOKING */
  $stmt = $conn->prepare("
    INSERT INTO servis_booking
    (service_id, usahawan_id, nama_pelanggan, telefon, alamat, tarikh, masa, masalah, imej, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
  ");

  $stmt->bind_param(
    "iisssssss",
    $service_id,
    $servis['usahawan_id'],
    $nama,
    $telefon,
    $alamat,
    $tarikh,
    $masa,
    $masalah,
    $imej
  );

  if ($stmt->execute()) {
    echo "<script>
      alert('✅ Tempahan berjaya dihantar!');
      window.location='senarai_servis.php';
    </script>";
    exit;
  } else {
    echo "<script>alert('❌ Gagal menghantar tempahan');</script>";
  }
}


?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tempah Servis - Sistem Usahawan Pahang</title>
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
  margin-bottom: auto;
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

body { font-family: Arial; background: #f2f2f2; margin: 0; }
.container {
  max-width: 700px; background: white; margin: 30px auto;
  padding: 25px; border-radius: 12px;
  box-shadow: 0 0 15px rgba(0,0,0,.15);
}
label { font-weight: bold; display: block; margin-top: 10px; }
input, textarea {
  width: 100%; padding: 10px; margin-top: 5px;
  border-radius: 6px; border: 1px solid #ccc;
}
button {
  width: 100%; margin-top: 15px; padding: 14px;
  border-radius: 8px; border: none;
  background: #007bff; color: white;
  font-weight: bold;
}
.map-box { height: 300px; background: #ddd; margin-top: 10px; border-radius: 10px; }

.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,.5);
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  padding: 25px;
  border-radius: 14px;
  width: 90%;
  max-width: 450px;
  box-shadow: 0 5px 20px rgba(0,0,0,.3);
  animation: fadeIn .3s ease;
}

@keyframes fadeIn {
  from { transform: scale(.8); opacity:0 }
  to { transform: scale(1); opacity:1 }
}

.modal-buttons {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
}

.btn-cancel, .btn-confirm {
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
}

.btn-cancel { background: #dc3545; color:white; }
.btn-confirm { background: #28a745; color:white; }


</style>
</head>

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

<br><br><br><br>

<body>

<div class="container">
<h2> Tempahan <?= htmlspecialchars($servis['nama']) ?> </h2>

<form method="POST" enctype="multipart/form-data">

<img src="uploads/<?= htmlspecialchars($servis['gambar_servis_url']) ?>" 
style="width:100%; max-height:250px; object-fit:cover; border-radius:12px; margin-bottom:15px;">

<label>Nama *</label>
<input type="text" name="nama_pelanggan" value="<?= htmlspecialchars($user['nama']) ?>" required>

<label>No Telefon *</label>
<input type="text" name="telefon" value="<?= htmlspecialchars($user['telefon']) ?>" required>

<label>Email</label>
<input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>

<label>Tarikh *</label>
<input type="date" name="tarikh" id="tarikh" required>

<label>Masa *</label>
<input type="time" name="masa" value="12:00" required>

<label>Masalah *</label>
<textarea name="masalah" rows="4" required></textarea>

<label for="imej">Muat Naik Masalah Imej (Optional):</label>
<input type="file" id="imej" name="imej" accept="image/*" required onchange="previewImage(event)">
<img id="preview" style="display:none; margin-top:15px; max-width:200px; border-radius:10px;">

<label>Alamat *</label>
<input type="text" id="alamat" name="alamat" value="<?= htmlspecialchars($user['alamat']) ?>" required>

<button type="button" onclick="getLocation()">📍 Guna Lokasi Semasa</button>
<div id="map" class="map-box"></div>

<button type="button" onclick="showConfirmation()">HANTAR TEMPAHAN</button>

</form>
</div>

<!-- ===================== MODAL CONFIRMATION ===================== -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <h3>Semak Tempahan Anda</h3>

    <div id="previewDetails"></div>

    <div class="modal-buttons">
      <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
      <button type="button" class="btn-confirm" onclick="submitForm()">Sahkan Tempahan</button>
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

const today = new Date().toISOString().split('T')[0];
document.getElementById("tarikh").setAttribute("min", today);

function getLocation(){
  if(!navigator.geolocation) return alert("GPS tidak disokong");

  navigator.geolocation.getCurrentPosition(function(pos){
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    document.getElementById("alamat").value = `Lat: ${lat}, Lng: ${lng}`;

    document.getElementById("map").innerHTML = `
      <iframe width="100%" height="300"
        src="https://www.google.com/maps?q=${lat},${lng}&output=embed">
      </iframe>`;
  });
}

 //preview gambar upload
    function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
  }

function showConfirmation() {
  // Ambil value dari form
  const nama = document.querySelector("[name='nama_pelanggan']").value;
  const telefon = document.querySelector("[name='telefon']").value;
  const alamat = document.querySelector("[name='alamat']").value;
  const tarikh = document.querySelector("[name='tarikh']").value;
  const masa = document.querySelector("[name='masa']").value;
  const masalah = document.querySelector("[name='masalah']").value;

  // Tukar masa ke AM/PM
  const masaFormatted = new Date("2000-01-01 " + masa).toLocaleTimeString('en-US', {
    hour: 'numeric', minute: 'numeric', hour12: true
  });

  // PREVIEW ORDER DALAM MODAL
  document.getElementById("previewDetails").innerHTML = `
    <p><strong>Nama:</strong> ${nama}</p>
    <p><strong>Telefon:</strong> ${telefon}</p>
    <p><strong>Alamat:</strong> ${alamat}</p>
    <p><strong>Tarikh:</strong> ${tarikh}</p>
    <p><strong>Masa:</strong> ${masaFormatted}</p>
    <p><strong>Masalah:</strong><br>${masalah}</p>
  `;

  // BUKA MODAL
  document.getElementById("confirmModal").style.display = "flex";
}

function closeModal(){
  document.getElementById("confirmModal").style.display = "none";
}

function submitForm(){
  document.querySelector("form").submit();
}
</script>

</body>
</html>

<?php $conn->close(); ?>
