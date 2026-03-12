<?php
session_start();
include "connection.php";

// ── Auth ───────────────────────────────────────────────────────────────────
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$buyer_id = $_SESSION['usahawan_id'];

// ── Buyer info ─────────────────────────────────────────────────────────────
$userStmt = $conn->prepare("SELECT nama FROM usahawan WHERE id = ?");
$userStmt->bind_param("i", $buyer_id);
$userStmt->execute();
$buyer = $userStmt->get_result()->fetch_assoc();
if (!$buyer) die("Maklumat pengguna tidak ditemui.");

// ── Validate: order must belong to buyer and be delivered ──────────────────
$pesanan_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$stmt = $conn->prepare(
    "SELECT p.* FROM pesanan p
     WHERE p.id = ? AND p.usahawan_id = ? AND p.status_pesanan = 'delivered'
     LIMIT 1"
);
$stmt->bind_param("ii", $pesanan_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<script>alert('Pesanan tidak dijumpai atau belum selesai.'); window.location.href='pesanan_detail.php';</script>";
    exit;
}

// ── Load items (display only) ──────────────────────────────────────────────
$items_stmt = $conn->prepare(
    "SELECT pi.*, pr.nama, pr.gambar_url, pr.harga
     FROM pesanan_item pi
     INNER JOIN produk pr ON pi.produk_id = pr.id
     WHERE pi.pesanan_id = ? ORDER BY pi.id ASC"
);
$items_stmt->bind_param("i", $pesanan_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Check if already reviewed ──────────────────────────────────────────────
function alreadyReviewed($conn, $pesanan_id, $buyer_id) {
    $chk = $conn->query("SHOW TABLES LIKE 'reviews'");
    if (!$chk || $chk->num_rows === 0) return false;
    $s = $conn->prepare(
        "SELECT id FROM reviews
         WHERE type='produk' AND pesanan_id=? AND usahawan_pembeli_id=? LIMIT 1"
    );
    $s->bind_param("ii", $pesanan_id, $buyer_id);
    $s->execute();
    return $s->get_result()->num_rows > 0;
}

$already_reviewed = alreadyReviewed($conn, $pesanan_id, $buyer_id);

// ── Handle POST ────────────────────────────────────────────────────────────
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_reviewed) {

    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $komen  = trim($_POST['komen'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Sila pilih rating bintang terlebih dahulu.';
    } elseif (strlen($komen) < 10) {
        $error = 'Ulasan terlalu pendek (minimum 10 aksara).';
    } else {

        // ── Photo uploads ──────────────────────────────────────────────────
        $uploaded  = [];
        $uploadDir = "uploads/reviews/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (!empty($_FILES['gambar']['name'][0])) {
            foreach ($_FILES['gambar']['tmp_name'] as $k => $tmp) {
                if ($_FILES['gambar']['error'][$k] !== 0) continue;
                $ext = strtolower(pathinfo($_FILES['gambar']['name'][$k], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
                $fname = uniqid("rev_prod_") . ".$ext";
                if (move_uploaded_file($tmp, $uploadDir . $fname)) {
                    $uploaded[] = $fname;
                    if (count($uploaded) >= 5) break;
                }
            }
        }
        $gambar_json = !empty($uploaded) ? json_encode($uploaded) : null;

        // ── Determine seller_id from first item ────────────────────────────
        $seller_id = 0;
        if (!empty($items)) {
            $sid_stmt = $conn->prepare("SELECT usahawan_id FROM produk WHERE id = ? LIMIT 1");
            $pid_val  = (int)$items[0]['produk_id'];
            $sid_stmt->bind_param("i", $pid_val);
            $sid_stmt->execute();
            $sid_row   = $sid_stmt->get_result()->fetch_assoc();
            $seller_id = $sid_row ? (int)$sid_row['usahawan_id'] : 0;
        }

        // ── Insert ─────────────────────────────────────────────────────────
        $ins = $conn->prepare(
            "INSERT INTO reviews
             (type, pesanan_id, usahawan_id, usahawan_pembeli_id,
              pelanggan_nama, rating, komen, gambar, created_at)
             VALUES ('produk', ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $ins->bind_param("iiisiss",
            $pesanan_id, $seller_id, $buyer_id,
            $buyer['nama'], $rating, $komen, $gambar_json
        );

        if ($ins->execute()) {
            $success          = true;
            $already_reviewed = true;
        } else {
            $error = 'Ralat semasa menyimpan ulasan. Sila cuba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tulis Ulasan – Sistem Usahawan Pahang</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --navy:#001f3f;--blue:#003399;--accent:#0055cc;
  --success:#16a34a;--success-bg:#dcfce7;
  --danger:#dc2626;--danger-bg:#fee2e2;
  --surface:#ffffff;--surface2:#f8fafd;--border:#e2e8f0;
  --text:#0f172a;--text-mid:#334155;--text-muted:#64748b;
  --radius-lg:16px;--radius-md:10px;
  --shadow:0 4px 24px rgba(0,31,63,.08);
  --tr:.25s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}

body{
  font-family:'DM Sans',sans-serif;background:#eef2f7;
  color:var(--text);margin-top:90px;min-height:100vh;
}
body::after{
  content:"";position:fixed;inset:0;
  background-image:url("assets/img/jatapahang.png");
  background-repeat:repeat;background-size:160px;
  opacity:.04;z-index:-1;pointer-events:none;
}

.wrapper{max-width:620px;margin:36px auto 80px;padding:0 18px;}

@keyframes fadeUp{
  from{opacity:0;transform:translateY(14px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── Order strip ── */
.order-strip{
  background:var(--surface);border-radius:var(--radius-lg);
  border:1px solid var(--border);box-shadow:var(--shadow);
  padding:15px 20px;margin-bottom:18px;
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;
  animation:fadeUp .35s ease both;
}
.strip-left{display:flex;align-items:center;gap:10px;}
.strip-icon{width:36px;height:36px;border-radius:8px;background:#e8f0fb;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.strip-no{font-weight:700;font-size:.93rem;color:var(--navy);}
.strip-date{font-size:.76rem;color:var(--text-muted);margin-top:2px;}
.badge-done{background:var(--success-bg);color:var(--success);border:1px solid #86efac;border-radius:20px;padding:4px 12px;font-size:.71rem;font-weight:700;display:flex;align-items:center;gap:5px;}

/* ── Main card ── */
.main-card{
  background:var(--surface);border-radius:var(--radius-lg);
  border:1px solid var(--border);box-shadow:var(--shadow);
  overflow:hidden;
  animation:fadeUp .4s ease both;
}

.card-header{
  background:var(--surface2);border-bottom:1px solid var(--border);
  padding:16px 24px;display:flex;align-items:center;gap:10px;
}
.hdr-num{width:30px;height:30px;background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0;}
.hdr-title{font-size:.93rem;font-weight:700;color:var(--navy);}
.hdr-sub{font-size:.76rem;color:var(--text-muted);}

.card-body{padding:26px;}

/* ── Items list ── */
.items-list{margin-bottom:22px;}
.item-row{
  display:flex;align-items:center;gap:12px;
  padding:11px 13px;background:var(--surface2);
  border:1px solid var(--border);border-radius:var(--radius-md);
  margin-bottom:8px;
}
.item-img{width:52px;height:52px;border-radius:8px;object-fit:cover;border:1.5px solid var(--border);flex-shrink:0;}
.item-img-ph{width:52px;height:52px;border-radius:8px;background:#e8f0fb;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.item-name{font-weight:600;font-size:.88rem;color:var(--navy);}
.item-meta{font-size:.76rem;color:var(--text-muted);margin-top:2px;}

/* ── Section label ── */
.sec-label{font-size:.77rem;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:.5px;margin-bottom:9px;display:flex;align-items:center;gap:6px;}
.sec-label i{color:var(--accent);font-size:.74rem;}
.sec-label .opt{font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0;font-size:.72rem;}

.divider{border:none;border-top:1px solid var(--border);margin:20px 0;}

/* ── Stars ── */
.stars-wrap{margin-bottom:18px;}
.stars{display:flex;gap:6px;margin-bottom:5px;}
.stars input{display:none;}
.stars label{font-size:34px;cursor:pointer;color:#d1dbe8;transition:color .14s,transform .14s;line-height:1;}
.stars label.on{color:#f59e0b;}
.stars label:hover{transform:scale(1.1);}
.star-hint{font-size:.77rem;color:var(--text-muted);min-height:17px;}

/* ── Textarea ── */
textarea.review-ta{
  width:100%;min-height:120px;padding:12px 15px;
  border:2px solid var(--border);border-radius:var(--radius-md);
  font-family:'DM Sans',sans-serif;font-size:.91rem;
  color:var(--text);line-height:1.6;resize:vertical;
  background:var(--surface2);transition:all var(--tr);
}
textarea.review-ta::placeholder{color:#b0bec5;}
textarea.review-ta:focus{outline:none;border-color:var(--accent);background:#fff;box-shadow:0 0 0 4px rgba(0,85,204,.1);}
.char-count{text-align:right;font-size:.7rem;color:var(--text-muted);margin-top:4px;}

/* ── Upload zone ── */
.upload-zone{
  border:2px dashed #c8d6e8;border-radius:var(--radius-md);
  padding:20px;text-align:center;cursor:pointer;
  background:linear-gradient(135deg,#f0f6ff,#f8faff);
  transition:all var(--tr);position:relative;
}
.upload-zone:hover,.upload-zone.drag-over{border-color:var(--accent);background:#eef4ff;}
.upload-zone input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.uz-icon{font-size:1.8rem;color:#93b4d8;margin-bottom:6px;transition:color var(--tr);}
.upload-zone:hover .uz-icon{color:var(--accent);}
.uz-title{font-size:.84rem;font-weight:700;color:var(--navy);margin-bottom:2px;}
.uz-sub{font-size:.73rem;color:var(--text-muted);}
.uz-tags{display:flex;justify-content:center;gap:5px;margin-top:8px;}
.uz-tags span{background:#e0ecff;color:var(--accent);border-radius:20px;padding:2px 8px;font-size:.67rem;font-weight:700;}

/* Photo preview */
.preview-wrap{display:none;}
.preview-wrap.visible{display:block;}
.preview-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(78px,1fr));gap:8px;}
.photo-tile{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;border:2px solid var(--success);}
.photo-tile img{width:100%;height:100%;object-fit:cover;display:block;}
.ph-remove{position:absolute;top:4px;right:4px;width:21px;height:21px;background:rgba(220,38,38,.85);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.56rem;cursor:pointer;z-index:2;transition:background var(--tr);}
.ph-remove:hover{background:var(--danger);}
.photo-add-tile{aspect-ratio:1;border-radius:8px;border:2px dashed #c8d6e8;background:var(--surface2);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:pointer;transition:all var(--tr);position:relative;font-size:.67rem;font-weight:700;color:var(--text-muted);}
.photo-add-tile:hover{border-color:var(--accent);color:var(--accent);background:#eef4ff;}
.photo-add-tile i{font-size:1.1rem;}
.photo-add-tile input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}

/* ── Alert ── */
.alert-err{
  display:flex;align-items:center;gap:10px;
  padding:12px 15px;background:var(--danger-bg);
  border:1px solid #fca5a5;border-radius:var(--radius-md);
  font-size:.84rem;color:var(--danger);font-weight:500;
  margin-bottom:20px;
}

/* ── Actions ── */
.form-actions{display:flex;justify-content:flex-end;align-items:center;gap:14px;margin-top:6px;}
.btn-cancel{font-size:.85rem;color:var(--text-muted);text-decoration:none;font-weight:600;transition:color var(--tr);}
.btn-cancel:hover{color:var(--navy);}
.btn-submit{
  padding:12px 28px;
  background:linear-gradient(135deg,#16a34a,#22c55e);
  color:#fff;border:none;border-radius:var(--radius-md);
  font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:800;
  cursor:pointer;display:flex;align-items:center;gap:8px;
  transition:all var(--tr);box-shadow:0 4px 14px rgba(34,197,94,.35);
}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(34,197,94,.4);}
.btn-submit:disabled{opacity:.65;cursor:not-allowed;transform:none;}

/* ── Success ── */
.success-body{padding:50px 28px;text-align:center;}
.success-ring{width:68px;height:68px;border-radius:50%;background:var(--success-bg);border:3px solid #86efac;display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:var(--success);margin:0 auto 16px;}
.success-body h3{font-family:'DM Serif Display',serif;font-size:1.3rem;color:var(--navy);margin-bottom:7px;}
.success-body p{font-size:.86rem;color:var(--text-muted);margin-bottom:26px;line-height:1.6;}
.btn-return{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border:2px solid var(--border);border-radius:var(--radius-md);font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:700;color:var(--text-mid);text-decoration:none;transition:all var(--tr);}
.btn-return:hover{border-color:var(--navy);color:var(--navy);}

@media(max-width:540px){
  .order-strip{flex-direction:column;align-items:flex-start;}
  .form-actions{flex-direction:column;align-items:stretch;}
  .btn-submit{justify-content:center;}
}

/* ── Header ── */
header{
  background:linear-gradient(135deg,#001F3F 0%,#003399 15%,#0066FF 40%,#99CCFF 60%,#003399 80%,#001F3F 100%);
  animation:metalshine 6s linear infinite;
  padding:15px 20px;position:fixed;top:0;left:0;width:100%;z-index:1000;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 4px 15px rgba(0,0,0,.1);flex-wrap:wrap;
}
@keyframes metalshine{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
header img.jata{height:55px;}
header .title{
  font-size:1.5rem;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
  background:linear-gradient(90deg,#e6e6e6 0%,#bfbfbf 50%,#f2f2f2 100%);
  background-clip:text;-webkit-background-clip:text;color:transparent;-webkit-text-fill-color:transparent;
  position:relative;overflow:hidden;
}
header .title::after{
  content:"";position:absolute;top:0;left:-75%;width:50%;height:100%;
  background:linear-gradient(120deg,rgba(255,255,255,0) 0%,rgba(255,255,255,.55) 50%,rgba(255,255,255,0) 100%);
  animation:textshine 4s linear infinite;
}
@keyframes textshine{0%{left:-75%}100%{left:125%}}
nav{display:flex;gap:14px;}
nav a{color:#fff;padding:8px 12px;font-weight:500;text-decoration:none;transition:.3s;}
nav a:hover{color:#ffd700;}
.menu-toggle{display:none;font-size:1.8rem;cursor:pointer;background:none;border:none;color:#fff;}
@media(max-width:768px){
  .menu-toggle{display:block;}
  nav{display:none;flex-direction:column;background:linear-gradient(135deg,#001F3F,#003399,#0066FF);padding:15px;border-radius:10px;margin-top:12px;width:100%;}
  nav.show{display:flex;}
  nav a{text-align:center;padding:10px;}
  .title{font-size:1.1rem;}
}

/* ── Footer ── */
footer{
  background:linear-gradient(135deg,#001F3F 0%,#003399 15%,#0066FF 40%,#99CCFF 60%,#003399 80%,#001F3F 100%);
  animation:metalshine 6s linear infinite;
  color:#fff;padding:30px 20px;margin-top:40px;text-align:center;
}
footer .footer-content{max-width:1100px;margin:auto;}
footer img.footer-logo{height:55px;margin-bottom:12px;filter:drop-shadow(0 3px 5px rgba(0,0,0,.4));}
footer p,footer strong{color:#f8f8f8;font-weight:600;letter-spacing:.4px;}
footer .copyright{margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.2);font-size:.83rem;color:#ddd;}

</style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="document.getElementById('navMenu').classList.toggle('show')">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="pesanan_detail.php"><strong>Pesanan Saya</strong></a>
  </nav>
</header>

<div class="wrapper">

  <!-- Order strip -->
  <div class="order-strip">
    <div class="strip-left">
      <div class="strip-icon"><i class="fas fa-receipt" style="color:#003399"></i></div>
      <div>
        <div class="strip-no"><?= htmlspecialchars($order['no_pesanan']) ?></div>
        <div class="strip-date">
          <i class="far fa-calendar-alt"></i>
          <?= date('d M Y', strtotime($order['tarikh_pesanan'])) ?>
        </div>
      </div>
    </div>
    <div class="badge-done"><i class="fas fa-check"></i> Pesanan Selesai</div>
  </div>

  <!-- Main card -->
  <div class="main-card">

    <div class="card-header">
      <div class="hdr-num"><i class="fas fa-star" style="font-size:.65rem"></i></div>
      <div>
        <div class="hdr-title">Tulis Ulasan</div>
        <div class="hdr-sub">Pesanan #<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?></div>
      </div>
    </div>

    <div class="card-body">

      <!-- Already reviewed OR just submitted -->
      <?php if ($already_reviewed): ?>
      <div class="success-body">
        <div class="success-ring">
          <?= $success ? '<i class="fas fa-check"></i>' : '<i class="fas fa-star"></i>' ?>
        </div>
        <h3><?= $success ? 'Ulasan Berjaya Dihantar!' : 'Ulasan Telah Dihantar' ?></h3>
        <p>
          <?= $success
            ? 'Terima kasih kerana berkongsi pengalaman anda. Ulasan anda membantu pembeli lain.'
            : 'Anda telah memberikan ulasan untuk pesanan ini sebelum ini.' ?>
        </p>
        <a href="pesanan_detail.php" class="btn-return">
          <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
        </a>
      </div>

      <?php else: ?>

      <!-- Products in this order (display only) -->
      <div class="items-list">
        <div class="sec-label"><i class="fas fa-box"></i> Produk dalam Pesanan Ini</div>
        <?php foreach ($items as $item): ?>
        <div class="item-row">
          <?php if (!empty($item['gambar_url'])): ?>
            <img class="item-img"
                 src="<?= htmlspecialchars('uploads/' . $item['gambar_url']) ?>"
                 alt="<?= htmlspecialchars($item['nama']) ?>"
                 onerror="this.style.display='none'">
          <?php else: ?>
            <div class="item-img-ph">📦</div>
          <?php endif; ?>
          <div>
            <div class="item-name"><?= htmlspecialchars($item['nama_produk'] ?? $item['nama']) ?></div>
            <div class="item-meta">
              RM <?= number_format($item['harga'], 2) ?> × <?= (int)$item['kuantiti'] ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <hr class="divider">

      <!-- Error alert -->
      <?php if ($error): ?>
      <div class="alert-err">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="ulasanForm" novalidate>

        <!-- Star rating -->
        <div class="stars-wrap">
          <div class="sec-label"> Rating </div>
          <div class="stars" id="starsRow">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <input type="radio" name="rating" id="s<?= $i ?>" value="<?= $i ?>">
            <label for="s<?= $i ?>" data-v="<?= $i ?>">★</label>
            <?php endfor; ?>
          </div>
          <div class="star-hint" id="starHint"></div>
        </div>

        <hr class="divider">

        <!-- Comment -->
        <div style="margin-bottom:18px;">
          <div class="sec-label"> Review</div>
          <textarea class="review-ta" name="komen" id="komen" maxlength="600"
            placeholder="Kongsikan pengalaman anda tentang pesanan ini…"
            oninput="document.getElementById('cn').textContent=this.value.length"
          ></textarea>
          <div class="char-count"><span id="cn">0</span> / 600</div>
        </div>

        <hr class="divider">

        <!-- Photo upload -->
        <div style="margin-bottom:4px;">
          <div class="sec-label"> Gambar <span class="opt">(Optional)</span>
          </div>

          <div class="upload-zone" id="uploadZone">
            <input type="file" name="gambar[]" id="photoInput"
                   accept="image/jpeg,image/png,image/webp" multiple
                   onchange="handlePhotos(event)">
            <div class="uz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="uz-title">Klik atau seret gambar ke sini</div>
            <div class="uz-sub">Kongsi gambar produk yang diterima</div>
            <div class="uz-tags"><span>JPG</span><span>PNG</span><span>WEBP</span></div>
          </div>

          <div class="preview-wrap" id="previewWrap">
            <div class="preview-grid" id="previewGrid"></div>
          </div>
        </div>

        <hr class="divider">

        <div class="form-actions">
          <a href="pesanan_detail.php?order_id=<?= $pesanan_id ?>" class="btn-cancel">Batal</a>
          <button type="submit" class="btn-submit" id="submitBtn"> Hantar </button>
        </div>

      </form>

      <?php endif; ?>

    </div><!-- card-body -->
  </div><!-- main-card -->

</div><!-- wrapper -->

<script>
/* ── Stars ── */
const hints  = ['','Sangat Lemah','Kurang Memuaskan','Memuaskan','Bagus','Cemerlang'];
const lbls   = document.querySelectorAll('#starsRow label');
const hint   = document.getElementById('starHint');
let picked   = 0;

lbls.forEach(l => {
  l.addEventListener('mouseenter', () => paint(+l.dataset.v));
  l.addEventListener('mouseleave', () => paint(picked));
  l.addEventListener('click', () => {
    picked = +l.dataset.v; paint(picked);
    hint.textContent = hints[picked];
    hint.style.color = picked >= 4 ? '#16a34a' : picked === 3 ? '#d97706' : '#dc2626';
  });
});
function paint(v) { lbls.forEach(l => l.classList.toggle('on', +l.dataset.v <= v)); }

/* ── Photos ── */
const MAX  = 5;
let files  = [];

function handlePhotos(e) {
  Array.from(e.target.files).forEach(f => {
    if (files.length < MAX && !files.find(x => x.name===f.name && x.size===f.size))
      files.push(f);
  });
  render(); sync(); toggle();
}
function removePhoto(i) { files.splice(i,1); render(); sync(); toggle(); }

function sync() {
  const dt = new DataTransfer();
  files.forEach(f => dt.items.add(f));
  document.getElementById('photoInput').files = dt.files;
}
function toggle() {
  const has = files.length > 0;
  document.getElementById('uploadZone').style.display = has ? 'none' : '';
  document.getElementById('previewWrap').classList.toggle('visible', has);
}
function render() {
  const grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  files.forEach((f,i) => {
    const r = new FileReader();
    r.onload = e => {
      const t = document.createElement('div');
      t.className = 'photo-tile';
      t.innerHTML = `<img src="${e.target.result}">
        <div class="ph-remove" onclick="removePhoto(${i})"><i class="fas fa-times"></i></div>`;
      const add = grid.querySelector('.photo-add-tile');
      grid.insertBefore(t, add || null);
    };
    r.readAsDataURL(f);
  });
  if (files.length < MAX) {
    const a = document.createElement('div');
    a.className = 'photo-add-tile';
    a.innerHTML = `<i class="fas fa-plus"></i><span>Tambah</span>
      <input type="file" accept="image/jpeg,image/png,image/webp" multiple onchange="handlePhotos(event)">`;
    grid.appendChild(a);
  }
}

/* Drag effects */
const zone = document.getElementById('uploadZone');
zone?.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone?.addEventListener('drop',      () => zone.classList.remove('drag-over'));

/* ── Validation ── */
document.getElementById('ulasanForm')?.addEventListener('submit', e => {
  if (!picked) {
    e.preventDefault();
    hint.textContent = 'Sila pilih rating terlebih dahulu.';
    hint.style.color = '#dc2626';
    document.getElementById('starsRow').scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }
  const ta = document.getElementById('komen');
  if (ta.value.trim().length < 10) {
    e.preventDefault();
    ta.style.borderColor = '#dc2626';
    ta.focus();
    return;
  }
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghantar…';
});
</script>

<?php include 'footer.php'; ?>

</body>
</html>