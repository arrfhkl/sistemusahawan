<?php
include("connection.php");
include "header.php";

if (!isset($_GET['id'])) {
  die("Servis tidak ditemui.");
}

$id = (int)$_GET['id'];

/* ===============================
   AMBIL SERVIS + USAHAWAN
================================ */
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

/* ===============================
   GAMBAR SERVIS
================================ */
$gambar = $service['gambar_servis_url'];
if (!empty($gambar) && strpos($gambar, 'uploads/') === false) {
  $gambar = "uploads/" . $gambar;
}

/* ===============================
   AVATAR TUKANG
================================ */
$avatar = $service['avatar'];
if (empty($avatar)) $avatar = "default.png";
if (strpos($avatar, 'uploads/') === false) $avatar = "uploads/$avatar";

/* ===============================
   TOTAL CUSTOMER
================================ */
$total_customer = 0;
$check = $conn->query("SHOW TABLES LIKE 'servis_booking'");
if ($check->num_rows > 0) {
  $stmt2 = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM servis_booking 
    WHERE service_id = ?
  ");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $total_customer = (int)$stmt2->get_result()->fetch_assoc()['total'];
}

/* ===============================
   GALLERY
================================ */
$gallery = false;
$check2 = $conn->query("SHOW TABLES LIKE 'servis_gallery'");
if ($check2->num_rows > 0) {
  $stmt3 = $conn->prepare("
    SELECT * FROM servis_gallery WHERE service_id = ?
  ");
  $stmt3->bind_param("i", $id);
  $stmt3->execute();
  $gallery = $stmt3->get_result();
}

/* ===============================
   SERVIS LAIN OLEH TUKANG SAMA
================================ */
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

/* ===============================
   SERVIS BERKAITAN (KATEGORI SAMA)
================================ */
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
<title><?= htmlspecialchars($service['nama']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f4f6f8; }

.container {
  max-width: 1100px;
  margin: 30px auto;
  background: #fff;
  padding: 30px;
  border-radius: 14px;
}

.hero img {
  width: 100%;
  height: 360px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: 30px;
}

.section { margin: 60px 0; }
.section h2 { margin-bottom: 15px; }

.cta-group {
  display: flex;
  gap: 15px;
  margin: 40px 0;
}
.cta-group button {
  flex: 1;
  padding: 14px;
  border-radius: 8px;
  border: none;
  font-size: 1rem;
  cursor: pointer;
}
.btn-primary { background: #007bff; color: #fff; }
.btn-secondary { background: #25D366; color: #fff; }

.tukang-card {
  display: flex;
  gap: 20px;
  background: #eef4ff;
  padding: 25px;
  border-radius: 14px;
  align-items: center;
}
.tukang-card img {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
}

.stats {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 15px;
  margin-top: 30px;
}
.stat-box {
  background: #fafafa;
  padding: 18px;
  border-radius: 12px;
  text-align: center;
  font-weight: bold;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 12px;
}
.gallery-grid img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 10px;
}

.servis-lain-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 18px;
}
.servis-card {
  border: 1px solid #ddd;
  border-radius: 12px;
  overflow: hidden;
}
.servis-card img {
  width: 100%;
  height: 160px;
  object-fit: cover;
}
.servis-card-body { padding: 15px; }

@media(max-width:768px){
  .stats { grid-template-columns: repeat(2,1fr); }
  .gallery-grid { grid-template-columns: repeat(2,1fr); }
  .servis-lain-grid { grid-template-columns: 1fr; }
  .tukang-card { flex-direction: column; text-align: center; }
}

.hero {
  position: relative;
}

.hero-overlay {
  position: absolute;
  bottom: 20px;
  left: 20px;
}

.badge {
  background: rgba(0,0,0,0.65);
  color: #fff;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 0.85rem;
}

/*CTA Button Lebih “Action-Oriented”*/
.cta-group button {
  transition: transform .15s ease, box-shadow .15s ease;
}

.cta-group button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,.15);
}

/*mobile-friendly sticky bar.*/
.sticky-cta {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: none;
  gap: 10px;
  padding: 12px;
  background: #fff;
  border-top: 1px solid #ddd;
}

.sticky-cta a {
  flex: 1;
  text-align: center;
  padding: 12px;
  border-radius: 8px;
  color: #fff;
  text-decoration: none;
  font-weight: bold;
}

@media(max-width:768px){
  .sticky-cta { display: flex; }
}

.verified {
  display: inline-block;
  margin-top: 6px;
  font-size: 0.85rem;
  color: #2e7d32;
  background: #e8f5e9;
  padding: 4px 10px;
  border-radius: 12px;
}

.img-preview {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.8);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.img-preview img {
  max-width: 90%;
  max-height: 90%;
  border-radius: 12px;
}

.servis-card {
  transition: transform .15s ease, box-shadow .15s ease;
}

.servis-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0,0,0,.12);
}

</style>
</head>

<body>

<div class="container">

<!-- HERO -->
<div class="hero">
  <img src="<?= htmlspecialchars($gambar) ?>" alt="Servis">
</div>

<div class="hero-overlay">
  <span class="badge">Servis Profesional</span>
</div>

<h1><?= htmlspecialchars($service['nama']) ?></h1>
<p>📍 <?= htmlspecialchars($service['lokasi']) ?></p>

<!-- DESKRIPSI -->
<div class="section">
  <h2>Tentang Servis</h2>
  <p><?= nl2br(htmlspecialchars($service['deskripsi'])) ?></p>
</div>

<!-- CTA -->
<div class="cta-group">
  <button class="btn-primary"
    onclick="window.location.href='tempah_servis.php?id=<?= $service['id'] ?>'">
    Tempah Servis
  </button>
  <button class="btn-secondary"
    onclick="window.location.href='chat_room.php?servis_id=<?= $service['id'] ?>'">
    💬 Chat Dengan Tukang
  </button>
</div>

<!-- PROFIL TUKANG -->
<div class="section">
  <h2>Profil Penyedia Servis</h2>
  <div class="tukang-card">
    <img src="<?= htmlspecialchars($avatar) ?>">
    <div>
      <p><strong><?= htmlspecialchars($service['nama_tukang']) ?></strong></p>
      <p><?= htmlspecialchars($service['perniagaan']) ?></p>
      <p><?= htmlspecialchars($service['jenis']) ?></p>
      <p>📞 <?= htmlspecialchars($service['telefon']) ?></p>
      <p>Daftar: <?= date("d M Y", strtotime($service['tarikh_daftar'])) ?></p>
      <p class="verified">✔ Penyedia Disahkan</p>
    </div>
  </div>
</div>

<!-- STATISTIK -->
<div class="stats">
  <div class="stat-box">👥 <?= $total_customer ?> Pelanggan</div>
  <div class="stat-box">💼 <?= $servis_lain ? $servis_lain->num_rows + 1 : 1 ?> Servis</div>
  <div class="stat-box">✅ Disahkan</div>
</div>

<!-- GALLERY -->
 <div id="imgPreview" class="img-preview" onclick="this.style.display='none'">
  <img id="previewImg">
</div>
<div class="section">
<h2>Contoh Kerja</h2>
<div class="gallery-grid">
<?php if ($gallery && $gallery->num_rows > 0): ?>
  <?php while ($g = $gallery->fetch_assoc()):
    $gbr = $g['gambar'];
    if (strpos($gbr, 'uploads/') === false) $gbr = "uploads/$gbr";
  ?>
    <img src="<?= htmlspecialchars($gbr) ?>">
  <?php endwhile; ?>
<?php else: ?>
  <p>Tiada gambar kerja.</p>
<?php endif; ?>
</div>
</div>

<!-- SERVIS LAIN OLEH TUKANG -->
<?php if ($servis_lain && $servis_lain->num_rows > 0): ?>
<div class="section">
<h2>Servis lain oleh <?= htmlspecialchars($service['perniagaan']) ?></h2>
<div class="servis-lain-grid">
<?php while ($sl = $servis_lain->fetch_assoc()):
  $img = $sl['gambar_servis_url'] ?: "default-service.png";
  if (strpos($img, 'uploads/') === false) $img = "uploads/$img";
?>
<div class="servis-card">
  <img src="<?= htmlspecialchars($img) ?>">
  <div class="servis-card-body">
    <h4><?= htmlspecialchars($sl['nama']) ?></h4>
    <p>📍 <?= htmlspecialchars($sl['lokasi']) ?></p>
    <a href="butiran_servis.php?id=<?= $sl['id'] ?>">Tengok lanjut →</a>
  </div>
</div>
<?php endwhile; ?>
</div>
</div>
<?php endif; ?>

<!-- SERVIS BERKAITAN -->
<?php if ($servis_berkaitan && $servis_berkaitan->num_rows > 0): ?>
<div class="section">
<h2>Servis Berkaitan</h2>
<div class="servis-lain-grid">
<?php while ($sb = $servis_berkaitan->fetch_assoc()):
  $img = $sb['gambar_servis_url'] ?: "default-service.png";
  if (strpos($img, 'uploads/') === false) $img = "uploads/$img";
?>
<div class="servis-card">
  <img src="<?= htmlspecialchars($img) ?>">
  <div class="servis-card-body">
    <h4><?= htmlspecialchars($sb['nama']) ?></h4>
    <p>📍 <?= htmlspecialchars($sb['lokasi']) ?></p>
    <a href="butiran_servis.php?id=<?= $sb['id'] ?>">Tengok lanjut →</a>
  </div>
</div>
<?php endwhile; ?>
</div>
</div>
<?php endif; ?>

</div>
<div class="sticky-cta">
  <a href="tempah_servis.php?id=<?= $service['id'] ?>" class="btn-primary">Tempah</a>
  <a href="chat_room.php?servis_id=<?= $service['id'] ?>" class="btn-secondary">Chat</a>

<?php include "footer.php"; ?>

<script>
document.querySelectorAll('.gallery-grid img').forEach(img => {
  img.onclick = () => {
    document.getElementById('previewImg').src = img.src;
    document.getElementById('imgPreview').style.display = 'flex';
  }
});
</script>

</body>
</html>

<?php $conn->close(); ?>
