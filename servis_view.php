<?php
include "connection.php";
include "header.php";

/* =========================
   SECURITY CHECK
========================= */
if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

if (!isset($_GET['id'])) {
    die("<div style='padding:20px'>Servis tidak ditemui.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];
$servis_id   = (int) $_GET['id'];

/* =========================
   AMBIL SERVIS (OWNER ONLY)
========================= */
$stmt = $conn->prepare("
    SELECT s.*, ks.nama AS nama_kategori
    FROM servis s
    LEFT JOIN kategori_servis ks ON ks.id = s.kategori_servis_id
    WHERE s.id = ? AND s.usahawan_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $servis_id, $usahawan_id);
$stmt->execute();
$servis = $stmt->get_result()->fetch_assoc();

if (!$servis) {
    die("<div style='padding:20px'>Anda tidak dibenarkan akses servis ini.</div>");
}

/* =========================
   KPI SERVIS
========================= */
$q_total_tempahan = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis_booking 
    WHERE service_id = $servis_id
")->fetch_assoc()['total'] ?? 0;

/* =========================
   GALLERY
========================= */
$gallery = $conn->query("
    SELECT * FROM servis_gallery
    WHERE service_id = $servis_id
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Urus Servis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.container { max-width: 1200px; padding-top: 120px; }
.card { border-radius: 14px; box-shadow: 0 5px 18px rgba(0,0,0,.08); }
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
  gap: 15px;
}
.gallery-item img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 10px;
}
.badge-cover {
  position: absolute;
  top: 8px;
  left: 8px;
  background: #198754;
}
.gallery-item {
  position: relative;
}
</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="mb-4 text-center">
  <h2>Urus Servis</h2>
  <p class="text-muted"><?= htmlspecialchars($servis['nama']) ?></p>
</div>

<!-- ACTION -->
<div class="d-flex gap-2 mb-4 justify-content-center">
  <a href="butiran_servis.php?id=<?= $servis_id ?>" class="btn btn-outline-primary">Preview Customer</a>
  <a href="servis_edit.php?id=<?= $servis_id ?>" class="btn btn-primary">Edit Servis</a>
</div>

<!-- KPI -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h4><?= $q_total_tempahan ?></h4>
      <small>Jumlah Tempahan</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h4><?= $gallery->num_rows ?></h4>
      <small>Gambar Gallery</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h4><?= $servis['gambar_servis_url'] ? 'Lengkap' : 'Tiada' ?></h4>
      <small>Cover Servis</small>
    </div>
  </div>
</div>

<!-- MAKLUMAT SERVIS -->
<div class="card p-4 mb-4">
  <h5>Maklumat Servis</h5>
  <p><strong>Kategori:</strong> <?= htmlspecialchars($servis['nama_kategori'] ?? '-') ?></p>
  <p><strong>Lokasi:</strong> <?= htmlspecialchars($servis['lokasi']) ?></p>
  <p><?= nl2br(htmlspecialchars($servis['deskripsi'])) ?></p>
</div>

<!-- GALLERY -->
<div class="card p-4 mb-4">
  <h5 class="mb-3">Gallery Servis</h5>

  <?php if ($gallery->num_rows > 0): ?>
  <div class="gallery-grid">
    <?php while ($g = $gallery->fetch_assoc()):
      $img = strpos($g['gambar'],'uploads/') === false
        ? "uploads/".$g['gambar']
        : $g['gambar'];

      $isCover = ($servis['gambar_servis_url'] == $g['gambar']);
    ?>
    <div class="gallery-item">
      <?php if ($isCover): ?>
        <span class="badge badge-cover">Cover</span>
      <?php endif; ?>

      <img src="<?= htmlspecialchars($img) ?>">

      <div class="mt-2 d-flex gap-1">
        <a href="servis_gallery_delete.php?id=<?= $g['id'] ?>&servis=<?= $servis_id ?>"
           class="btn btn-sm btn-danger w-100"
           onclick="return confirm('Padam gambar ini?')">
           Padam
        </a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
    <p class="text-muted">Tiada gambar gallery. Tambah untuk tarik pelanggan.</p>
  <?php endif; ?>
</div>

<!-- UPLOAD -->
<div class="card p-4 mb-5">
  <h5>Tambah Gambar Gallery</h5>
  <form action="servis_gallery_upload.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="service_id" value="<?= $servis_id ?>">
    <input type="file" name="gambar[]" multiple class="form-control mb-3" required>
    <button class="btn btn-primary">Upload Gambar</button>
  </form>
</div>

</div>

<?php include "footer.php"; ?>
</body>
</html>
