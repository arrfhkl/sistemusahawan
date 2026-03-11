<!--THIS PAGE IS FOR CUSTOMER VIEW-->

<?php
include "connection.php";
include "header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM usahawan WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usahawan = $stmt->get_result()->fetch_assoc();

if (!$usahawan) {
  die("Usahawan tidak ditemui.");
}

/* ===== Avatar ===== */
$avatar = (!empty($usahawan['avatar']) && file_exists($usahawan['avatar']))
  ? $usahawan['avatar']
  : 'assets/img/default_avatar.jpg';

/* ===== Produk ===== */
$stmt2 = $conn->prepare("SELECT * FROM produk WHERE usahawan_id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$produk = $stmt2->get_result();

/* ===== Servis ===== */
$stmt3 = $conn->prepare("SELECT * FROM servis WHERE usahawan_id = ?");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$servis = $stmt3->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Profil <?= htmlspecialchars($usahawan['nama']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
.container {
  max-width:1100px;
  margin:40px auto;
  padding:20px;
}

.profile-box {
  background:#fff;
  padding:25px;
  border-radius:14px;
  box-shadow:0 6px 18px rgba(0,0,0,.08);
  margin-bottom:40px;
  display:flex;
  gap:20px;
  align-items:center;
}

.profile-box img {
  width:110px;
  height:110px;
  border-radius:50%;
  object-fit:cover;
}

.produk-grid {
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:20px;
}

.card {
  background:#fff;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 6px 16px rgba(0,0,0,.08);
}

.card img {
  width:100%;
  height:180px;
  object-fit:cover;
}

.card-body {
  padding:15px;
}

.card h3 {
  margin:0 0 8px 0;
}

.btn-view {
  display:block;
  margin-top:10px;
  padding:8px;
  background:#003366;
  color:#fff;
  text-align:center;
  text-decoration:none;
  border-radius:6px;
  font-size:0.9rem;
}

@media(max-width:992px){
  .produk-grid { grid-template-columns:repeat(2,1fr); }
}

@media(max-width:576px){
  .produk-grid { grid-template-columns:1fr; }
}
</style>
</head>

<body>

<div class="container">

<!-- PROFILE SUMMARY -->
<div class="profile-box">
  <img src="<?= htmlspecialchars($avatar) ?>" 
       onerror="this.src='assets/img/default_avatar.jpg'">
  <div>
    <h2><?= htmlspecialchars($usahawan['nama']) ?></h2>
    <p><strong><?= htmlspecialchars($usahawan['perniagaan']) ?></strong></p>
    <p><?= htmlspecialchars($usahawan['jenis']) ?></p>
    <p>📞 <?= htmlspecialchars($usahawan['telefon']) ?></p>
    <p>📍 <?= htmlspecialchars($usahawan['alamat']) ?></p>
  </div>
</div>

<!-- PRODUK -->
<?php if ($produk->num_rows > 0): ?>
<h2>Produk</h2>
<div class="produk-grid">
  <?php while ($p = $produk->fetch_assoc()): 
    $gambar = $p['gambar_url'];
    if (!empty($gambar) && strpos($gambar,'uploads/') === false) {
      $gambar = "uploads/".$gambar;
    }
  ?>
  <div class="card">
    <img src="<?= htmlspecialchars($gambar) ?>">
    <div class="card-body">
      <h3><?= htmlspecialchars($p['nama']) ?></h3>
      <p><?= htmlspecialchars($p['deskripsi']) ?></p>
      <p><strong>RM <?= number_format($p['harga'],2) ?></strong></p>
    </div>
  </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>


<!-- SERVIS -->
<?php if ($servis->num_rows > 0): ?>
<h2 style="margin-top:50px;">Servis Ditawarkan</h2>
<div class="produk-grid">
  <?php while ($s = $servis->fetch_assoc()):
    $gambarServis = $s['gambar_servis_url'];
    if (!empty($gambarServis) && strpos($gambarServis,'uploads/') === false) {
      $gambarServis = "uploads/".$gambarServis;
    }
  ?>
  <div class="card">
    <img src="<?= htmlspecialchars($gambarServis) ?>">
    <div class="card-body">
      <h3><?= htmlspecialchars($s['nama']) ?></h3>
      <p><strong>Lokasi:</strong> <?= htmlspecialchars($s['lokasi']) ?></p>

      <a class="btn-view" 
         href="butiran_servis.php?id=<?= $s['id'] ?>">
         Lihat Servis
      </a>
    </div>
  </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

</div>

<?php include "footer.php"; ?>
</body>
</html>

<?php $conn->close(); ?>
