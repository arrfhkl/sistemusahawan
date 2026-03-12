<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Supports ?order_id=X (from order list) OR shows all orders for the user
$single_mode = isset($_GET['order_id']);
$order_id    = $single_mode ? (int)$_GET['order_id'] : 0;

// ── Fetch order(s) ─────────────────────────────────────────────────────────
if ($single_mode) {
    $sql = "SELECT p.*
            FROM pesanan p
            WHERE p.id = ? AND p.usahawan_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $usahawan_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($orders)) {
        echo "<script>alert('Pesanan tidak dijumpai atau akses tidak dibenarkan.'); window.location.href='pesanan_detail.php';</script>";
        exit;
    }
} else {
    $sql = "SELECT p.*
            FROM pesanan p
            WHERE p.usahawan_id = ?
            ORDER BY p.tarikh_pesanan DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usahawan_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function translateStatus($s) {
    return ['pending'=>'Menunggu','processing'=>'Diproses','shipped'=>'Dihantar',
            'delivered'=>'Selesai','cancelled'=>'Dibatalkan','paid'=>'Dibayar','failed'=>'Gagal'][$s] ?? $s;
}
function statusClass($s) {
    return ['pending'=>'warning','processing'=>'info','shipped'=>'primary',
            'delivered'=>'success','cancelled'=>'danger','paid'=>'success','failed'=>'danger'][$s] ?? 'secondary';
}
function getOrderItems($conn, $pesanan_id) {
    $stmt = $conn->prepare(
        "SELECT pi.*, pr.nama, pr.gambar_url, pr.harga
         FROM pesanan_item pi
         INNER JOIN produk pr ON pi.produk_id = pr.id
         WHERE pi.pesanan_id = ? ORDER BY pi.id ASC");
    $stmt->bind_param("i", $pesanan_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function hasReviewed($conn, $pesanan_id, $usahawan_id) {
    // One review per order — check reviews table (type='produk')
    $check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if (!$check || $check->num_rows === 0) return false;

    $s = $conn->prepare(
        "SELECT id FROM reviews
         WHERE type='produk' AND pesanan_id=? AND usahawan_pembeli_id=? LIMIT 1"
    );
    $s->bind_param("ii", $pesanan_id, $usahawan_id);
    $s->execute();
    return $s->get_result()->num_rows > 0;
}

// Step definitions for progress tracker
$step_keys = ['pending','processing','shipped','delivered'];
$step_meta = [
    'pending'    => ['label'=>'Pesanan Dibuat',    'icon'=>'fa-file-alt',    'desc'=>'Pesanan anda telah berjaya dihantar'],
    'processing' => ['label'=>'Sedang Diproses',   'icon'=>'fa-cogs',        'desc'=>'Penjual sedang menyediakan pesanan anda'],
    'shipped'    => ['label'=>'Dalam Penghantaran','icon'=>'fa-truck',        'desc'=>'Pesanan anda sedang dalam perjalanan'],
    'delivered'  => ['label'=>'Selesai',            'icon'=>'fa-check-circle','desc'=>'Pesanan anda telah berjaya diterima'],
];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $single_mode ? 'Jejak Pesanan' : 'Pesanan Saya' ?> – Sistem Usahawan Pahang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">
<style>
/* ── Base ── */
*{margin:0;padding:0;box-sizing:border-box;}
body{
  background:linear-gradient(135deg,#fdfdfd 0%,#f8f8f6 40%,#ede8dc 100%);
  background-attachment:fixed;color:#111;overflow-x:hidden;margin-top:90px;
}
body::after{
  content:"";position:fixed;inset:0;
  background-image:url("assets/img/jatapahang.png");
  background-repeat:repeat;background-size:180px;opacity:.14;
  animation:watermarkFloat 40s linear infinite;z-index:-1;
}
@keyframes watermarkFloat{0%{background-position:0 0;opacity:.13}50%{background-position:80px 60px;opacity:.17}100%{background-position:0 0;opacity:.13}}

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
  text-shadow:none;position:relative;overflow:hidden;
}
header .title::after{
  content:"";position:absolute;top:0;left:-75%;width:50%;height:100%;
  background:linear-gradient(120deg,rgba(255,255,255,0) 0%,rgba(255,255,255,.55) 50%,rgba(255,255,255,0) 100%);
  animation:textshine 4s linear infinite;
}
@keyframes textshine{0%{left:-75%}100%{left:125%}}
nav{display:flex;gap:14px;}
nav a{color:#fff;padding:8px 12px;font-weight:500;text-decoration:none;transition:.3s;}
nav a:hover,nav a.active{color:#ffd700;}
.menu-toggle{display:none;font-size:1.8rem;cursor:pointer;background:none;border:none;color:#fff;}

/* ── Container ── */
.container{max-width:900px;margin:auto;padding:25px 16px;}

/* ── Back link ── */
.back-link{display:inline-flex;align-items:center;gap:7px;color:#003399;font-weight:600;text-decoration:none;margin-bottom:20px;transition:.2s;}
.back-link:hover{color:#002266;transform:translateX(-3px);}

/* ── Page header ── */
.page-header{background:#fff;border-radius:15px;padding:24px;box-shadow:0 5px 18px rgba(0,0,0,.08);margin-bottom:25px;text-align:center;}
.page-header h2{color:#003399;margin:0;font-weight:700;}
.page-header p{color:#666;margin:5px 0 0;font-size:.93rem;}

/* ── Order card ── */
.order-card{background:#fff;border-radius:15px;padding:24px;box-shadow:0 5px 18px rgba(0,0,0,.08);margin-bottom:22px;}

/* Header row */
.order-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:14px;padding-bottom:14px;border-bottom:2px solid #f0f0f0;}
.order-number{font-size:1.12rem;font-weight:700;color:#003399;}
.order-date{color:#666;font-size:.87rem;margin-top:3px;}
.order-badges{display:flex;gap:7px;flex-wrap:wrap;}
.badge-status{font-size:.78rem;padding:5px 11px;border-radius:20px;font-weight:600;}

/* ── Visual progress tracker ── */
.progress-wrap{margin:0 0 20px;}
.progress-label{font-weight:600;color:#003399;margin-bottom:12px;display:flex;align-items:center;gap:8px;}

.progress-track{display:flex;align-items:flex-start;position:relative;padding:0 6px;}
.track-step{display:flex;flex-direction:column;align-items:center;position:relative;z-index:1;flex:1;}
.track-icon{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.14);transition:all .35s;}
.track-icon.done{background:#28a745;color:#fff;}
.track-icon.active{background:#003399;color:#fff;box-shadow:0 0 0 5px rgba(0,51,153,.15);}
.track-icon.step-pending{background:#ffc107;color:#fff;box-shadow:0 0 0 5px rgba(255,193,7,.2);}
.track-icon.idle{background:#e9ecef;color:#aaa;}
.track-label{font-size:.71rem;font-weight:600;margin-top:6px;text-align:center;line-height:1.3;}
.track-label.done{color:#28a745}.track-label.active{color:#003399}.track-label.step-pending{color:#c89000}.track-label.idle{color:#aaa}
.track-connector{flex:1;height:3px;background:#e9ecef;margin-top:19px;position:relative;z-index:0;transition:.35s;}
.track-connector.done{background:#28a745;}

/* current step description */
.status-desc{background:#f0f4ff;border-left:4px solid #003399;border-radius:0 8px 8px 0;padding:10px 14px;margin-top:14px;font-size:.9rem;color:#333;}
.status-desc.delivered{background:#f0fff4;border-color:#28a745;}
.status-desc.cancelled{background:#fff5f5;border-color:#dc3545;}

/* ── Info grid ── */
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:16px;}
.info-card{background:#f8f9fa;border-radius:10px;padding:13px 15px;}
.info-label{font-size:.8rem;color:#888;font-weight:500;margin-bottom:4px;}
.info-value{font-size:.95rem;font-weight:600;color:#333;}

/* ── Products ── */
.products-section{border-top:2px solid #f0f0f0;padding-top:15px;margin-top:2px;}
.section-title{font-weight:600;color:#003399;display:flex;align-items:center;gap:8px;margin-bottom:12px;}
.product-item{display:flex;gap:13px;padding:11px;background:#f8f9fa;border-radius:10px;margin-bottom:8px;align-items:center;transition:.2s;}
.product-item:hover{background:#edf0f5;}
.product-image{width:65px;height:65px;object-fit:cover;border-radius:8px;border:2px solid #ddd;flex-shrink:0;}
.product-details{flex:1;}
.product-name{font-weight:600;color:#333;margin-bottom:3px;}
.product-price{color:#666;font-size:.87rem;}
.product-qty{color:#003399;font-weight:700;margin-left:7px;}
.product-sub{font-weight:700;color:#003399;font-size:1rem;text-align:right;white-space:nowrap;}

/* ── Totals ── */
.totals-row{border-top:2px solid #f0f0f0;padding-top:14px;margin-top:4px;display:flex;justify-content:flex-end;}
.total-amount{font-size:1.25rem;font-weight:700;color:#003399;}

/* ── Review section ── */
.review-section{margin-top:18px;border-top:2px solid #f0f0f0;padding-top:18px;text-align:center;}
.btn-review{
  display:inline-flex;align-items:center;gap:9px;
  background:linear-gradient(135deg,#FFD700,#FFA500);
  color:#003399;padding:12px 32px;border-radius:30px;font-weight:700;font-size:1rem;
  text-decoration:none;border:none;cursor:pointer;transition:.3s;
  box-shadow:0 5px 18px rgba(255,165,0,.35);
}
.btn-review:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(255,165,0,.45);color:#003399;}
.review-done{
  display:inline-flex;align-items:center;gap:9px;
  background:#e9f7ef;color:#28a745;padding:10px 24px;border-radius:30px;font-weight:600;font-size:.95rem; margin-left: auto;
}
.review-hint{color:#888;font-size:.87rem;margin-top:8px;}

/* ── Footer actions ── */
.card-footer-actions{display:flex;justify-content:flex-end;margin-top:16px;}
.btn-back-list{
  display:inline-flex;align-items:center;gap:7px;
  background:#003399;color:#fff;padding:9px 22px;border-radius:22px;
  font-weight:600;font-size:.9rem;text-decoration:none;transition:.25s;
}
.btn-back-list:hover{background:#002266;color:#fff;transform:translateY(-2px);box-shadow:0 5px 14px rgba(0,51,153,.25);}

/* ── Empty state ── */
.empty-state{text-align:center;padding:60px 20px;background:#fff;border-radius:15px;box-shadow:0 5px 18px rgba(0,0,0,.08);}
.empty-state i{font-size:4.5rem;color:#ddd;margin-bottom:15px;}
.empty-state h3{color:#666;margin-bottom:10px;}
.empty-state a{display:inline-block;margin-top:14px;background:#FFD700;color:#003399;padding:11px 28px;border-radius:24px;text-decoration:none;font-weight:600;transition:.3s;}
.empty-state a:hover{background:#FFC107;transform:translateY(-2px);}

/* ── Footer ── */
footer{
  background:linear-gradient(135deg,#001F3F 0%,#003399 15%,#0066FF 40%,#99CCFF 60%,#003399 80%,#001F3F 100%);
  animation:metalshine 6s linear infinite;
  color:#fff;padding:30px 20px;margin-top:40px;text-align:center;
}
footer .footer-content{max-width:1100px;margin:auto;}
footer img{height:55px;margin-bottom:12px;filter:drop-shadow(0 3px 5px rgba(0,0,0,.4));}
footer p,footer strong{color:#f8f8f8;font-weight:600;letter-spacing:.4px;}
footer .copyright{margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.2);font-size:.83rem;color:#ddd;}

/* ── Responsive ── */
@media(max-width:768px){
  .menu-toggle{display:block;}
  nav{display:none;flex-direction:column;background:linear-gradient(135deg,#001F3F 0%,#003399 30%,#0066FF 100%);padding:15px;border-radius:10px;margin-top:12px;width:100%;}
  nav.show{display:flex;}
  nav a{text-align:center;padding:10px;}
  .title{font-size:1.1rem;}
  .order-header{flex-direction:column;}
  .product-item{flex-direction:column;text-align:center;}
  .product-image{width:100%;max-width:180px;height:auto;}
  .product-sub{text-align:center;}
  .progress-track{overflow-x:auto;}
  .track-label{font-size:.64rem;}
  .totals-row{justify-content:flex-start;}
}
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
    <a href="pesanan_detail.php" class="active"><strong>Pesanan Saya</strong></a>
  </nav>
</header>

<div class="container">

  <?php if ($single_mode): ?>
    <a href="pesanan_detail.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Kembali ke Senarai Pesanan
    </a>
  <?php endif; ?>

  <div class="page-header">
    <?php if ($single_mode && !empty($orders)): ?>
      <h2><i class="fas fa-map-marker-alt"></i> Jejak Pesanan</h2>
      <p>Pantau status dan perkembangan pesanan anda secara masa nyata</p>
    <?php else: ?>
      <h2><i class="fas fa-shopping-bag"></i> Pesanan Saya</h2>
      <p>Semua pesanan yang telah anda buat</p>
    <?php endif; ?>
  </div>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <i class="fas fa-box-open"></i>
      <h3>Tiada Pesanan</h3>
      <p>Anda belum membuat sebarang pesanan lagi.</p>
      <a href="promosi-pasaran.php"><i class="fas fa-shopping-cart"></i> Mula Membeli-belah</a>
    </div>

  <?php else: ?>
    <?php foreach ($orders as $order):
      $items       = getOrderItems($conn, $order['id']);
      $cur_status  = $order['status_pesanan'];
      $cur_idx     = array_search($cur_status, $step_keys);
      $reviewed    = ($cur_status === 'delivered') ? hasReviewed($conn, $order['id'], $usahawan_id) : false;
    ?>
    <div class="order-card">

      <!-- Order header -->
      <div class="order-header">
        <div>
          <div class="order-number"><i class="fas fa-receipt me-1"></i><?= htmlspecialchars($order['no_pesanan']) ?></div>
          <div class="order-date"><i class="far fa-calendar-alt me-1"></i><?= date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])) ?></div>
        </div>
        <div class="order-badges">
          <span class="badge bg-<?= statusClass($cur_status) ?> badge-status"><?= translateStatus($cur_status) ?></span>
          <span class="badge bg-<?= statusClass($order['status_bayaran']) ?> badge-status"><?= translateStatus($order['status_bayaran']) ?></span>
        </div>
      </div>

      <!-- Progress tracker -->
      <?php if ($cur_status !== 'cancelled'): ?>
      <div class="progress-wrap">
        <div class="progress-label"><i class="fas fa-route"></i> Status Penghantaran</div>
        <div class="progress-track">
          <?php foreach ($step_keys as $i => $key):
            if ($i > 0): ?><div class="track-connector <?= ($cur_idx !== false && $cur_idx >= $i) ? 'done' : '' ?>"></div><?php endif;
            $state = 'idle';
            if ($cur_idx !== false) {
                if ($cur_idx > $i)  $state = 'done';
                elseif ($cur_idx === $i) $state = ($key === 'pending') ? 'step-pending' : 'active';
            }
          ?>
            <div class="track-step">
              <div class="track-icon <?= $state ?>">
                <?php if ($state === 'done'): ?><i class="fas fa-check"></i>
                <?php else: ?><i class="fas <?= $step_meta[$key]['icon'] ?>"></i><?php endif; ?>
              </div>
              <div class="track-label <?= $state ?>"><?= $step_meta[$key]['label'] ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Current status description -->
        <?php
        $desc_class = ($cur_status === 'delivered') ? 'delivered' : '';
        $cur_desc   = $step_meta[$cur_status]['desc'] ?? 'Memproses...';
        ?>
        <div class="status-desc <?= $desc_class ?>">
          <i class="fas <?= $step_meta[$cur_status]['icon'] ?? 'fa-info-circle' ?> me-2"></i>
          <strong><?= $step_meta[$cur_status]['label'] ?? translateStatus($cur_status) ?>:</strong>
          <?= $cur_desc ?>
          <?php if ($cur_status === 'shipped' && !empty($order['no_tracking'])): ?>
            — <strong>No. Tracking:</strong> <?= htmlspecialchars($order['no_tracking']) ?>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
        <div class="status-desc cancelled mb-3">
          <i class="fas fa-times-circle me-2"></i>
          <strong>Pesanan Dibatalkan</strong> – Hubungi kami jika anda mempunyai sebarang soalan.
        </div>
      <?php endif; ?>

      <!-- Info grid -->
      <div class="info-grid">
        <div class="info-card">
          <div class="info-label"><i class="fas fa-truck me-1"></i>Cara Penghantaran</div>
          <div class="info-value"><?= $order['cara_hantar']==='delivery'?'Hantar ke Rumah':'Pickup di Dropspot' ?></div>
        </div>
        <div class="info-card">
          <div class="info-label"><i class="fas fa-money-bill-wave me-1"></i>Cara Bayaran</div>
          <div class="info-value"><?= $order['cara_bayar']==='online'?'Online Banking':'COD' ?></div>
        </div>
        <?php if (!empty($order['alamat_penghantaran'])): ?>
        <div class="info-card" style="grid-column:1/-1;">
          <div class="info-label"><i class="fas fa-map-marker-alt me-1"></i>Alamat Penghantaran</div>
          <div class="info-value"><?= nl2br(htmlspecialchars($order['alamat_penghantaran'])) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Timestamps for completed steps -->
      <?php if (!empty($order['tarikh_diproses']) || !empty($order['tarikh_dihantar']) || !empty($order['tarikh_selesai'])): ?>
      <div style="font-size:.82rem;color:#888;margin-bottom:14px;display:flex;gap:18px;flex-wrap:wrap;">
        <?php if (!empty($order['tarikh_diproses'])): ?>
          <span><i class="fas fa-cogs me-1 text-info"></i>Diproses: <?= date('d/m/Y H:i', strtotime($order['tarikh_diproses'])) ?></span>
        <?php endif; ?>
        <?php if (!empty($order['tarikh_dihantar'])): ?>
          <span><i class="fas fa-truck me-1 text-primary"></i>Dihantar: <?= date('d/m/Y H:i', strtotime($order['tarikh_dihantar'])) ?></span>
        <?php endif; ?>
        <?php if (!empty($order['tarikh_selesai'])): ?>
          <span><i class="fas fa-check-circle me-1 text-success"></i>Selesai: <?= date('d/m/Y H:i', strtotime($order['tarikh_selesai'])) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Products -->
      <div class="products-section">
        <div class="section-title"><i class="fas fa-box"></i> Produk yang Dipesan</div>
        <?php foreach ($items as $item): ?>
        <div class="product-item">
          <img src="<?= htmlspecialchars('uploads/'.$item['gambar_url']) ?>"
               alt="<?= htmlspecialchars($item['nama_produk'] ?? $item['nama']) ?>"
               class="product-image" onerror="this.src='assets/img/no-image.png'">
          <div class="product-details">
            <div class="product-name"><?= htmlspecialchars($item['nama_produk'] ?? $item['nama']) ?></div>
            <div class="product-price">RM <?= number_format($item['harga'],2) ?> <span class="product-qty">× <?= $item['kuantiti'] ?></span></div>
          </div>
          <div class="product-sub">RM <?= number_format($item['subtotal'],2) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Total -->
      <div class="totals-row">
        <div class="total-amount">Jumlah Bayaran: RM <?= number_format($order['jumlah_bayaran'],2) ?></div>
      </div>

      <!-- ── Review section (delivered orders only) ── -->
      <?php if ($cur_status === 'delivered'): ?>
      <div class="review-section">
        <?php if ($reviewed): ?>
          <div class="review-done">
            Ulasan anda telah dihantar. Terima kasih!
          </div>
        <?php else: ?>
          <a href="review_produk.php?order_id=<?= $order['id'] ?>" class="btn-review">
            <i class="fas fa-star"></i> Tulis Ulasan
          </a>
          <p class="review-hint">Kongsi pengalaman anda tentang produk yang diterima</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Back to list (single mode) -->
      <?php if ($single_mode): ?>
      <div class="card-footer-actions">
        <a href="pesanan_detail.php" class="btn-back-list">
          <i class="fas fa-list"></i> Semua Pesanan
        </a>
      </div>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>