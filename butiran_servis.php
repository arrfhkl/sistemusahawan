<?php
include("connection.php");
include "header.php";

if (!isset($_GET['id'])) {
  die("Servis tidak ditemui.");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
SELECT 
  s.*, 
  u.nama AS nama_tukang,
  u.perniagaan ,
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

/* ✅ SERVIS LAIN OLEH USAHAWAN YANG SAMA */
$servis_lain = [];
if (!empty($service['usahawan_id'])) {
  $stmt4 = $conn->prepare("
    SELECT id, nama, lokasi, gambar_servis_url
    FROM servis
    WHERE usahawan_id = ?
      AND id != ?
    ORDER BY id DESC
    LIMIT 3
  ");
  $stmt4->bind_param("ii", $service['usahawan_id'], $service['id']);
  $stmt4->execute();
  $servis_lain = $stmt4->get_result();
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

/* ✅ SERVIS LAIN YANG BERKAITAN (KATEGORI SAMA) */
$servis_berkaitan = [];
if (!empty($service['kategori_servis_id'])) {
  $stmt5 = $conn->prepare("
    SELECT id, nama, lokasi, gambar_servis_url
    FROM servis
    WHERE kategori_servis_id = ?
      AND id != ?
    ORDER BY id DESC
    LIMIT 6
  ");
  $stmt5->bind_param("ii", $service['kategori_servis_id'], $service['id']);
  $stmt5->execute();
  $servis_berkaitan = $stmt5->get_result();
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

/* ===== Servis Lain ===== */
.servis-lain-grid {
  margin-top: 20px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

.servis-card {
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #ddd;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.servis-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.servis-card img {
  width: 100%;
  height: 160px;
  object-fit: cover;
}

.servis-card-body {
  padding: 15px;
}

.servis-card-body h4 {
  font-size: 1.05rem;
  margin-bottom: 6px;
}

.servis-card-body p {
  font-size: 0.9rem;
  color: #555;
}

.servis-link {
  display: inline-block;
  margin-top: 8px;
  color: #007bff;
  font-weight: bold;
  text-decoration: none;
}

.servis-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .servis-lain-grid {
    grid-template-columns: repeat(1, 1fr);
  }
}

</style>
</head>
<body>

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

<?php if ($servis_lain && $servis_lain->num_rows > 0): ?>
<hr style="margin:40px 0">

<h2>
  Servis lain yang ditawarkan oleh Perniagaan
  <?= htmlspecialchars($service['perniagaan']) ?>:
</h2>

<div class="servis-lain-grid">
<?php while ($sl = $servis_lain->fetch_assoc()): ?>
  <?php
    $img = $sl['gambar_servis_url'];
    if (empty($img)) {
      $img = "default-service.png";
    } elseif (strpos($img, 'uploads/') === false) {
      $img = "uploads/$img";
    }
  ?>
  <div class="servis-card">
    <img src="<?= htmlspecialchars($img) ?>" alt="Servis">
    <div class="servis-card-body">
      <h4><?= htmlspecialchars($sl['nama']) ?></h4>
      <p>📍 <?= htmlspecialchars($sl['lokasi']) ?></p>
      <a href="butiran_servis.php?id=<?= $sl['id'] ?>" class="servis-link">
        Tengok lanjut →
      </a>  <!-- ===== create a page for usahawan (buyer view)===== -->
    </div>

  </div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<?php if ($servis_berkaitan && $servis_berkaitan->num_rows > 0): ?>
<hr style="margin:40px 0">

<h2>Servis lain yang berkaitan sama:</h2>

<div class="servis-lain-grid">
<?php while ($sb = $servis_berkaitan->fetch_assoc()): ?>
  <?php
    $img = $sb['gambar_servis_url'];
    if (empty($img)) {
      $img = "default-service.png";
    } elseif (strpos($img, 'uploads/') === false) {
      $img = "uploads/$img";
    }
  ?>
  <div class="servis-card">
    <img src="<?= htmlspecialchars($img) ?>" alt="Servis Berkaitan">
    <div class="servis-card-body">
      <h4><?= htmlspecialchars($sb['nama']) ?></h4>
      <p>📍 <?= htmlspecialchars($sb['lokasi']) ?></p>
      <a href="page.php?id=<?= $sb['id'] ?>" class="servis-link"> 
        Tengok lanjut →
      </a>
    </div>
  </div>
<?php endwhile; ?>
</div>
<?php endif; ?>


</div>

<?php include "footer.php"?>

</body>
</html>

<?php $conn->close(); ?>
