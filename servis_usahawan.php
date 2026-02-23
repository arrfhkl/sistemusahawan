<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   KPI / RINGKASAN SERVIS
=========================== */

// Jumlah servis
$q_total = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Servis aktif
$q_aktif = $conn->query("
    SELECT COUNT(*) AS aktif 
    FROM servis 
    WHERE usahawan_id = $usahawan_id
    AND nama IS NOT NULL
")->fetch_assoc()['aktif'] ?? 0;

// Jumlah tempahan
$q_tempahan = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis_booking 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Tempahan menunggu
$q_menunggu = $conn->query("
    SELECT COUNT(*) AS menunggu 
    FROM servis_booking 
    WHERE usahawan_id = $usahawan_id
    AND status = 'pending'
")->fetch_assoc()['menunggu'] ?? 0;

/* ===========================
   SENARAI SERVIS
=========================== */

$search = $_GET['search'] ?? '';

$sql = "
SELECT 
    s.*,
    ks.nama AS nama_kategori,
    (
        SELECT COUNT(*) 
        FROM servis_booking sb 
        WHERE sb.service_id = s.id
    ) AS jumlah_tempahan
FROM servis s
LEFT JOIN kategori_servis ks ON ks.id = s.kategori_servis_id
WHERE s.usahawan_id = ?
";

if ($search !== '') {
    $sql .= " AND s.nama LIKE ?";
}

$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = "%$search%";
    $stmt->bind_param("is", $usahawan_id, $like);
} else {
    $stmt->bind_param("i", $usahawan_id);
}

$stmt->execute();
$servis = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Servis Perniagaan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* warna ikut KPI */
.icon-blue   { color:#007bff; }
.icon-green  { color:#28a745; }
.icon-orange { color:#fd7e14; }
.icon-red    { color:#dc3545; }

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

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 30px 40px;
    padding-top: 120px;
    min-height: 100vh;
}

/* ================= KPI ================= */
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
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #003399;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

/* ================= PENAPIS ================= */
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
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
    color: #003399;
}

.filter-group input {
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ddd;
}

.filter-btn {
    background: #003399;
    color: #fff;
    padding: 10px 25px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
}

/* ================= KAD SERVIS ================= */
.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
}

.service-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
    overflow: hidden;
    cursor: pointer;
    transition: 0.3s;
}

.service-card:hover {
    transform: translateY(-6px);
}

.service-cover {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.service-body {
    padding: 16px;
}

.service-body h3 {
    font-size: 16px;
    margin: 8px 0 4px;
}

.service-body p {
    font-size: 13px;
    color: #666;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-top: 10px;
}

.badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-hijau { background:#e6f4ea; color:#1e7e34; }
.badge-biru { background:#e7f1ff; color:#004085; }

.empty {
    text-align: center;
    padding: 40px;
    color: #777;
}
</style>
</head>

<body>

<div class="container">

<div class="page-header">
        <h2><i class="fas fa-briefcase"></i> Servis Perniagaan</h2>
</div>

<div style="text-align:right; margin-bottom:20px;">
    <a href="seller_booking.php" class="filter-btn">
        <i class="fas fa-calendar-check"></i> Lihat Semua Tempahan
    </a>
</div>

<!-- KPI -->
<div class="stats-container">

    <!-- Jumlah Servis -->
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-list"></i>
        </div>
        <div class="stat-number"><?= $q_total ?></div>
        <div class="stat-label">Jumlah Servis</div>
    </div>

    <!-- Servis Aktif -->
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="stat-number"><?= $q_aktif ?></div>
        <div class="stat-label">Servis Aktif</div>
    </div>

    <!-- Jumlah Tempahan -->
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-number"><?= $q_tempahan ?></div>
        <div class="stat-label">Jumlah Tempahan</div>
    </div>

    <!-- Tempahan Menunggu -->
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-number"><?= $q_menunggu ?></div>
        <div class="stat-label">Tempahan Menunggu</div>
    </div>

</div>

<!-- PENAPIS -->
<div class="filters-section">
    <div class="filters-row">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Cari Servis</label>
            <input type="text" id="searchServis" placeholder="Masukkan nama servis">
        </div>

        <a href="servis_tambah.php" class="filter-btn">
            <i class="fas fa-plus"></i> Tambah Servis
        </a>
    </div>
</div>

<!-- SENARAI SERVIS -->
<div id="servisContainer">
<div class="service-grid">

<?php if ($servis->num_rows > 0): ?>
<?php while ($s = $servis->fetch_assoc()):

    $img = !empty($s['gambar_servis_url'])
        ? "uploads/".$s['gambar_servis_url']
        : "assets/img/no-image.png";

    $badge = $s['jumlah_tempahan'] > 0
        ? "<span class='badge badge-hijau'>Aktif</span>"
        : "<span class='badge badge-biru'>Belum Ditempah</span>";
?>

<div class="service-card" data-href="servis_view.php?id=<?= $s['id'] ?>">
    <img src="<?= $img ?>" class="service-cover">

    <div class="service-body">
        <?= $badge ?>
        <h3><?= htmlspecialchars($s['nama']) ?></h3>
        <p><?= substr(strip_tags($s['deskripsi']),0,70) ?>...</p>

        <div class="service-meta">
            <span><?= htmlspecialchars($s['nama_kategori'] ?? '-') ?></span>
            <span><?= $s['jumlah_tempahan'] ?> tempahan</span>
        </div>
    </div>
</div>

<?php endwhile; ?>
<?php else: ?>
<div class="empty">Tiada servis didaftarkan.</div>
<?php endif; ?>

</div>
</div>

</div>

<script>
const searchInput = document.getElementById("searchServis");
const servisContainer = document.getElementById("servisContainer");

function loadServis() {
    const keyword = searchInput.value;

    fetch(`?search=${encodeURIComponent(keyword)}`)
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, "text/html");
            servisContainer.innerHTML =
                doc.querySelector("#servisContainer").innerHTML;
        });
}

searchInput.addEventListener("keyup", loadServis);

document.addEventListener("click", function(e) {
    const card = e.target.closest(".service-card");
    if (card) {
        window.location.href = card.dataset.href;
    }
});
</script>

<?php include "footer.php"; ?>
</body>
</html>
