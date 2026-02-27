<?php
include("connection.php");
include "header.php";

$user_id = isset($_SESSION['usahawan_id']) 
    ? (int)$_SESSION['usahawan_id'] 
    : 0;

if (!isset($_GET['id'])) {
  die("Produk tidak ditemui.");
}

$id = (int)$_GET['id'];

/* ===============================
   AMBIL PRODUK + USAHAWAN + KATEGORI
================================ */
$stmt = $conn->prepare("
SELECT 
  p.*,
  u.nama AS nama_usahawan,
  u.perniagaan,
  u.jenis,
  u.telefon,
  u.avatar,
  u.tarikh_daftar,
  k.nama AS nama_kategori
FROM produk p
LEFT JOIN usahawan u ON p.usahawan_id = u.id
LEFT JOIN kategori k ON p.kategori_id = k.id
WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
  die("Produk tidak ditemui.");
}

/* ===============================
   GAMBAR PRODUK
================================ */
$gambar = $produk['gambar_url'];
if (!empty($gambar) && strpos($gambar, 'uploads/') === false) {
  $gambar = "uploads/" . $gambar;
}

/* ===============================
   AVATAR USAHAWAN
================================ */
$avatar = $produk['avatar'];

if (!empty($avatar)) {
    if (strpos($avatar, 'uploads/') === false) {
        $avatar = 'uploads/' . $avatar;
    }

    if (!file_exists($avatar)) {
        $avatar = 'assets/img/default_avatar.jpg';
    }
} else {
    $avatar = 'assets/img/default_avatar.jpg';
}

/* ===============================
   PRODUK LAIN OLEH USAHAWAN SAMA
================================ */
$produk_lain = [];
$stmt2 = $conn->prepare("
SELECT id, nama, harga, gambar_url
FROM produk
WHERE usahawan_id = ?
AND id != ?
ORDER BY id DESC
LIMIT 3
");
$stmt2->bind_param("ii", $produk['usahawan_id'], $produk['id']);
$stmt2->execute();
$produk_lain = $stmt2->get_result();

/* ===============================
   PRODUK BERKAITAN (KATEGORI SAMA)
================================ */
$produk_berkaitan = [];
$stmt3 = $conn->prepare("
SELECT p.id, p.nama, p.harga, p.gambar_url, u.perniagaan
FROM produk p
LEFT JOIN usahawan u ON p.usahawan_id = u.id
WHERE p.kategori_id = ?
AND p.id != ?
ORDER BY p.id DESC
LIMIT 6
");
$stmt3->bind_param("ii", $produk['kategori_id'], $produk['id']);
$stmt3->execute();
$produk_berkaitan = $stmt3->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($produk['nama']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background:#f5f7fa;
    margin:0;
    padding-top:90px; 
}

.container {
    max-width:1200px;
    margin:40px auto;
    padding:0 20px;
}

/* ====== SPLIT LAYOUT ====== */
.product-wrapper {
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:50px;
    background:#fff;
    padding:40px;
    border-radius:16px;
    box-shadow:0 8px 30px rgba(0,0,0,0.05);
}

/* ===== LEFT SIDE IMAGE ===== */
.product-image {
    max-width:450px;
}

.product-image img {
    width:100%;
    height:auto;
    max-height:450px;
    object-fit:contain;
    border-radius:14px;
}

/* ===== RIGHT SIDE INFO ===== */
.product-info h1 {
    font-size:28px;
    margin-bottom:10px;
}

.price {
    font-size:26px;
    font-weight:700;
    color:#1f3c88;
    margin:15px 0;
}

.meta-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px 20px;
    margin:20px 0;
    font-size:14px;
    color:#555;
}

.divider {
    height:1px;
    background:#eee;
    margin:25px 0;
}

/* ===== CTA BUTTON ===== */
.cta-group {
    display:flex;
    gap:12px;
    margin-top:15px;
}

.cta-group button {
    flex:1;
    padding:14px;
    border:none;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    font-size:14px;
    transition:all 0.2s ease;
}

.cta-group form {
    flex:1;
}

.cta-group form button {
    width:100%;
}

/* BUY NOW - Primary */
.btn-buy {
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
}

.btn-buy:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 15px rgba(37,99,235,0.3);
}

/* ADD TO CART - Attention Color */
.btn-cart {
    background:#f59e0b;
    color:#fff;
}

.btn-cart:hover {
    background:#d97706;
    transform:translateY(-2px);
}

/* CHAT */
.btn-chat {
    background:#25D366;
    color:#fff;
}

.btn-chat:hover {
    background:#1ebe5d;
}
.btn-secondary {
    background:#25D366;
    color:#fff;
}

.btn-secondary:hover {
    background:#1ebe5d;
}

/* ===== SELLER CARD ===== */
.seller-card {
    display:flex;
    gap:15px;
    align-items:center;
    background:#f9fafc;
    padding:15px;
    border-radius:12px;
    margin-top:30px;
}

.seller-card img {
    width:60px;
    height:60px;
    border-radius:50%;
    object-fit:cover;
}

.seller-info p {
    margin:2px 0;
    font-size:13px;
    color:#666;
}

/* ===== SECTION ===== */
.section {
    margin-top:60px;
}

.section h2 {
    margin-bottom:20px;
}

.card-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
    gap:20px;
}

.card {
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,0.05);
    transition:0.2s;
}

.card:hover {
    transform:translateY(-4px);
}

.card img {
    width:100%;
    height:180px;
    object-fit:cover;
}

.card-body {
    padding:15px;
}

.card-body h4 {
    margin:5px 0;
    font-size:15px;
}

.card-body p {
    margin:3px 0;
    font-size:13px;
    color:#555;
}

/* ===== MOBILE ===== */
@media(max-width:900px){
    .product-wrapper{
        grid-template-columns:1fr;
        padding:20px;
    }

    .product-image img{
        height:350px;
    }
}
</style>
</head>

<body>
<div class="container">

<div class="product-wrapper">

    <!-- LEFT IMAGE -->
    <div class="product-image">
        <img src="<?= htmlspecialchars($gambar) ?>">
    </div>

    <!-- RIGHT INFO -->
    <div class="product-info">

        <h1><?= htmlspecialchars($produk['nama']) ?></h1>

        <div class="price">
            RM <?= number_format($produk['harga'],2) ?>
        </div>

        <div class="meta-grid">
            <div><strong>Kategori:</strong> <?= htmlspecialchars($produk['nama_kategori']) ?></div>
            <div><strong>Lokasi:</strong> <?= htmlspecialchars($produk['lokasi']) ?></div>
            <div><strong>Stok:</strong> 
                <?= $produk['stok'] > 0 ? $produk['stok']." unit tersedia" : "Stok habis" ?>
            </div>
            <div><strong>Perniagaan:</strong> <?= htmlspecialchars($produk['perniagaan']) ?></div>
        </div>

        <div class="divider"></div>

        <!-- CTA -->
        <div class="cta-group">

        <?php if ($produk['stok'] > 0): ?>

        <!-- ADD TO CART -->
        <form action="add_to_cart.php" method="POST" style="flex:1;">
            <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
            <input type="hidden" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>">
            <input type="hidden" name="harga" value="<?= $produk['harga'] ?>">
            <input type="hidden" name="gambar_url" value="<?= htmlspecialchars($produk['gambar_url']) ?>">

            <button type="submit" class="btn-cart">
                🛒 Tambah ke Troli
            </button>
        </form>

        <!-- BUY NOW -->
        <button class="btn-buy"
        onclick="window.location.href='beli_produk.php?id=<?= $produk['id'] ?>'">
        ⚡ Beli Sekarang
        </button>

        <?php endif; ?>

        <?php if (isset($_SESSION['usahawan_id'])): ?>

            <?php if ($_SESSION['usahawan_id'] != $produk['usahawan_id']): ?>
                <button class="btn-chat"
                onclick="window.location.href='chat_room.php?user_id=<?= $produk['usahawan_id'] ?>'">
                💬 Chat Usahawan
                </button>
            <?php else: ?>
                <button class="btn-chat" style="background:#999;" disabled>
                Ini produk anda
                </button>
            <?php endif; ?>

        <?php endif; ?>

        </div>

        <!-- SHORT DESCRIPTION -->
        <div class="divider"></div>

        <h3>Deskripsi</h3>
        <p><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>

        <!-- SELLER CARD -->
        <div class="seller-card">
            <img src="<?= htmlspecialchars($avatar) ?>">
            <div class="seller-info">
                <strong><?= htmlspecialchars($produk['nama_usahawan']) ?></strong>
                <p><?= htmlspecialchars($produk['telefon']) ?></p>
                <p>Ahli sejak <?= date("d M Y", strtotime($produk['tarikh_daftar'])) ?></p>
            </div>
        </div>

    </div>
</div>

<!-- PRODUK LAIN -->
<?php if ($produk_lain && $produk_lain->num_rows > 0): ?>
<div class="section">
<h2>Produk lain oleh penjual ini</h2>
<div class="card-grid">
<?php while ($pl = $produk_lain->fetch_assoc()):
$img = $pl['gambar_url'] ?: "default.png";
if (strpos($img, 'uploads/') === false) $img = "uploads/$img";
?>
<div class="card">
<img src="<?= htmlspecialchars($img) ?>">
<div class="card-body">
<h4><?= htmlspecialchars($pl['nama']) ?></h4>
<p>RM <?= number_format($pl['harga'],2) ?></p>
<a href="butiran_produk.php?id=<?= $pl['id'] ?>">Lihat Produk</a>
</div>
</div>
<?php endwhile; ?>
</div>
</div>
<?php endif; ?>

</div>
<?php include "footer.php"; ?>
</body>
</html>