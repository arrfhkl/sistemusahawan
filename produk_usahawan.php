<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   KPI / RINGKASAN PRODUK
=========================== */

// Jumlah produk
$q_total = $conn->query("
    SELECT COUNT(*) AS total 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Produk aktif
$q_aktif = $conn->query("
    SELECT COUNT(*) AS aktif 
    FROM produk 
    WHERE usahawan_id = $usahawan_id 
    AND stok > 0
")->fetch_assoc()['aktif'] ?? 0;

// Produk habis stok
$q_habis = $conn->query("
    SELECT COUNT(*) AS habis 
    FROM produk 
    WHERE usahawan_id = $usahawan_id 
    AND stok = 0
")->fetch_assoc()['habis'] ?? 0;

// Nilai inventori (harga × stok)
$q_nilai = $conn->query("
    SELECT IFNULL(SUM(harga * stok),0) AS nilai 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['nilai'] ?? 0;

/* ===========================
   SENARAI PRODUK
=========================== */
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM produk WHERE usahawan_id = ?";

if ($search !== '') {
    $sql .= " AND nama LIKE ?";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = "%$search%";
    $stmt->bind_param("is", $usahawan_id, $like);
} else {
    $stmt->bind_param("i", $usahawan_id);
}

$stmt->execute();
$produk = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk - Sistem Usahawan Pahang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">

<style>
:root {
    --primary-dark: #0f2a44;   /* biru gelap utama */
    --primary-soft: #3b6ea8;   /* biru lembut */
    --text-muted: #6b7280;
}

/* ===========================c                                                                                                                                                
   LAYOUT ASAS
=========================== */
.container {
    max-width: 1280px;   /* atau 1200px kalau nak lebih padat */
    margin: 0 auto;      /* center */
    padding: 30px 40px;
    padding-top: 120px;
    min-height: 100vh;
}

.title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-wrap i {
    font-size: 20px;
    color: var(--primary-soft);
}

/* ===========================
   KPI CARDS
=========================== */
.kpi-card:hover {
    transform: translateY(-4px);
}

.blue { color:#007bff; }
.green { color:#28a745; }
.red { color:#dc3545; }
.orange { color:#fd7e14; }

/* KPI / STATS STYLE — SAME AS PESANAN MASUK */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: #fff;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

.stat-icon.total { color: #003399; }
.stat-icon.active { color: #28a745; }
.stat-icon.low { color: #dc3545; }
.stat-icon.value { color: #007bff; }

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #003399;
  margin-bottom: 5px;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
  font-weight: 500;
}

/* ===========================
 PRODUK
=========================== */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    max-width: 1200px;
    margin: 0 auto;
    gap: 24px;
}

.product-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    transition: 0.3s;
}

.product-card:hover {
    transform: translateY(-6px);
}

.product-cover {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.product-body {
    padding: 16px;
    flex: 1;
}

.product-body h3 {
    font-size: 16px;
    margin: 8px 0 4px;
}

.product-body p {
    font-size: 13px;
    color: #666;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 14px;
}

.product-actions {
    display: flex;
    justify-content: space-between;
    padding: 14px 16px;
    border-top: 1px solid #eee;
}

.product-actions a {
    font-weight: 600;
    text-decoration: none;
    color: #007bff;
}

.product-img {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-green { background:#e6f4ea; color:#1e7e34; }
.badge-red { background:#fdecea; color:#b02a37; }
.badge-orange { background:#fff3cd; color:#856404; }

.action a {
    margin-right: 8px;
    text-decoration: none;
    font-weight: 600;
    color: #007bff;
}

.empty {
    padding: 40px;
    text-align: center;
    color: #777;
}

.page-header {
  background: #fff;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 30px;
  text-align: center;
}

.page-header h2 {
  color: #003399;
  margin: 0;
  font-weight: 700;
}

.subtitle {
    font-size: 14px;
    color: #666;
    margin-top: 4px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.search-input {
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #ddd;
    min-width: 220px;
}

.btn-primary {
    background: #007bff;
    color: #fff;
    padding: 10px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}
.kpi-card {
    position: relative;
}

/* warna ikut KPI */
.icon-blue   { color:#007bff; }
.icon-green  { color:#28a745; }
.icon-red    { color:#dc3545; }
.icon-orange { color:#fd7e14; }

.filters-section {
  background: #fff;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}

.filters-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  flex: 1;
  min-width: 220px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #003399;
}

.filter-group input {
  width: 100%;
  padding: 10px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
}

.filter-btn {
  background: #003399;
  color: #fff;
  padding: 10px 25px;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
}

.product-card {
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-6px);
}

.product-actions a {
    cursor: pointer;
}

</style>

<div class="container">

    <div class="page-header">
        <h2><i class="fas fa-box-open"></i> Produk Perniagaan</h2>
    </div>

    <!-- ===========================
    CONTAINER
=========================== -->
<div class="stats-container">

    <div class="stat-card">
        <div class="stat-icon total">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-number"><?= $q_total ?></div>
        <div class="stat-label">Jumlah Produk</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon active">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="stat-number"><?= $q_aktif ?></div>
        <div class="stat-label">Produk Aktif</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon low">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="stat-number"><?= $q_habis ?></div>
        <div class="stat-label">Produk Habis Stok</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon value">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-number">RM <?= number_format($q_nilai, 2) ?></div>
        <div class="stat-label">Nilai Inventori</div>
    </div>

</div>

    <div class="filters-section">
        <div class="filters-row">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Cari Produk</label>
                <input type="text"
                    id="searchProduk"
                    placeholder="Nama produk"
                    autocomplete="off">
            </div>

            <a href="tambah_produk.php" class="filter-btn">
                <i class="fas fa-plus"></i> Tambah Produk
            </a>
        </div>
    </div>
    

<!-- ===========================
     TABLE PRODUK
=========================== -->
<div id="produkContainer">
<div class="product-grid">

<?php if ($produk->num_rows > 0): ?>
    <?php while ($p = $produk->fetch_assoc()):

        if ($p['stok'] == 0) {
            $badge = "badge-red";
            $label = "Habis Stok";
        } elseif ($p['stok'] <= 5) {
            $badge = "badge-orange";
            $label = "Stok Rendah";
        } else {
            $badge = "badge-green";
            $label = "Aktif";
        }

        $img = !empty($p['gambar_url'])
            ? "uploads/" . $p['gambar_url']
            : "assets/img/no-image.png";
    ?>

    <div class="product-card" data-href="produk_view.php?id=<?= $p['id'] ?>">
        <img src="<?= $img ?>" class="product-cover">

        <div class="product-body">
            <span class="badge <?= $badge ?>"><?= $label ?></span>
            <h3><?= htmlspecialchars($p['nama']) ?></h3>
            <p><?= substr(strip_tags($p['deskripsi']),0,60) ?>...</p>

            <div class="product-meta">
                <strong>RM <?= number_format($p['harga'],2) ?></strong>
                <span>Stok: <?= $p['stok'] ?></span>
            </div>
        </div>
    </div>

    <?php endwhile; ?>
<?php else: ?>
    <div class="empty">Tiada produk.</div>
<?php endif; ?>

</div>
</div>
</div>

<script>
const searchInput = document.getElementById("searchProduk");
const produkContainer = document.getElementById("produkContainer");

function loadProduk() {
    const keyword = searchInput.value;

    fetch(`?search=${encodeURIComponent(keyword)}`)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");
            produkContainer.innerHTML =
                doc.querySelector("#produkContainer").innerHTML;
        });
}

searchInput.addEventListener("keyup", loadProduk);
</script>

<script>
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function () {
        window.location.href = this.dataset.href;
    });
});

// prevent click bubbling for action links
document.querySelectorAll('.product-actions a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.stopPropagation();
    });
});
</script>


<?php include "footer.php"; ?>

</body>
</html>