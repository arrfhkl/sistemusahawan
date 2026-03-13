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
$main_image = $gambar;

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
   RATING PRODUK
================================ */
$rating_data = ['avg' => 0, 'count' => 0];
$rating_check = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($rating_check && $rating_check->num_rows > 0) {
    $r_stmt = $conn->prepare("
        SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
        FROM reviews
        WHERE usahawan_id = ? AND type = 'produk'
    ");
    $r_stmt->bind_param("i", $produk['usahawan_id']);
    $r_stmt->execute();
    $r_row = $r_stmt->get_result()->fetch_assoc();
    if ($r_row) {
        $rating_data['avg']   = round((float)$r_row['avg_rating'], 1);
        $rating_data['count'] = (int)$r_row['total_reviews'];
    }
}

/* ===============================
   TERJUAL (DARI PESANAN SELESAI)
================================ */
$sold_count = 0;
$sold_check = $conn->query("SHOW TABLES LIKE 'pesanan_item'");
if ($sold_check && $sold_check->num_rows > 0) {
    $s_stmt = $conn->prepare("
        SELECT COALESCE(SUM(pi.kuantiti),0) AS total_sold
        FROM pesanan_item pi
        INNER JOIN pesanan p ON pi.pesanan_id = p.id
        WHERE pi.produk_id = ? AND p.status_pesanan = 'delivered'
    ");
    $s_stmt->bind_param("i", $id);
    $s_stmt->execute();
    $s_row = $s_stmt->get_result()->fetch_assoc();
    $sold_count = $s_row ? (int)$s_row['total_sold'] : 0;
}

/* ===============================
   AVATAR USAHAWAN
================================ */
$avatar = $produk['avatar'];
if (!empty($avatar)) {
    if (strpos($avatar, 'uploads/') === false) $avatar = 'uploads/' . $avatar;
    if (!file_exists($avatar)) $avatar = 'assets/img/default_avatar.jpg';
} else {
    $avatar = 'assets/img/default_avatar.jpg';
}

/* ===============================
   ULASAN PRODUK (per usahawan, type=produk)
================================ */
$reviews_list = [];
$rating_breakdown = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
$reviews_check = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($reviews_check && $reviews_check->num_rows > 0) {
    // Fetch all reviews for this seller's produk type
    $rev_stmt = $conn->prepare("
        SELECT r.*, 
               p.no_pesanan,
               (SELECT GROUP_CONCAT(pi.nama_produk SEPARATOR ', ') 
                FROM pesanan_item pi WHERE pi.pesanan_id = r.pesanan_id LIMIT 1) AS produk_names
        FROM reviews r
        LEFT JOIN pesanan p ON r.pesanan_id = p.id
        WHERE r.usahawan_id = ? AND r.type = 'produk'
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $rev_stmt->bind_param("i", $produk['usahawan_id']);
    $rev_stmt->execute();
    $rev_result = $rev_stmt->get_result();
    while ($rev = $rev_result->fetch_assoc()) {
        $reviews_list[] = $rev;
        if (isset($rating_breakdown[$rev['rating']])) {
            $rating_breakdown[$rev['rating']]++;
        }
    }
}

/* ===============================
   PRODUK LAIN OLEH USAHAWAN SAMA
================================ */
$stmt2 = $conn->prepare("
SELECT id, nama, harga, gambar_url
FROM produk
WHERE usahawan_id = ? AND id != ?
ORDER BY id DESC LIMIT 4
");
$stmt2->bind_param("ii", $produk['usahawan_id'], $produk['id']);
$stmt2->execute();
$produk_lain = $stmt2->get_result();

/* ===============================
   PRODUK BERKAITAN (KATEGORI SAMA)
================================ */
$stmt3 = $conn->prepare("
SELECT p.id, p.nama, p.harga, p.gambar_url, u.perniagaan
FROM produk p
LEFT JOIN usahawan u ON p.usahawan_id = u.id
WHERE p.kategori_id = ? AND p.id != ?
ORDER BY p.id DESC LIMIT 6
");
$stmt3->bind_param("ii", $produk['kategori_id'], $produk['id']);
$stmt3->execute();
$produk_berkaitan = $stmt3->get_result();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($produk['nama']) ?> – Sistem Usahawan Pahang</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800&family=Noto+Sans+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --blue:#1a56db;
  --blue-dark:#1346c0;
  --blue-light:#eff4ff;
  --blue-border:#c7d7fd;
  --text:#1e293b;
  --text-mid:#475569;
  --text-muted:#94a3b8;
  --border:#e8ecf2;
  --surface:#fff;
  --bg:#f4f6fb;
  --red:#ef4444;
  --green:#16a34a;
  --orange:#f59e0b;
  --radius:12px;
  --shadow:0 2px 16px rgba(26,86,219,.07);
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}

body {
  font-family:'Noto Sans',sans-serif;
  background:var(--bg);
  color:var(--text);
  padding-top:90px;
  font-size:14px;
}

a{text-decoration:none;color:inherit;}

.container{max-width:1160px;margin:0 auto;padding:24px 16px 48px;}

/* ── Breadcrumb ── */
.breadcrumb{font-size:12px;color:var(--text-muted);margin-bottom:18px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.breadcrumb a{color:var(--blue);}
.breadcrumb a:hover{text-decoration:underline;}
.breadcrumb .sep{color:#cbd5e1;}

/* ── Main product panel ── */
.product-panel{
  background:var(--surface);border-radius:16px;
  box-shadow:var(--shadow);
  display:grid;grid-template-columns:420px 1fr;
  gap:0;overflow:hidden;
  border:1px solid var(--border);
}

/* ═══ LEFT — IMAGE ═══ */
.product-gallery{padding:28px 24px;border-right:1px solid var(--border);}

.main-image-wrapper{position:relative;}
.main-image-box{
  background:#f8f9fb;border-radius:12px;
  border:1px solid var(--border);
  overflow:hidden;aspect-ratio:1;
  display:flex;align-items:center;justify-content:center;
}
.main-image-box img{
  width:100%;height:100%;object-fit:contain;
  padding:16px;cursor:zoom-in;
  transition:transform .4s ease;
}
.main-image-box img:hover{transform:scale(1.04);}

.nav-btn{
  position:absolute;top:50%;transform:translateY(-50%);
  background:rgba(255,255,255,.9);color:var(--text);
  border:1px solid var(--border);width:34px;height:34px;
  border-radius:50%;cursor:pointer;font-size:16px;
  backdrop-filter:blur(6px);transition:.2s;
  display:flex;align-items:center;justify-content:center;
  z-index:5;
}
.nav-btn.left{left:8px;}
.nav-btn.right{right:8px;}
.nav-btn:hover{background:var(--blue);color:#fff;border-color:var(--blue);}

.mini-gallery{display:flex;gap:8px;margin-top:12px;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin;}
.mini-gallery::-webkit-scrollbar{height:4px;}
.mini-gallery::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px;}
.mini-thumb{
  width:64px;height:64px;object-fit:contain;
  background:#f8f9fb;border-radius:8px;cursor:pointer;
  border:2px solid var(--border);flex-shrink:0;
  padding:4px;transition:border-color .2s,transform .2s;
}
.mini-thumb.active-thumb,.mini-thumb:hover{border-color:var(--blue);transform:scale(1.05);}

/* ═══ RIGHT — INFO ═══ */
.product-info{padding:28px 32px;display:flex;flex-direction:column;gap:0;}

/* Product name — large, e-commerce style */
.product-name{
  font-family:'Noto Sans Display',sans-serif;
  font-size:22px;font-weight:800;line-height:1.35;
  color:#0f172a;margin-bottom:10px;
  letter-spacing:-0.3px;
}

/* ── Rating row ── */
.rating-row{
  display:flex;align-items:center;gap:0;
  margin-bottom:14px;flex-wrap:wrap;
}
.rating-score{font-size:14px;font-weight:700;color:#f59e0b;margin-right:4px;}
.stars-display{display:flex;gap:1px;margin-right:8px;}
.stars-display i{font-size:12px;color:#f59e0b;}
.stars-display i.empty{color:#d1d5db;}
.rating-pipe{color:#d1d5db;margin:0 10px;font-size:13px;}
.rating-meta{font-size:13px;color:var(--text-mid);}
.rating-meta span{color:var(--blue-dark);font-weight:600;}

/* ── Price ── */
.price-wrap{
  background:linear-gradient(135deg,#f0f5ff,#fff);
  border-radius:10px;padding:14px 18px;
  margin-bottom:16px;border:1px solid var(--blue-border);
}
.price-main{display:flex;align-items:baseline;gap:4px;}
.price-rm{font-size:16px;font-weight:700;color:var(--red);line-height:1;}
.price-value{font-size:36px;font-weight:900;color:var(--red);line-height:1;letter-spacing:-1px;}
.price-cents{font-size:20px;font-weight:800;color:var(--red);}

/* ── Meta tags ── */
.meta-grid{
  display:grid;grid-template-columns:1fr 1fr;
  gap:8px;margin-bottom:16px;
}
.meta-item{
  display:flex;align-items:center;gap:8px;
  background:#f8fafc;border-radius:8px;
  padding:9px 12px;border:1px solid var(--border);
  font-size:13px;
}
.meta-item i{color:var(--blue);font-size:13px;width:14px;flex-shrink:0;}
.meta-label{color:var(--text-muted);font-size:11px;display:block;margin-bottom:1px;}
.meta-value{font-weight:600;color:var(--text);}
.meta-item.full{grid-column:1/-1;}
.stock-ok{color:var(--green)!important;}
.stock-out{color:var(--red)!important;}

/* ── Divider ── */
.divider{height:1px;background:var(--border);margin:14px 0;}

/* ── Description ── */
.desc-section h3{font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px;}
.desc-box{
  background:#f8fafc;border-left:3px solid var(--blue);
  border-radius:0 8px 8px 0;padding:13px 15px;
  font-size:13.5px;line-height:1.85;color:var(--text-mid);
  max-height:160px;overflow-y:auto;scrollbar-width:thin;
}
.desc-box::-webkit-scrollbar{width:4px;}
.desc-box::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px;}

/* ── Quantity ── */
.qty-section{margin-top:14px;}
.qty-section label{font-size:13px;font-weight:700;color:var(--text);display:block;margin-bottom:8px;}
.qty-wrap{display:flex;align-items:center;gap:0;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;width:fit-content;}
.qty-btn{
  width:36px;height:36px;border:none;background:#f1f5f9;
  color:var(--text);font-size:18px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .15s;font-weight:700;
}
.qty-btn:hover{background:var(--blue-light);color:var(--blue);}
.qty-input{
  width:52px;height:36px;border:none;border-left:1.5px solid var(--border);border-right:1.5px solid var(--border);
  text-align:center;font-size:14px;font-weight:700;font-family:'Noto Sans',sans-serif;
  color:var(--text);background:#fff;
}
.qty-input::-webkit-inner-spin-button,.qty-input::-webkit-outer-spin-button{-webkit-appearance:none;}
.stok-hint{font-size:12px;color:var(--text-muted);margin-top:5px;}

/* ── CTA Buttons ── */
.cta-group{display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;}

.btn-beli{
  flex:1;min-width:140px;
  padding:13px 20px;border-radius:10px;
  font-family:'Noto Sans',sans-serif;font-size:14px;font-weight:700;
  cursor:pointer;transition:all .2s;border:2px solid transparent;
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:var(--blue);color:#fff;
  box-shadow:0 3px 12px rgba(26,86,219,.25);
}
.btn-beli:hover{background:var(--blue-dark);transform:translateY(-1px);box-shadow:0 5px 18px rgba(26,86,219,.35);}

.btn-cart{
  flex:1;min-width:140px;
  padding:13px 20px;border-radius:10px;
  font-family:'Noto Sans',sans-serif;font-size:14px;font-weight:700;
  cursor:pointer;transition:all .2s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:#fff;color:var(--blue);
  border:2px solid var(--blue);
}
.btn-cart:hover{background:var(--blue-light);transform:translateY(-1px);box-shadow:0 4px 14px rgba(26,86,219,.15);}

.btn-disabled{
  flex:1;min-width:140px;
  padding:13px 20px;border-radius:10px;
  font-size:14px;font-weight:700;
  background:#f1f5f9;color:#94a3b8;
  border:2px solid #e2e8f0;cursor:not-allowed;
  display:flex;align-items:center;justify-content:center;gap:8px;
}

/* ═══ SELLER SECTION ═══ */
.seller-section{
  background:var(--surface);border-radius:16px;
  border:1px solid var(--border);
  box-shadow:var(--shadow);
  padding:20px 24px;margin-top:20px;
  display:flex;align-items:center;gap:16px;
  flex-wrap:wrap;
}
.seller-left{display:flex;align-items:center;gap:14px;flex:1;min-width:200px;}
.seller-avatar{
  width:60px;height:60px;border-radius:50%;
  object-fit:cover;border:3px solid var(--blue-light);
  flex-shrink:0;
}
.seller-name{font-size:16px;font-weight:800;color:#0f172a;margin-bottom:3px;}
.seller-meta{font-size:12px;color:var(--text-muted);display:flex;gap:12px;flex-wrap:wrap;}
.seller-meta span{display:flex;align-items:center;gap:4px;}
.seller-meta i{color:var(--blue);}
.seller-actions{display:flex;gap:10px;flex-wrap:wrap;margin-left:auto;}

.btn-chat-seller{
  padding:10px 18px;border-radius:8px;
  font-family:'Noto Sans',sans-serif;font-size:13px;font-weight:700;
  background:#25D366;color:#fff;border:none;cursor:pointer;
  display:flex;align-items:center;gap:7px;
  transition:all .2s;box-shadow:0 2px 8px rgba(37,211,102,.25);
}
.btn-chat-seller:hover{background:#1ebe5d;transform:translateY(-1px);}

.btn-view-shop{
  padding:10px 18px;border-radius:8px;
  font-family:'Noto Sans',sans-serif;font-size:13px;font-weight:700;
  background:var(--blue-light);color:var(--blue);
  border:2px solid var(--blue-border);cursor:pointer;
  display:flex;align-items:center;gap:7px;
  transition:all .2s;text-decoration:none;
}
.btn-view-shop:hover{background:var(--blue);color:#fff;border-color:var(--blue);}

/* ═══ REVIEWS SECTION ═══ */
.reviews-section{
  background:var(--surface);
  border-radius:16px;
  border:1px solid var(--border);
  box-shadow:var(--shadow);
  padding:28px 28px;
  margin-top:20px;
}

.reviews-top{
  display:grid;
  grid-template-columns:200px 1fr;
  gap:32px;
  align-items:center;
  padding-bottom:24px;
  border-bottom:1px solid var(--border);
  margin-bottom:24px;
}

.reviews-score-box{text-align:center;}

/* ⭐ BIG RATING NUMBER */
.reviews-big-score{
  font-family:'Noto Sans Display',sans-serif;
  font-size:56px;
  font-weight:900;
  color:#000; /* BLACK */
  line-height:1;
}

/* ⭐ BIG STARS */
.reviews-big-stars{display:flex;justify-content:center;gap:4px;margin:8px 0;} .reviews-big-stars i{font-size:18px;color:var(--orange);} .reviews-big-stars i.empty{color:#d1d5db;}

.reviews-total{
  font-size:13px;
  color:var(--text-muted);
}


/* Breakdown bars */
.breakdown-list{
  display:flex;
  flex-direction:column;
  gap:7px;
}

.breakdown-row{
  display:flex;
  align-items:center;
  gap:10px;
  font-size:13px;
}

/* ⭐ LEFT SIDE STAR LABEL */
.breakdown-star{
  width:52px;
  display:flex;
  align-items:center;
  gap:4px;
  color:#374151; /* dark grey */
  font-weight:600;
  flex-shrink:0;
}


.breakdown-bar-wrap{
  flex:1;
  height:8px;
  background:#e5e7eb;
  border-radius:10px;
  overflow:hidden;
}

/* ⭐ RATING BAR */
.breakdown-bar-fill{
  height:100%;
  border-radius:10px;
  background:#4b5563; /* dark grey bar */
  transition:width .6s ease;
}

.breakdown-count{
  width:28px;
  text-align:right;
  color:#374151; /* dark grey number */
  font-size:12px;
  flex-shrink:0;
}
 
/* Review cards */
.review-list{display:flex;flex-direction:column;gap:16px;}
.review-card{
  padding:18px 20px;background:#fafbfd;
  border:1px solid var(--border);border-radius:12px;
  transition:box-shadow .2s;
}
.review-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.06);}
.review-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap;}
.reviewer-info{display:flex;align-items:center;gap:10px;}
.reviewer-avatar{
  width:38px;height:38px;border-radius:50%;
  background:linear-gradient(135deg,var(--blue),#6366f1);
  color:#fff;font-size:15px;font-weight:800;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.reviewer-name{font-weight:700;font-size:14px;color:var(--text);}
.reviewer-date{font-size:11px;color:var(--text-muted);margin-top:2px;}
.review-stars i{
  font-size:13px;
  color:#374151; /* dark grey */
}
.review-stars i.empty{
  color:#d1d5db;
}
.review-produk-tag{
  font-size:11px;color:var(--blue);
  background:var(--blue-light);border:1px solid var(--blue-border);
  border-radius:20px;padding:3px 10px;font-weight:600;
  display:inline-flex;align-items:center;gap:4px;margin-bottom:8px;
  max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.review-komen{font-size:13.5px;color:var(--text-mid);line-height:1.75;}
.review-photos{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
.review-photo{
  width:72px;height:72px;border-radius:8px;
  object-fit:cover;border:1.5px solid var(--border);
  cursor:pointer;transition:transform .2s,border-color .2s;
}
.review-photo:hover{transform:scale(1.05);border-color:var(--blue);}
 
.reviews-empty{
  text-align:center;padding:48px 20px;color:var(--text-muted);
}
.reviews-empty i{font-size:3rem;color:#d1d5db;display:block;margin-bottom:12px;}
.reviews-empty p{font-size:14px;}
 
/* show-more */
.btn-show-more{
  display:flex;align-items:center;justify-content:center;gap:7px;
  width:100%;margin-top:20px;padding:12px;
  background:var(--blue-light);color:var(--blue);
  border:1.5px solid var(--blue-border);border-radius:10px;
  font-family:'Noto Sans',sans-serif;font-size:13px;font-weight:700;
  cursor:pointer;transition:.2s;
}
.btn-show-more:hover{background:var(--blue);color:#fff;}

/* ═══ PRODUK LAIN SECTION ═══ */
.section-block{margin-top:32px;}
.section-heading{
  display:flex;align-items:center;gap:12px;margin-bottom:18px;
}
.section-heading h2{font-size:17px;font-weight:800;color:#0f172a;white-space:nowrap;}
.section-heading::after{content:'';flex:1;height:1.5px;background:linear-gradient(to right,var(--border),transparent);border-radius:2px;}

.card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;}

.prod-card{
  background:var(--surface);border-radius:12px;
  border:1px solid var(--border);overflow:hidden;
  transition:all .22s;display:block;color:inherit;
}
.prod-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.1);border-color:#c7d7fd;}
.prod-card-img{width:100%;height:160px;background:#f8f9fb;display:flex;align-items:center;justify-content:center;overflow:hidden;}
.prod-card-img img{width:100%;height:100%;object-fit:contain;padding:10px;transition:transform .3s;}
.prod-card:hover .prod-card-img img{transform:scale(1.07);}
.prod-card-body{padding:12px 14px;}
.prod-card-name{font-size:13px;font-weight:600;color:var(--text);margin-bottom:5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;}
.prod-card-price{font-size:15px;font-weight:800;color:var(--blue);}

/* ═══ MODAL ═══ */
.modal{
  display:none;position:fixed;inset:0;
  background:rgba(15,23,42,.8);backdrop-filter:blur(5px);
  z-index:9999;align-items:center;justify-content:center;
}
.modal-content{
  background:#fff;width:88%;max-width:860px;height:80vh;
  display:grid;grid-template-columns:2fr 1fr;
  border-radius:18px;overflow:hidden;
  box-shadow:0 24px 60px rgba(0,0,0,.35);position:relative;
}
.modal-left{display:flex;align-items:center;justify-content:center;background:#f8f9fb;position:relative;}
.modal-left img{width:100%;height:100%;object-fit:contain;padding:20px;}
.modal-nav{
  position:absolute;top:50%;transform:translateY(-50%);
  background:var(--blue);color:#fff;border:none;
  width:38px;height:38px;border-radius:50%;cursor:pointer;font-size:18px;
  display:flex;align-items:center;justify-content:center;transition:.2s;
}
.modal-nav:hover{background:var(--blue-dark);}
.modal-nav.left{left:12px;}
.modal-nav.right{right:12px;}
.modal-right{overflow-y:auto;padding:12px;background:#f8f9fb;display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;align-content:start;}
.modal-thumb{width:80px;height:80px;object-fit:contain;background:#fff;cursor:pointer;border-radius:10px;border:2px solid transparent;padding:4px;transition:.2s;}
.modal-thumb.active-thumb,.modal-thumb:hover{border-color:var(--blue);}
.modal-close{position:absolute;top:12px;right:12px;width:34px;height:34px;border:none;border-radius:50%;background:#ef4444;color:#fff;font-size:15px;cursor:pointer;z-index:20;transition:.2s;display:flex;align-items:center;justify-content:center;}
.modal-close:hover{background:#dc2626;}

/* ── Toast ── */
.toast{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
  background:#1e293b;color:#fff;padding:12px 24px;border-radius:30px;
  font-size:13.5px;font-weight:600;opacity:0;pointer-events:none;
  transition:all .3s;z-index:9999;white-space:nowrap;
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.success{background:#16a34a;}
.toast.error{background:#ef4444;}

/* ── Responsive ── */
@media(max-width:900px){
  .product-panel{grid-template-columns:1fr;}
  .product-gallery{border-right:none;border-bottom:1px solid var(--border);}
  .modal-content{grid-template-columns:1fr;height:90vh;}
  .modal-right{grid-template-columns:repeat(4,1fr);max-height:110px;}
  .meta-grid{grid-template-columns:1fr;}
  .meta-item.full{grid-column:1;}
}
@media(max-width:560px){
  .cta-group{flex-direction:column;}
  .seller-section{flex-direction:column;align-items:flex-start;}
  .seller-actions{margin-left:0;width:100%;}
  .btn-chat-seller,.btn-view-shop{flex:1;justify-content:center;}
}
</style>
</head>
<body>

<div class="container">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="index.php">Laman Utama</a>
    <span class="sep">›</span>
    <a href="promosi-pasaran.php">Pasaran</a>
    <span class="sep">›</span>
    <a href="promosi-pasaran.php?kategori=<?= $produk['kategori_id'] ?>"><?= htmlspecialchars($produk['nama_kategori']) ?></a>
    <span class="sep">›</span>
    <span><?= htmlspecialchars($produk['nama']) ?></span>
  </nav>

  <!-- ═══════════════════════════════════════
       MAIN PRODUCT PANEL
  ════════════════════════════════════════ -->
  <div class="product-panel">

    <!-- LEFT: Gallery -->
    <div class="product-gallery">
      <div class="main-image-wrapper">
        <button class="nav-btn left" onclick="prevImage()"><i class="fas fa-chevron-left"></i></button>
        <button class="nav-btn right" onclick="nextImage()"><i class="fas fa-chevron-right"></i></button>
        <div class="main-image-box">
          <img id="mainImage" src="<?= htmlspecialchars($main_image) ?>" onclick="openModal()" alt="<?= htmlspecialchars($produk['nama']) ?>">
        </div>
      </div>
      <?php if (!empty($gallery_images)): ?>
      <div class="mini-gallery">
        <?php foreach ($gallery_images as $index => $img): ?>
          <img src="<?= htmlspecialchars($img) ?>" class="mini-thumb" data-index="<?= $index ?>" alt="Gambar <?= $index+1 ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Product Info -->
    <div class="product-info">

      <!-- Product Name -->
      <h1 class="product-name"><?= htmlspecialchars($produk['nama']) ?></h1>

      <!-- Rating row -->
      <div class="rating-row">
        <?php
          $avg   = $rating_data['avg'];
          $count = $rating_data['count'];
          $full  = floor($avg);
          $half  = ($avg - $full) >= 0.5 ? 1 : 0;
          $empty = 5 - $full - $half;
        ?>
        <span class="rating-score"><?= $avg > 0 ? $avg : '–' ?></span>
        <div class="stars-display">
          <?php for ($i=0;$i<$full;$i++): ?><i class="fas fa-star"></i><?php endfor; ?>
          <?php if ($half): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
          <?php for ($i=0;$i<$empty;$i++): ?><i class="far fa-star empty"></i><?php endfor; ?>
        </div>
        <span class="rating-pipe">|</span>
        <span class="rating-meta"><span><?= number_format($count) ?></span> ulasan</span>
        <span class="rating-pipe">|</span>
        <span class="rating-meta"><span><?= number_format($sold_count) ?></span> terjual</span>
      </div>

      <!-- Price -->
      <div class="price-wrap">
        <div class="price-main">
          <span class="price-rm">RM</span>
          <?php
            $harga_str = number_format($produk['harga'], 2);
            $parts     = explode('.', $harga_str);
          ?>
          <span class="price-value"><?= $parts[0] ?></span>
          <span class="price-cents">.<?= $parts[1] ?></span>
        </div>
      </div>

      <!-- Meta grid -->
      <div class="meta-grid">
        <div class="meta-item">
          <i class="fas fa-store"></i>
          <div>
            <span class="meta-label">Nama Perniagaan</span>
            <span class="meta-value"><?= htmlspecialchars($produk['perniagaan']) ?></span>
          </div>
        </div>
        <div class="meta-item">
          <i class="fas fa-tag"></i>
          <div>
            <span class="meta-label">Jenis Perniagaan</span>
            <span class="meta-value"><?= htmlspecialchars($produk['jenis']) ?></span>
          </div>
        </div>
        <div class="meta-item">
          <i class="fas fa-map-marker-alt"></i>
          <div>
            <span class="meta-label">Lokasi</span>
            <span class="meta-value"><?= htmlspecialchars($produk['lokasi']) ?></span>
          </div>
        </div>
        <div class="meta-item">
          <i class="fas fa-boxes"></i>
          <div>
            <span class="meta-label">Stok</span>
            <?php if ($produk['stok'] > 0): ?>
              <span class="meta-value stock-ok"><?= $produk['stok'] ?> unit tersedia</span>
            <?php else: ?>
              <span class="meta-value stock-out">Stok habis</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Description -->
      <div class="desc-section">
        <h3> Deskripsi Produk</h3>
        <div class="desc-box"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></div>
      </div>

      <div class="divider"></div>

      <!-- Quantity -->
      <?php if ($produk['stok'] > 0): ?>
      <div class="qty-section">
        <label> Kuantiti</label>
        <div class="qty-wrap">
          <button class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="<?= $produk['stok'] ?>">
          <button class="qty-btn" onclick="changeQty(1)">+</button>
        </div>
        <div class="stok-hint">Maksimum <?= $produk['stok'] ?> unit</div>
      </div>
      <?php endif; ?>

      <!-- CTA Buttons -->
      <div class="cta-group">
        <?php if ($produk['stok'] > 0): ?>
          <button class="btn-cart" onclick="doTambahCart()">
            <i class="fas fa-shopping-cart"></i> Tambah ke Troli
          </button>
          <button class="btn-beli" onclick="doBeliSekarang()"> Beli Sekarang </button>
        <?php else: ?>
          <div class="btn-disabled"><i class="fas fa-times-circle"></i> Stok Habis</div>
        <?php endif; ?>
      </div>

    </div><!-- /product-info -->
  </div><!-- /product-panel -->

  <!-- ═══════════════════════════════════════
       SELLER SECTION
  ════════════════════════════════════════ -->
  <div class="seller-section">
    <div class="seller-left">
      <img src="<?= htmlspecialchars($avatar) ?>" class="seller-avatar" alt="Avatar">
      <div>
        <div class="seller-name"><?= htmlspecialchars($produk['nama_usahawan']) ?></div>
        <div class="seller-meta">
          <span><i class="fas fa-store"></i><?= htmlspecialchars($produk['perniagaan']) ?></span>
          <span><i class="fas fa-calendar-alt"></i>Ahli sejak <?= date("M Y", strtotime($produk['tarikh_daftar'])) ?></span>
          <span><i class="fas fa-phone"></i><?= htmlspecialchars($produk['telefon']) ?></span>
        </div>
      </div>
    </div>

    <div class="seller-actions">
      <?php if (isset($_SESSION['usahawan_id']) && $_SESSION['usahawan_id'] != $produk['usahawan_id']): ?>
      <button class="btn-chat-seller" onclick="window.location.href='chat_room.php?user_id=<?= $produk['usahawan_id'] ?>'">
         Chat Usahawan
      </button>
      <?php endif; ?>
      <a href="profil_usahawan3.php?id=<?= $produk['usahawan_id'] ?>" class="btn-view-shop">
        <i class="fas fa-store"></i> Lihat Kedai
      </a>
    </div>
  </div>

<!-- Customer Reviews -->
  <div class="reviews-section">
    <div class="section-heading">
      <h2> Ulasan Pelanggan</h2>
    </div>
 
    <?php
      $total_reviews = count($reviews_list);
      $avg_score     = $rating_data['avg'];
      $avg_full      = floor($avg_score);
      $avg_half      = ($avg_score - $avg_full) >= 0.5 ? 1 : 0;
      $avg_empty     = 5 - $avg_full - $avg_half;
    ?>
 
    <?php if ($total_reviews > 0): ?>
 
    <!-- Score + breakdown -->
    <div class="reviews-top">
      <div class="reviews-score-box">
        <div class="reviews-big-score"><?= $avg_score ?></div>
        <div class="reviews-big-stars">
          <?php for($i=0;$i<$avg_full;$i++): ?><i class="fas fa-star"></i><?php endfor; ?>
          <?php if($avg_half): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
          <?php for($i=0;$i<$avg_empty;$i++): ?><i class="far fa-star empty"></i><?php endfor; ?>
        </div>
        <div class="reviews-total"><?= number_format($total_reviews) ?> ulasan</div>
      </div>
 
      <div class="breakdown-list">
        <?php for ($s = 5; $s >= 1; $s--):
          $cnt  = $rating_breakdown[$s];
          $pct  = $total_reviews > 0 ? ($cnt / $total_reviews * 100) : 0;
        ?>
        <div class="breakdown-row">
          <div class="breakdown-star">
            <?= $s ?> <i class="fas fa-star" style="font-size:11px;"></i>
          </div>
          <div class="breakdown-bar-wrap">
            <div class="breakdown-bar-fill" style="width:<?= round($pct) ?>%"></div>
          </div>
          <div class="breakdown-count"><?= $cnt ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>
 
    <!-- Review cards -->
    <div class="review-list" id="reviewList">
      <?php foreach (array_slice($reviews_list, 0, 5) as $rev):
        $r_full  = $rev['rating'];
        $r_empty = 5 - $r_full;
        $initials = mb_strtoupper(mb_substr($rev['pelanggan_nama'], 0, 1));
        $photos   = !empty($rev['gambar']) ? json_decode($rev['gambar'], true) : [];
      ?>
      <div class="review-card">
        <div class="review-card-top">
          <div class="reviewer-info">
            <div class="reviewer-avatar"><?= htmlspecialchars($initials) ?></div>
            <div>
              <div class="reviewer-name"><?= htmlspecialchars($rev['pelanggan_nama']) ?></div>
              <div class="reviewer-date"><i class="far fa-clock" style="margin-right:3px;"></i><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
            </div>
          </div>
          <div class="review-stars">
            <?php for($i=0;$i<$r_full;$i++): ?><i class="fas fa-star"></i><?php endfor; ?>
            <?php for($i=0;$i<$r_empty;$i++): ?><i class="far fa-star empty"></i><?php endfor; ?>
          </div>
        </div>
 
        <?php if (!empty($rev['produk_names'])): ?>
        <div class="review-produk-tag">
          <i class="fas fa-box" style="font-size:10px;"></i>
          <?= htmlspecialchars($rev['produk_names']) ?>
        </div>
        <?php endif; ?>
 
        <div class="review-komen"><?= nl2br(htmlspecialchars($rev['komen'])) ?></div>
 
        <?php if (!empty($photos)): ?>
        <div class="review-photos">
          <?php foreach ($photos as $photo): ?>
            <img src="uploads/reviews/<?= htmlspecialchars($photo) ?>"
                 class="review-photo"
                 onclick="openReviewPhoto(this.src)"
                 alt="Gambar ulasan"
                 onerror="this.style.display='none'">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Show more (if > 5 reviews) -->
    <?php if ($total_reviews > 5): ?>
    <button class="btn-show-more" id="btnShowMore" onclick="showMoreReviews()">
      <i class="fas fa-chevron-down"></i> Lihat Semua <?= $total_reviews ?> Ulasan
    </button>
    <?php endif; ?>
 
    <?php else: ?>
    <div class="reviews-empty">
      <i class="far fa-comment-dots"></i>
      <p>Belum ada ulasan untuk penjual ini.</p>
    </div>
    <?php endif; ?>
  </div>


  <!-- ═══════════════════════════════════════
       PRODUK LAIN OLEH PENJUAL SAMA
  ════════════════════════════════════════ -->
  <?php if ($produk_lain && $produk_lain->num_rows > 0): ?>
  <div class="section-block">
    <div class="section-heading">
      <h2><i class="fas fa-store" style="color:var(--blue);"></i> Produk Lain dari <?= htmlspecialchars($produk['perniagaan']) ?></h2>
    </div>
    <div class="card-grid">
      <?php while ($pl = $produk_lain->fetch_assoc()):
        $pimg = $pl['gambar_url'] ?: "default.png";
        if (strpos($pimg,'uploads/') === false) $pimg = "uploads/$pimg";
      ?>
      <a href="butiran_produk.php?id=<?= $pl['id'] ?>" class="prod-card">
        <div class="prod-card-img">
          <img src="<?= htmlspecialchars($pimg) ?>" alt="<?= htmlspecialchars($pl['nama']) ?>" onerror="this.src='assets/img/no-image.png'">
        </div>
        <div class="prod-card-body">
          <div class="prod-card-name"><?= htmlspecialchars($pl['nama']) ?></div>
          <div class="prod-card-price">RM <?= number_format($pl['harga'],2) ?></div>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════
       PRODUK BERKAITAN
  ════════════════════════════════════════ -->
  <?php if ($produk_berkaitan && $produk_berkaitan->num_rows > 0): ?>
  <div class="section-block">
    <div class="section-heading">
      <h2><i class="fas fa-layer-group" style="color:var(--blue);"></i> Produk Berkaitan</h2>
    </div>
    <div class="card-grid">
      <?php while ($pb = $produk_berkaitan->fetch_assoc()):
        $bimg = $pb['gambar_url'] ?: "default.png";
        if (strpos($bimg,'uploads/') === false) $bimg = "uploads/$bimg";
      ?>
      <a href="butiran_produk.php?id=<?= $pb['id'] ?>" class="prod-card">
        <div class="prod-card-img">
          <img src="<?= htmlspecialchars($bimg) ?>" alt="<?= htmlspecialchars($pb['nama']) ?>" onerror="this.src='assets/img/no-image.png'">
        </div>
        <div class="prod-card-body">
          <div class="prod-card-name"><?= htmlspecialchars($pb['nama']) ?></div>
          <div class="prod-card-price">RM <?= number_format($pb['harga'],2) ?></div>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- Modal -->
<div id="imageModal" class="modal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    <div class="modal-left">
      <button class="modal-nav left" onclick="prevImage()"><i class="fas fa-chevron-left"></i></button>
      <img id="modalMainImage" alt="Modal Image">
      <button class="modal-nav right" onclick="nextImage()"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="modal-right" id="modalThumbList"></div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const PRODUK_ID   = <?= (int)$produk['id'] ?>;
const PRODUK_NAMA = <?= json_encode($produk['nama']) ?>;
const PRODUK_HARGA= <?= (float)$produk['harga'] ?>;
const PRODUK_IMG  = <?= json_encode($produk['gambar_url']) ?>;
const STOK_MAX    = <?= (int)$produk['stok'] ?>;

/* ── Toast ── */
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show ' + type;
  setTimeout(() => { t.className = 'toast'; }, 3000);
}

/* ── Quantity ── */
function changeQty(delta) {
  const inp = document.getElementById('qtyInput');
  if (!inp) return;
  let v = parseInt(inp.value) + delta;
  if (v < 1) v = 1;
  if (v > STOK_MAX) v = STOK_MAX;
  inp.value = v;
}

function getQty() {
  const inp = document.getElementById('qtyInput');
  return inp ? Math.max(1, Math.min(STOK_MAX, parseInt(inp.value) || 1)) : 1;
}

/* ── Add to cart ── */
async function doTambahCart() {
  const qty = getQty();
  try {
    const resp = await fetch('add_to_cart.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        produk_id: PRODUK_ID,
        nama: PRODUK_NAMA,
        harga: PRODUK_HARGA,
        gambar_url: PRODUK_IMG,
        kuantiti: qty
      })
    });
    const data = await resp.json();
    if (data.success) {
      showToast('🛒 Produk berjaya dimasukkan ke troli!', 'success');
    } else {
      showToast('⚠️ Gagal menambah ke troli.', 'error');
    }
  } catch(e) {
    showToast('❌ Ralat: ' + e.message, 'error');
  }
}

/* ── Buy now ── */
async function doBeliSekarang() {
  const qty = getQty();
  try {
    const resp = await fetch('add_to_cart.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        produk_id: PRODUK_ID,
        nama: PRODUK_NAMA,
        harga: PRODUK_HARGA,
        gambar_url: PRODUK_IMG,
        kuantiti: qty
      })
    });
    const data = await resp.json();
    if (data.success) {
      window.location.href = 'checkout.php';
    } else {
      showToast('⚠️ Gagal memproses. Sila cuba lagi.', 'error');
    }
  } catch(e) {
    showToast('❌ Ralat: ' + e.message, 'error');
  }
}

/* ── Image gallery ── */
document.addEventListener("DOMContentLoaded", function () {
  const images = <?= json_encode(!empty($gallery_images) ? $gallery_images : [$gambar]) ?>;
  const mainImage       = document.getElementById("mainImage");
  const modal           = document.getElementById("imageModal");
  const modalMainImage  = document.getElementById("modalMainImage");
  const modalThumbList  = document.getElementById("modalThumbList");

  let currentIndex = 0;

  function updateImage() {
    mainImage.src = images[currentIndex];
    modalMainImage.src = images[currentIndex];
    document.querySelectorAll(".mini-thumb").forEach((t,i) => {
      t.classList.toggle("active-thumb", i === currentIndex);
    });
    if (modal.style.display === "flex") generateModalThumbs();
  }

  document.querySelectorAll(".mini-thumb").forEach((thumb, index) => {
    thumb.addEventListener("mouseenter", () => { currentIndex = index; updateImage(); });
    thumb.addEventListener("click", () => openModal());
  });

  window.prevImage = () => { currentIndex = (currentIndex - 1 + images.length) % images.length; updateImage(); };
  window.nextImage = () => { currentIndex = (currentIndex + 1) % images.length; updateImage(); };

  window.openModal = () => {
    modal.style.display = "flex";
    modalMainImage.src = images[currentIndex];
    generateModalThumbs();
  };
  window.closeModal = () => { modal.style.display = "none"; };

  function generateModalThumbs() {
    modalThumbList.innerHTML = "";
    images.forEach((img, index) => {
      const t = document.createElement("img");
      t.src = img; t.className = "modal-thumb";
      if (index === currentIndex) t.classList.add("active-thumb");
      t.onclick = () => { currentIndex = index; updateImage(); generateModalThumbs(); };
      modalThumbList.appendChild(t);
    });
  }

  modal.addEventListener("click", e => { if (e.target === modal) closeModal(); });

  // Keyboard nav
  document.addEventListener("keydown", e => {
    if (modal.style.display !== "flex") return;
    if (e.key === "ArrowLeft") prevImage();
    if (e.key === "ArrowRight") nextImage();
    if (e.key === "Escape") closeModal();
  });

  updateImage();
});
</script>

<?php include "footer.php"; ?>
</body>
</html>