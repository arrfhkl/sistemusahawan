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
   AMBIL GALLERY PRODUK
================================ */
$gallery_images = [];
$main_image = $gambar; // fallback dari produk

$gallery_stmt = $conn->prepare("
    SELECT gambar_url, is_primary
    FROM produk_gallery
    WHERE produk_id = ?
    ORDER BY is_primary DESC, id ASC
");
$gallery_stmt->bind_param("i", $id);
$gallery_stmt->execute();
$gallery_result = $gallery_stmt->get_result();

while ($row = $gallery_result->fetch_assoc()) {

    $img = $row['gambar_url'];

    if (strpos($img, 'uploads/') === false) {
        $img = "uploads/" . $img;
    }

    $gallery_images[] = $img;

    if ($row['is_primary'] == 1) {
        $main_image = $img;
    }
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
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f0f2f7;
    padding-top: 90px;
    color: #1e293b;
}

.container {
    max-width: 1180px;
    margin: 36px auto;
    padding: 0 20px;
}

/* ====== BREADCRUMB ====== */
.breadcrumb {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 20px;
}
.breadcrumb a { color: #2563eb; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

/* ====== MAIN WRAPPER ====== */
.product-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    background: #fff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}

/* ====== LEFT — IMAGE ====== */
.product-image { width: 100%; }

.main-image-box {
    background: #f8f9fb;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #eef0f4;
}

.main-image-box img {
    width: 100%;
    height: 460px;
    object-fit: contain;
    padding: 14px;
    box-sizing: border-box;
    cursor: zoom-in;
    transition: transform 0.4s ease;
}

.main-image-box img:hover {
    transform: scale(1.03);
}

/* Arrow nav on main image */
.main-image-wrapper { position: relative; }

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.85);
    color: #1e293b;
    border: 1px solid #e2e8f0;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    z-index: 5;
    backdrop-filter: blur(6px);
    transition: 0.2s;
}
.nav-btn.left { left: 10px; }
.nav-btn.right { right: 10px; }
.nav-btn:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

/* ====== MINI GALLERY ====== */
.mini-gallery {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.mini-gallery::-webkit-scrollbar { height: 4px; }
.mini-gallery::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

.mini-thumb {
    width: 70px;
    height: 70px;
    object-fit: contain;
    background: #f8f9fb;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid #e2e8f0;
    flex-shrink: 0;
    padding: 4px;
    transition: border-color 0.2s, transform 0.2s;
}

.mini-thumb.active-thumb,
.mini-thumb:hover {
    border-color: #2563eb;
    transform: scale(1.05);
}

/* ====== RIGHT — INFO ====== */
.product-info h1 {
    font-size: 26px;
    font-weight: 800;
    line-height: 1.3;
    color: #0f172a;
    margin-bottom: 12px;
}

.price {
    font-size: 30px;
    font-weight: 800;
    color: #eb2525;
    margin: 14px 0;
    letter-spacing: -0.5px;
}

/* ====== BADGE TAGS ====== */
.badge-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-category { background: #eff6ff; color: #2563eb; }
.badge-location  { background: #f0fdf4; color: #16a34a; }
.badge-stock-ok  { background: #f0fdf4; color: #16a34a; }
.badge-stock-out { background: #fef2f2; color: #dc2626; }

/* ====== DIVIDER ====== */
.divider {
    height: 1px;
    background: #f1f5f9;
    margin: 22px 0;
}

/* ====== CTA GROUP ====== */
.cta-group {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.cta-group form { flex: 1; min-width: 130px; }
.cta-group form button { width: 100%; }

.cta-group button {
    flex: 1;
    min-width: 130px;
    padding: 13px 16px;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    letter-spacing: 0.2px;
}

.btn-buy {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    box-shadow: 0 4px 14px rgba(37,99,235,0.25);
}
.btn-buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37,99,235,0.35);
}

.btn-cart {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    box-shadow: 0 4px 14px rgba(245,158,11,0.25);
}
.btn-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245,158,11,0.35);
}

.btn-chat {
    background: linear-gradient(135deg, #25D366, #1ebe5d);
    color: #fff;
    box-shadow: 0 4px 14px rgba(37,211,102,0.25);
}
.btn-chat:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37,211,102,0.35);
}

/* ====== DESCRIPTION ====== */
.desc-box {
    background: #f8fafc;
    border-left: 3px solid #2563eb;
    border-radius: 0 10px 10px 0;
    padding: 16px 18px;
    font-size: 14px;
    line-height: 1.8;
    color: #475569;
    margin-top: 8px;
}

/* ====== SELLER CARD ====== */
.seller-card {
    display: flex;
    gap: 14px;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #eff6ff);
    padding: 16px 18px;
    border-radius: 14px;
    margin-top: 24px;
    border: 1px solid #e2e8f0;
    transition: box-shadow 0.2s;
}
.seller-card:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.1); }

.seller-card img {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.seller-info strong {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.seller-info p {
    margin: 3px 0 0;
    font-size: 12px;
    color: #64748b;
}

/* ====== SECTION TITLE ====== */
.section { margin-top: 56px; }

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 22px;
}

.section-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

.section-header::after {
    content: '';
    flex: 1;
    height: 2px;
    background: linear-gradient(to right, #e2e8f0, transparent);
    border-radius: 2px;
}

/* ====== PRODUCT CARD ====== */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 18px;
}

.card {
    display: block;
    color: inherit;
    text-decoration: none;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    border-color: #e0e9ff;
}

.card-img-wrap {
    width: 100%;
    height: 175px;
    background: #f8f9fb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    box-sizing: border-box;
    transition: transform 0.35s ease;
}

.card:hover .card-img-wrap img {
    transform: scale(1.07);
}

.card-body { padding: 14px; }

.card-body h4 {
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.card-body .card-price {
    font-size: 15px;
    font-weight: 800;
    color: #2563eb;
    margin-bottom: 10px;
}

.card-body a {
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    color: #2563eb;
    background: #eff6ff;
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.2s;
}

.card-body a:hover {
    background: #2563eb;
    color: #fff;
}

/* ====== MODAL ====== */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.75);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    width: 85%;
    max-width: 900px;
    height: 80vh;
    display: grid;
    grid-template-columns: 2fr 1fr;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.3);
}

.modal-left {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fb;
}

.modal-left img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
}

.modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: #2563eb;
    color: #fff;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    transition: 0.2s;
}
.modal-nav:hover { background: #1d4ed8; transform: translateY(-50%) scale(1.1); }
.modal-nav.left { left: 14px; }
.modal-nav.right { right: 14px; }

.modal-right {
    overflow-y: auto;
    padding: 12px;
    background: #f8f9fb;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
    align-content: start;
}

.modal-thumb {
    width: 80px;
    height: 80px;
    object-fit: contain;
    background: #fff;
    cursor: pointer;
    border-radius: 10px;
    border: 2px solid transparent;
    padding: 4px;
    transition: 0.2s;
}
.modal-thumb.active-thumb,
.modal-thumb:hover { border-color: #2563eb; }

.modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    background: #ef4444;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    z-index: 20;
    transition: 0.2s;
}
.modal-close:hover { background: #dc2626; transform: scale(1.1); }

/* ====== RESPONSIVE ====== */
@media (max-width: 900px) {
    .product-wrapper {
        grid-template-columns: 1fr;
        padding: 22px;
        gap: 28px;
    }
    .main-image-box img { height: 320px; }
    .cta-group { flex-direction: column; }
    .modal-content { grid-template-columns: 1fr; height: 90vh; }
    .modal-right { grid-template-columns: repeat(4, 1fr); max-height: 120px; }
}

</style>

</head>

<body>
<div class="container">

<div class="product-wrapper">

    <!-- LEFT IMAGE -->
    <div class="product-image">

        <!-- MAIN IMAGE -->
        <div class="main-image-wrapper">

            <button class="nav-btn left" onclick="prevImage()">‹</button>
            <button class="nav-btn right" onclick="nextImage()">›</button>

            <div class="main-image-box">
                <img id="mainImage" src="<?= htmlspecialchars($main_image) ?>" onclick="openModal()">
            </div>

        </div>
            <?php if (!empty($gallery_images)): ?>
            <div class="mini-gallery">
                <?php foreach ($gallery_images as $index => $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>"
                        class="mini-thumb"
                        data-index="<?= $index ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- ===== MODAL GALLERY ===== -->
            <div id="imageModal" class="modal">
                <div class="modal-content">
                    <button class="modal-close" onclick="closeModal()">✕</button>

                    <!-- LEFT SIDE IMAGE -->
                    <div class="modal-left">
                        <button class="modal-nav left" onclick="prevImage()">‹</button>
                        <img id="modalMainImage">
                        <button class="modal-nav right" onclick="nextImage()">›</button>
                    </div>

                    <!-- RIGHT SIDE LIST -->
                    <div class="modal-right" id="modalThumbList">
                    </div>

                </div>
            </div>

    </div>

    <!-- RIGHT INFO -->
    <div class="product-info">

        <h1><?= htmlspecialchars($produk['nama']) ?></h1>

        <div class="price">
            RM <?= number_format($produk['harga'],2) ?>
        </div>

        <div class="badge-row">
            <span class="badge badge-category">📦 <?= htmlspecialchars($produk['nama_kategori']) ?></span>
            <span class="badge badge-location">📍 <?= htmlspecialchars($produk['lokasi']) ?></span>
            <?php if ($produk['stok'] > 0): ?>
                <span class="badge badge-stock-ok">✅ <?= $produk['stok'] ?> unit tersedia</span>
            <?php else: ?>
                <span class="badge badge-stock-out">❌ Stok habis</span>
            <?php endif; ?>
            <span class="badge badge-category">🏪 <?= htmlspecialchars($produk['perniagaan']) ?></span>
        </div>

        <div class="divider"></div>

        <!-- CTA -->
        <div class="cta-group">

        <?php if ($produk['stok'] > 0): ?>

        <!-- ADD TO CART -->
        <button class="btn-cart"
        onclick="tambahKeCart(
        <?= (int)$produk['id'] ?>,
        '<?= htmlspecialchars(addslashes($produk['nama'])) ?>',
        <?= (float)$produk['harga'] ?>,
        '<?= htmlspecialchars(addslashes($produk['gambar_url'])) ?>'
        )">
        🛒 Tambah ke Troli
        </button>

        <!-- BUY NOW -->
        <button class="btn-buy"
        onclick="beliSekarang(
        <?= (int)$produk['id'] ?>,
        '<?= htmlspecialchars(addslashes($produk['nama'])) ?>',
        <?= (float)$produk['harga'] ?>,
        '<?= htmlspecialchars(addslashes($produk['gambar_url'])) ?>'
        )">
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
        <<a href="profil_usahawan3.php?id=<?= $produk['usahawan_id'] ?>" class="seller-card">
            <img src="<?= htmlspecialchars($avatar) ?>">
            <div class="seller-info">
                <strong><?= htmlspecialchars($produk['nama_usahawan']) ?></strong>
                <p><?= htmlspecialchars($produk['telefon']) ?></p>
                <p>Ahli sejak <?= date("d M Y", strtotime($produk['tarikh_daftar'])) ?></p>
            </div>
            <div style="margin-left:auto; font-size:13px; color:#2563eb; font-weight:600;">
                Lihat Profil →
            </div>
        </a>

    </div>
</div>

<!-- PRODUK LAIN -->
<?php if ($produk_lain && $produk_lain->num_rows > 0): ?>
<div class="section">
<h2>Produk lain yang ditawarkan</h2>
<div class="card-grid">
<?php while ($pl = $produk_lain->fetch_assoc()):
$img = $pl['gambar_url'] ?: "default.png";
if (strpos($img, 'uploads/') === false) $img = "uploads/$img";
?>
<a href="butiran_produk.php?id=<?= $pl['id'] ?>" class="card">
    <div class="card-img-wrap">
        <img src="<?= htmlspecialchars($img) ?>">
    </div>
    <div class="card-body">
        <h4><?= htmlspecialchars($pl['nama']) ?></h4>
        <p class="card-price">RM <?= number_format($pl['harga'],2) ?></p>
    </div>
</a>
<?php endwhile; ?>
</div>
</div>
<?php endif; ?>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const images = <?= json_encode(
        !empty($gallery_images) ? $gallery_images : [$gambar]
    ) ?>;

    const mainImage = document.getElementById("mainImage");
    const modal = document.getElementById("imageModal");
    const modalMainImage = document.getElementById("modalMainImage");
    const modalThumbList = document.getElementById("modalThumbList");

    let currentIndex = images.indexOf(mainImage.getAttribute("src"));
    if (currentIndex === -1) currentIndex = 0;

    // =========================
    // UPDATE IMAGE
    // =========================
    function updateImage() {
        mainImage.src = images[currentIndex];
        modalMainImage.src = images[currentIndex];
        setActiveThumb();

        // Update modal thumbs highlight jika modal sedang terbuka
        if (modal.style.display === "flex") {
            generateModalThumbs();
        }
    }

    // =========================
    // THUMBNAIL ACTIVE STATE
    // =========================
    function setActiveThumb() {
        const thumbs = document.querySelectorAll(".mini-thumb");
        thumbs.forEach((thumb, index) => {
            if (index === currentIndex) {
                thumb.classList.add("active-thumb");
            } else {
                thumb.classList.remove("active-thumb");
            }
        });
    }

    // CLICK MINI THUMB
    document.querySelectorAll(".mini-thumb").forEach((thumb, index) => {
        // HOVER → change image
        thumb.addEventListener("mouseenter", function () {
            currentIndex = index;
            updateImage();
        });

        // CLICK → open modal
        thumb.addEventListener("click", function () {
            openModal();
        });
    });

    // =========================
    // CHANGE IMAGE
    // =========================
    window.changeImage = function (src, index) {
        currentIndex = index;
        updateImage();
    };

    window.prevImage = function () {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        updateImage();
    };

    window.nextImage = function () {
        currentIndex = (currentIndex + 1) % images.length;
        updateImage();
    };

    // =========================
    // OPEN MODAL
    // =========================
    window.openModal = function () {
        modal.style.display = "flex";
        modalMainImage.src = images[currentIndex];
        generateModalThumbs();
    };

    // =========================
    // CLOSE MODAL
    // =========================
    window.closeModal = function () {
        modal.style.display = "none";
    };

    // =========================
    // GENERATE MODAL THUMBS
    // =========================
    function generateModalThumbs() {
        modalThumbList.innerHTML = "";

        images.forEach((img, index) => {
            const thumb = document.createElement("img");
            thumb.src = img;
            thumb.classList.add("modal-thumb");

            if (index === currentIndex) {
                thumb.classList.add("active-thumb");
            }

            thumb.onclick = function () {
                currentIndex = index;
                updateImage();
                generateModalThumbs();
            };

            modalThumbList.appendChild(thumb);
        });
    }

    // INIT
    updateImage();

    //close button
    modal.addEventListener("click", function(e){
    if(e.target === modal){
        closeModal();
    }
});
});

function beliSekarang(produk_id,nama,harga,gambar_url){

    const formData = new URLSearchParams({
        produk_id: produk_id,
        nama: nama,
        harga: harga,
        gambar_url: gambar_url,
        kuantiti: 1
    });

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            window.location.href = "checkout.php";
        }
    });

    // ========== FUNGSI TAMBAH KE CART ========== //
async function tambahKeCart(produk_id, nama, harga, gambar_url) {
  console.log('🟢 START - Data yang dihantar:', {
    produk_id: produk_id,
    nama: nama,
    harga: harga,
    gambar_url: gambar_url
  });

  try {
    const formData = new URLSearchParams({
      produk_id: produk_id,
      nama: nama,
      harga: harga,
      gambar_url: gambar_url,
      kuantiti: 1
    });

    console.log('🟡 FormData:', formData.toString());

    const response = await fetch('add_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });

    console.log('🟠 Response status:', response.status);

    const text = await response.text();
    console.log('🔵 Response text:', text);

    const data = JSON.parse(text);
    console.log('🟣 Parsed JSON:', data);

    if (data.success) {
      showToast("🛒 Produk berjaya dimasukkan ke troli.", "success");

      // reload selepas toast 3 saat
      setTimeout(() => {
        document.querySelector(".cart-icon").innerText = "🛒";
      }, 3000);


    } else {
      showToast("⚠️ Gagal menambah produk ke troli.", "error");
    }

  } catch (error) {
    console.error('🔴 ERROR:', error);
    alert('❌ Error: ' + error.message);
  }
}

}
</script>

</div>
<?php include "footer.php"; ?>
</body>
</html>