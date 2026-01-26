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

<style>
:root {
    --primary-dark: #0f2a44;   /* biru gelap utama */
    --primary-soft: #3b6ea8;   /* biru lembut */
    --text-muted: #6b7280;
}

/* ===========================
   LAYOUT ASAS
=========================== */
.container {
    max-width: 1280px;   /* atau 1200px kalau nak lebih padat */
    margin: 0 auto;      /* center */
    padding: 30px 40px;
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
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    max-width: 1100px;
    margin: 0 auto 30px;
    gap: 20px;
}

.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    transition: 0.3s;
}

.kpi-card:hover {
    transform: translateY(-4px);
}

.kpi-title {
    font-size: 13px;
    color: #777;
}

.kpi-value {
    font-size: 26px;
    font-weight: 700;
    margin-top: 5px;
}

.blue { color:#007bff; }
.green { color:#28a745; }
.red { color:#dc3545; }
.orange { color:#fd7e14; }

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
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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

.kpi-icon {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: rgba(0,0,0,0.06);
}

/* warna ikut KPI */
.icon-blue   { color:#007bff; }
.icon-green  { color:#28a745; }
.icon-red    { color:#dc3545; }
.icon-orange { color:#fd7e14; }

</style>

<div class="container">

<div class="page-header">
    <div class="title-wrap">
    <i class="fa-solid fa-box-open"></i>
    <h1 class="page-title">Produk <span>Perniagaan</span></h1>
    </div>


    <div class="header-actions">
        <input type="text" id="searchProduk" placeholder="🔍 Cari produk..." autocomplete="off" class="search-input">
        <a href="tambah_produk.php" class="btn-primary">+ Tambah Produk</a>
    </div>
</div>

<!-- ===========================
     KPI SECTION
=========================== -->
<div class="kpi-grid">

    <div class="kpi-card">
    <div class="kpi-icon icon-blue">
        <i class="fa-solid fa-box"></i>
    </div>
        <div class="kpi-title">Jumlah Produk</div>
        <div class="kpi-value blue"><?= $q_total ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon icon-green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="kpi-title">Produk Aktif</div>
        <div class="kpi-value green"><?= $q_aktif ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon icon-red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="kpi-title">Produk Habis Stok</div>
        <div class="kpi-value red"><?= $q_habis ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon icon-blue">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div class="kpi-title">Nilai Inventori (RM)</div>
        <div class="kpi-value blue"><?= number_format($q_nilai,2) ?></div>
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
        <div class="product-card">
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

            <div class="product-actions">
                <a href="produk_view.php?id=<?= $p['id'] ?>">Lihat</a>
                <a href="edit_produk.php?id=<?= $p['id'] ?>">Edit</a>
                <a href="delete_produk.php?id=<?= $p['id'] ?>"
                   onclick="return confirm('Padam produk ini?')">Padam</a>
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

<?php include "footer.php"; ?>
