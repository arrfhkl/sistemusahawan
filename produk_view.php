<?php
include "connection.php";
include "header.php";

/* ===========================
   VALIDATION
=========================== */
if (!isset($_GET['id'])) {
    die("<div class='empty'>Produk tidak ditemui.</div>");
}

$produk_id = (int) $_GET['id'];

/* ===========================
   FETCH PRODUK + USAHAWAN
=========================== */
$stmt = $conn->prepare("
    SELECT 
        p.*,
        u.nama        AS nama_usahawan,
        u.perniagaan  AS nama_perniagaan,
        u.telefon,
        u.email,
        u.alamat
    FROM produk p
    LEFT JOIN usahawan u ON u.id = p.usahawan_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div class='empty'>Produk tidak dijumpai.</div>");
}

$p = $result->fetch_assoc();

/* ===========================
   DATA HELPER
=========================== */
$img = !empty($p['gambar_url'])
    ? "uploads/" . $p['gambar_url']
    : "assets/img/no-image.png";

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
?>

<style>
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px;
}

.product-view {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 30px;
}

.product-image {
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}

.product-image img {
    width: 100%;
    height: 380px;
    object-fit: cover;
    border-radius: 12px;
}

.product-info {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}

.product-info h1 {
    font-size: 24px;
    margin-bottom: 8px;
}

.price {
    font-size: 22px;
    font-weight: 700;
    color: #007bff;
    margin: 10px 0;
}

.meta {
    margin-top: 12px;
    color: #555;
    font-size: 14px;
}

.meta span {
    display: block;
    margin-bottom: 6px;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 10px;
}

.badge-green { background:#e6f4ea; color:#1e7e34; }
.badge-red { background:#fdecea; color:#b02a37; }
.badge-orange { background:#fff3cd; color:#856404; }

.section {
    margin-top: 30px;
}

.section h3 {
    margin-bottom: 10px;
    font-size: 18px;
}

.desc {
    line-height: 1.7;
    color: #444;
}

.actions {
    margin-top: 25px;
    display: flex;
    gap: 12px;
}

.actions a {
    padding: 10px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}

.btn-back {
    background: #e5e7eb;
    color: #111;
}

.btn-edit {
    background: #007bff;
    color: #fff;
}

.btn-delete {
    background: #dc3545;
    color: #fff;
}

.seller-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}

.empty {
    padding: 50px;
    text-align: center;
    color: #777;
}
</style>

<div class="container">

<div class="product-view">

    <!-- IMAGE -->
    <div class="product-image">
        <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
    </div>

    <!-- INFO -->
    <div class="product-info">
        <span class="badge <?= $badge ?>"><?= $label ?></span>

        <h1><?= htmlspecialchars($p['nama']) ?></h1>
        <div class="price">RM <?= number_format($p['harga'], 2) ?></div>

        <div class="meta">
            <span><strong>Stok:</strong> <?= $p['stok'] ?></span>
            <span><strong>Lokasi:</strong> <?= htmlspecialchars($p['lokasi'] ?? '-') ?></span>
        </div>

        <div class="section">
            <h3>Deskripsi Produk</h3>
            <div class="desc">
                <?= nl2br(htmlspecialchars($p['deskripsi'] ?? 'Tiada deskripsi.')) ?>
            </div>
        </div>

        <div class="actions">
            <a href="produk_usahawan.php" class="btn-back">← Kembali</a>
            <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn-edit">Edit</a>
            <a href="delete_produk.php?id=<?= $p['id'] ?>"
               class="btn-delete"
               onclick="return confirm('Padam produk ini?')">Padam</a>
        </div>
    </div>

</div>

<!-- SELLER INFO -->
<div class="seller-box">
    <h3>Maklumat Usahawan</h3>
    <p><strong>Nama:</strong> <?= htmlspecialchars($p['nama_usahawan']) ?></p>
    <p><strong>Perniagaan:</strong> <?= htmlspecialchars($p['nama_perniagaan']) ?></p>
    <p><strong>Telefon:</strong> <?= htmlspecialchars($p['telefon']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></p>
    <p><strong>Alamat:</strong><br><?= nl2br(htmlspecialchars($p['alamat'] ?? '-')) ?></p>
</div>

</div>

<?php include "footer.php"; ?>
