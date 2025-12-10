<?php
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");
if ($conn->connect_error) die("DB Error");

$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;

// =======================
// ✅ PART 1: AJAX REQUEST
// =======================
if (isset($_GET['ajax'])) {

  $search  = $_GET['search'] ?? '';
  $lokasi  = $_GET['lokasi'] ?? '';
  $kategori_id = (int)($_GET['kategori_id'] ?? 0);

  // ✅ Jika kategori kosong → hentikan
  if ($kategori_id == 0) {
    echo "<p style='grid-column:1/-1;text-align:center;'>Kategori tidak sah.</p>";
    exit;
  }

  $sql = "SELECT * FROM servis WHERE kategori_servis_id = ?";
  $params = [$kategori_id];
  $types = "i";

  if (!empty($search)) {
    $sql .= " AND nama LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
  }

  if (!empty($lokasi)) {
    $sql .= " AND lokasi = ?";
    $params[] = $lokasi;
    $types .= "s";
  }

  $sql .= " ORDER BY id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
?>

  <!-- ✅ INI YANG SEBELUM INI HILANG -->
  <div class="service-card">
    <img src="uploads/<?= htmlspecialchars($row['gambar_servis_url']) ?>">
    <div class="service-info">
      <h3><?= htmlspecialchars($row['nama']) ?></h3>
      <p>📍 <?= htmlspecialchars($row['lokasi']) ?></p>

      <button class="btn-butiran"
        onclick="window.location.href='butiran_servis.php?id=<?= $row['id'] ?>'">
        Butiran Servis
      </button>
    </div>
  </div>

<?php
    endwhile;
  else:
    echo "<p style='grid-column:1/-1; text-align:center;'>Tiada servis ditemui.</p>";
  endif;

  exit;
}

// =======================
//  PART 2: PAGE BIASA
// =======================
$result_lokasi = $conn->query("
  SELECT DISTINCT lokasi 
  FROM servis 
  WHERE kategori_servis_id = $kategori_id 
  ORDER BY lokasi ASC
");

?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Senarai Servis</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
  min-height: 100vh;
  display: flex;
  flex-direction: column;

  font-family: Arial;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  padding-top: 90px; /* Jarak untuk header fixed */
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

.search-wrapper input, .search-wrapper select {
  padding: 12px;
  border-radius: 20px;
  border: 1px solid #ccc;
  width: 100%;
}
.service-container {
  max-width: 1200px;
  margin: auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
  padding: 20px;
}
.service-card {
  background:#fff;
  border-radius: 12px;
  overflow:hidden;
  box-shadow:0 4px 10px rgba(0,0,0,.1);
}
.service-card img {
  width:100%;
  height:200px;
  object-fit:cover;
}
.service-info {
  padding: 15px;
}
.btn-butiran {
  width: 100%;
  margin-top: 10px;
  padding: 10px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.btn-butiran:hover { background:#0056b3; }

@media(max-width:768px){
  .service-container { grid-template-columns: repeat(2,1fr); }
}
@media(max-width:480px){
  .service-container { grid-template-columns: repeat(1,1fr); }
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
  position: relative;
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
  position: absolute;
  top: 12px;
  right: 12px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #dc3545; /* Merah */
  border: none;
  color: #fff;
  font-size: 22px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  transition: 0.25s ease;
  z-index: 10;
}

.modal-close:hover {
  background: #b02a37;
  transform: scale(1.1);
}

.main-content {
  flex: 1;  
}

/*supaya responsive*/
@media (max-width: 992px) {
  .produk-container {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .produk-container {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .produk-container {
    grid-template-columns: repeat(1, 1fr);
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

</div>

  </nav>

  
</header>

<main class="main-content">

 <!-- ===== SEARCH BAR ===== -->
<div class="search-wrapper">
  <div style="display:flex; gap:10px; align-items:center;">

    <!-- SEARCH -->
    <div class="search-box" style="flex:2;">
      <span class="search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari servis..." autocomplete="off">
    </div>

    <!-- FILTER LOKASI -->
    <select id="lokasiFilter" style="
      flex:1;
      padding:12px;
      border-radius:25px;
      border:1px solid #ccc;
      font-size:14px;
      outline:none;
      height:45px;
    ">
      <option value="">📍 Semua Lokasi</option>

      <?php if ($result_lokasi && $result_lokasi->num_rows > 0): ?>
        <?php while ($lok = $result_lokasi->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($lok['lokasi']) ?>">
            <?= htmlspecialchars($lok['lokasi']) ?>
          </option>
        <?php endwhile; ?>
      <?php endif; ?>
    </select>

  </div>
</div>

<!-- ✅ SERVICE LIST -->
<div class="service-container" id="serviceContainer"></div>

</main>

<!-- ✅ AJAX SCRIPT -->
<script>
const searchInput = document.getElementById("searchInput");
const lokasiFilter = document.getElementById("lokasiFilter");
const serviceContainer = document.getElementById("serviceContainer");

function loadServis() {
  const s = searchInput.value;
  const l = lokasiFilter.value;

  fetch(`senarai_servis.php?ajax=1&kategori_id=<?= $kategori_id ?>&search=${encodeURIComponent(s)}&lokasi=${encodeURIComponent(l)}`)
    .then(res => res.text())
    .then(data => {
      serviceContainer.innerHTML = data;
    });
}

// ✅ Load awal
loadServis();

// ✅ Real-time
searchInput.addEventListener("keyup", loadServis);
lokasiFilter.addEventListener("change", loadServis);
</script>

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

</body>
</html>
