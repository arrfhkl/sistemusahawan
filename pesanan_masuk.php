<?php
include "connection.php";
include "header.php";

// ── Auth & role guard ──────────────────────────────────────────────────────
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

$sql = "SELECT jenis, perniagaan FROM usahawan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['jenis'] === 'Pengguna' || $user['perniagaan'] === 'Pengguna') {
    echo "<script>
            alert('⚠️ AKSES DITOLAK!\\n\\nFungsi Pesanan Masuk hanya untuk Usahawan sahaja.');
            window.location = 'profil_usahawan.php?id=" . $usahawan_id . "';
          </script>";
    exit();
}

// ── Status update handler ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pesanan_id = (int) $_POST['pesanan_id'];
    $new_status = $_POST['new_status'];

    $allowed = ['processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $allowed)) die("Status tidak sah.");

    $sql  = "UPDATE pesanan SET status_pesanan = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $pesanan_id);

    if ($stmt->execute()) {
        $date_map = ['processing'=>'tarikh_diproses','shipped'=>'tarikh_dihantar','delivered'=>'tarikh_selesai'];
        if (isset($date_map[$new_status])) {
            $df  = $date_map[$new_status];
            $s2  = $conn->prepare("UPDATE pesanan SET $df = NOW() WHERE id = ?");
            $s2->bind_param("i", $pesanan_id);
            $s2->execute();
        }

            if ($new_status === 'shipped') {
            $no_tracking  = trim($_POST['no_tracking'] ?? '');
            $jenis_kurier = trim($_POST['jenis_kurier'] ?? '');
            if ($no_tracking !== '' || $jenis_kurier !== '') {
                $s3 = $conn->prepare("UPDATE pesanan SET no_tracking = ?, jenis_kurier = ? WHERE id = ?");
                $s3->bind_param("ssi", $no_tracking, $jenis_kurier, $pesanan_id);
                $s3->execute();
            }
        }
        
        echo "<script>alert('✅ Status pesanan berjaya dikemaskini!'); window.location.href='pesanan_masuk.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal mengemaskini status!');</script>";
    }
    exit;
}

// ── Filters ────────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$search        = trim($_GET['search'] ?? '');

// ── Fetch orders ────────────────────────────────────────────────────────────
$where  = "WHERE pr.usahawan_id = ?";
$params = [$usahawan_id];
$types  = "i";

if ($filter_status !== 'all') { $where .= " AND p.status_pesanan = ?"; $params[] = $filter_status; $types .= "s"; }
if ($search !== '')           { $where .= " AND (p.no_pesanan LIKE ? OR u.nama LIKE ?)"; $sp = "%$search%"; $params[] = $sp; $params[] = $sp; $types .= "ss"; }

$sql = "SELECT p.*, u.nama, u.telefon AS no_telefon, u.email,
        COUNT(DISTINCT pi.id) AS jumlah_item,
        SUM(pi.kuantiti)      AS jumlah_produk
        FROM pesanan p
        INNER JOIN pesanan_item pi ON p.id = pi.pesanan_id
        INNER JOIN produk pr       ON pi.produk_id = pr.id
        INNER JOIN usahawan u      ON p.usahawan_id = u.id
        $where
        GROUP BY p.id ORDER BY p.tarikh_pesanan DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Statistics ─────────────────────────────────────────────────────────────
$ss = $conn->prepare("SELECT
    COUNT(DISTINCT CASE WHEN p.status_pesanan='pending'    THEN p.id END) AS pending,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='processing' THEN p.id END) AS processing,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='shipped'    THEN p.id END) AS shipped,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='delivered'  THEN p.id END) AS delivered,
    COUNT(DISTINCT p.id) AS total
    FROM pesanan p
    INNER JOIN pesanan_item pi ON p.id = pi.pesanan_id
    INNER JOIN produk pr       ON pi.produk_id = pr.id
    WHERE pr.usahawan_id = ?");
$ss->bind_param("i", $usahawan_id);
$ss->execute();
$stats = $ss->get_result()->fetch_assoc();

// ── Helpers ────────────────────────────────────────────────────────────────
function translateStatus($s) {
    return ['pending'=>'Menunggu','processing'=>'Diproses','shipped'=>'Dihantar',
            'delivered'=>'Selesai','cancelled'=>'Dibatalkan','paid'=>'Dibayar','failed'=>'Gagal'][$s] ?? $s;
}
function statusClass($s) {
    return ['pending'=>'warning','processing'=>'info','shipped'=>'primary',
            'delivered'=>'success','cancelled'=>'danger','paid'=>'success','failed'=>'danger'][$s] ?? 'secondary';
}
function getOrderProducts($conn, $pesanan_id, $usahawan_id) {
    $stmt = $conn->prepare(
        "SELECT pi.*, pr.nama, pr.gambar_url, pr.harga
         FROM pesanan_item pi INNER JOIN produk pr ON pi.produk_id = pr.id
         WHERE pi.pesanan_id = ? AND pr.usahawan_id = ? ORDER BY pi.id ASC");
    $stmt->bind_param("ii", $pesanan_id, $usahawan_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function nextStatusOptions($current) {
    return [
        'pending'    => [['value'=>'processing','label'=>'Terima & Mula Proses','icon'=>'fa-cogs','cls'=>'btn-process'],
                         ['value'=>'cancelled', 'label'=>'Tolak / Batalkan',    'icon'=>'fa-times-circle','cls'=>'btn-cancel']],
        'processing' => [['value'=>'shipped',   'label'=>'Tandakan Dihantar',   'icon'=>'fa-truck','cls'=>'btn-ship'],
                         ['value'=>'delivered', 'label'=>'Tandakan Selesai',    'icon'=>'fa-check-circle','cls'=>'btn-deliver']],
        'shipped'    => [['value'=>'delivered', 'label'=>'Tandakan Selesai',    'icon'=>'fa-check-circle','cls'=>'btn-deliver']],
    ][$current] ?? [];
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk – Sistem Usahawan Pahang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">
<style>
.container { padding-top:110px; max-width:1100px; }

/* Page header */
.page-header{background:#fff;border-radius:15px;padding:25px;box-shadow:0 5px 20px rgba(0,0,0,.08);margin-bottom:28px;text-align:center;}
.page-header h2{color:#003399;margin:0;font-weight:700;}
.page-header p{color:#666;margin:6px 0 0;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:15px;margin-bottom:25px;}
.stat-card{background:#fff;border-radius:14px;padding:22px 14px;box-shadow:0 4px 14px rgba(0,0,0,.07);text-align:center;transition:.25s;}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 8px 22px rgba(0,0,0,.12);}
.stat-icon{font-size:2.1rem;margin-bottom:8px;}
.stat-icon.pending{color:#ffc107}.stat-icon.processing{color:#17a2b8}.stat-icon.shipped{color:#0d6efd}.stat-icon.delivered{color:#28a745}.stat-icon.total{color:#003399}
.stat-number{font-size:1.9rem;font-weight:700;color:#003399;margin-bottom:3px;}
.stat-label{color:#666;font-size:.83rem;font-weight:500;}

/* Filters */
.filters-section{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 4px 14px rgba(0,0,0,.07);margin-bottom:20px;}
.filters-row{display:flex;gap:13px;flex-wrap:wrap;align-items:flex-end;}
.filter-group{flex:1;min-width:175px;}
.filter-group label{display:block;margin-bottom:5px;font-weight:600;color:#003399;font-size:.88rem;}
.filter-group select,.filter-group input{width:100%;padding:9px 12px;border:2px solid #e9ecef;border-radius:8px;font-size:.93rem;transition:.2s;}
.filter-group select:focus,.filter-group input:focus{outline:none;border-color:#003399;}
.btn-filter{background:#003399;color:#fff;padding:10px 20px;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:.25s;}
.btn-filter:hover{background:#002266;transform:translateY(-2px);}
.btn-reset{background:#6c757d;color:#fff;padding:10px 20px;border:none;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:.25s;}
.btn-reset:hover{background:#5a6268;color:#fff;}

/* Alert */
.alert-new{background:#fff3cd;border-left:5px solid #ffc107;padding:14px 18px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:13px;}
.alert-new i{font-size:1.8rem;color:#ffc107;}

/* Order card */
.order-card{background:#fff;border-radius:15px;padding:22px 24px;box-shadow:0 5px 18px rgba(0,0,0,.08);margin-bottom:20px;transition:.25s;}
.order-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12);}

.order-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:12px;padding-bottom:12px;border-bottom:2px solid #f0f0f0;}
.order-number{font-size:1.12rem;font-weight:700;color:#003399;}
.order-date{color:#666;font-size:.87rem;margin-top:3px;}
.order-badges{display:flex;gap:7px;flex-wrap:wrap;}
.badge-status{font-size:.78rem;padding:5px 11px;border-radius:20px;font-weight:600;}

/* Progress tracker */
.progress-track{display:flex;align-items:center;margin:14px 0 18px;position:relative;}
.track-step{display:flex;flex-direction:column;align-items:center;position:relative;z-index:1;min-width:60px;}
.track-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.13);transition:.3s;}
.track-icon.done{background:#28a745;color:#fff;}
.track-icon.active{background:#003399;color:#fff;}
.track-icon.step-pending{background:#ffc107;color:#fff;}
.track-icon.idle{background:#e9ecef;color:#aaa;}
.track-label{font-size:.7rem;font-weight:600;margin-top:5px;text-align:center;color:#aaa;}
.track-label.done{color:#28a745}.track-label.active{color:#003399}.track-label.step-pending{color:#e0a800}
.track-connector{flex:1;height:3px;background:#e9ecef;position:relative;z-index:0;margin-top:-26px;}
.track-connector.done{background:#28a745;}

/* Customer info */
.customer-info{background:#f8f9fa;padding:13px 15px;border-radius:10px;margin-bottom:13px;}
.customer-name{font-weight:700;color:#003399;font-size:1rem;margin-bottom:4px;}
.customer-contact{color:#555;font-size:.87rem;display:flex;gap:16px;flex-wrap:wrap;}
.meta-row{margin-top:7px;color:#666;font-size:.87rem;display:flex;gap:16px;flex-wrap:wrap;}

/* Products */
.products-section{border-top:2px solid #f0f0f0;padding-top:14px;margin-top:12px;}
.section-title{font-weight:600;color:#003399;display:flex;align-items:center;gap:8px;margin-bottom:11px;}
.product-item{display:flex;gap:13px;padding:11px;background:#f8f9fa;border-radius:10px;margin-bottom:8px;align-items:center;transition:.2s;}
.product-item:hover{background:#edf0f5;}
.product-image{width:65px;height:65px;object-fit:cover;border-radius:8px;border:2px solid #ddd;flex-shrink:0;}
.product-details{flex:1;}
.product-name{font-weight:600;color:#333;margin-bottom:3px;}
.product-price{color:#666;font-size:.87rem;}
.product-qty{color:#003399;font-weight:700;margin-left:7px;}
.product-sub{font-weight:700;color:#003399;font-size:1rem;text-align:right;white-space:nowrap;}

/* Footer */
.order-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:13px;border-top:2px solid #f0f0f0;padding-top:13px;margin-top:13px;}
.total-amount{font-size:1.2rem;font-weight:700;color:#003399;}
.action-buttons{display:flex;gap:9px;flex-wrap:wrap;align-items:center;}

/* Action buttons */
.btn-action{padding:8px 18px;border-radius:22px;border:none;font-weight:600;cursor:pointer;transition:.25s;display:inline-flex;align-items:center;gap:6px;font-size:.88rem;text-decoration:none;}
.btn-action:hover{filter:brightness(1.1);transform:translateY(-2px);box-shadow:0 5px 13px rgba(0,0,0,.17);}
.btn-process{background:#17a2b8;color:#fff;}.btn-ship{background:#0d6efd;color:#fff;}.btn-deliver{background:#28a745;color:#fff;}.btn-cancel{background:#dc3545;color:#fff;}.btn-view{background:#003399;color:#fff;}

/* Empty state */
.empty-state{text-align:center;padding:55px 20px;background:#fff;border-radius:15px;box-shadow:0 5px 18px rgba(0,0,0,.08);}
.empty-state i{font-size:4.5rem;color:#ddd;margin-bottom:15px;}
.empty-state h3{color:#666;margin-bottom:9px;}

/* Responsive */
@media(max-width:768px){
  .stats-grid{grid-template-columns:repeat(2,1fr);}
  .filters-row{flex-direction:column;}
  .btn-filter,.btn-reset{width:100%;}
  .order-header,.order-footer{flex-direction:column;align-items:flex-start;}
  .product-item{flex-direction:column;text-align:center;}
  .product-image{width:100%;max-width:190px;height:auto;}
  .product-sub{text-align:center;}
  .progress-track{overflow-x:auto;}
  .track-label{font-size:.64rem;}
}
</style>
</head>
<body>

<div class="container">

  <div class="page-header">
    <h2><i class="fas fa-inbox"></i> Pesanan Masuk</h2>
    <p>Urus semua pesanan yang diterima untuk produk anda</p>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon pending"><i class="fas fa-clock"></i></div><div class="stat-number"><?= $stats['pending'] ?></div><div class="stat-label">Menunggu</div></div>
    <div class="stat-card"><div class="stat-icon processing"><i class="fas fa-cogs"></i></div><div class="stat-number"><?= $stats['processing'] ?></div><div class="stat-label">Diproses</div></div>
    <div class="stat-card"><div class="stat-icon shipped"><i class="fas fa-truck"></i></div><div class="stat-number"><?= $stats['shipped'] ?></div><div class="stat-label">Dihantar</div></div>
    <div class="stat-card"><div class="stat-icon delivered"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?= $stats['delivered'] ?></div><div class="stat-label">Selesai</div></div>
    <div class="stat-card"><div class="stat-icon total"><i class="fas fa-shopping-bag"></i></div><div class="stat-number"><?= $stats['total'] ?></div><div class="stat-label">Jumlah</div></div>
  </div>

  <!-- Filters -->
  <div class="filters-section">
    <form method="GET" action="">
      <div class="filters-row">
        <div class="filter-group">
          <label><i class="fas fa-filter"></i> Status</label>
          <select name="status">
            <option value="all"        <?= $filter_status==='all'        ?'selected':''?>>Semua Status</option>
            <option value="pending"    <?= $filter_status==='pending'    ?'selected':''?>>Menunggu</option>
            <option value="processing" <?= $filter_status==='processing' ?'selected':''?>>Diproses</option>
            <option value="shipped"    <?= $filter_status==='shipped'    ?'selected':''?>>Dihantar</option>
            <option value="delivered"  <?= $filter_status==='delivered'  ?'selected':''?>>Selesai</option>
            <option value="cancelled"  <?= $filter_status==='cancelled'  ?'selected':''?>>Dibatalkan</option>
          </select>
        </div>
        <div class="filter-group">
          <label><i class="fas fa-search"></i> Carian</label>
          <input type="text" name="search" placeholder="No. pesanan atau nama pelanggan" value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button>
        <a href="pesanan_masuk.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
      </div>
    </form>
  </div>

  <?php if ($stats['pending'] > 0): ?>
  <div class="alert-new">
    <i class="fas fa-bell"></i>
    <div>
      <strong>Pesanan Baru Menunggu!</strong>
      <p style="margin:0;color:#555;">Anda mempunyai <strong><?= $stats['pending'] ?></strong> pesanan yang perlu diproses.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Orders -->
  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      <h3>Tiada Pesanan</h3>
      <p>Tiada pesanan dijumpai dengan kriteria carian anda.</p>
    </div>
  <?php else: ?>
    <?php
    $step_keys = ['pending','processing','shipped','delivered'];
    $step_meta = [
      'pending'    => ['label'=>'Diterima', 'icon'=>'fa-file-alt'],
      'processing' => ['label'=>'Diproses', 'icon'=>'fa-cogs'],
      'shipped'    => ['label'=>'Dihantar', 'icon'=>'fa-truck'],
      'delivered'  => ['label'=>'Selesai',  'icon'=>'fa-check-circle'],
    ];
    foreach ($orders as $order):
      $products    = getOrderProducts($conn, $order['id'], $usahawan_id);
      $order_total = array_sum(array_column($products, 'subtotal'));
      $cur_status  = $order['status_pesanan'];
      $cur_idx     = array_search($cur_status, $step_keys);
      $next_opts   = nextStatusOptions($cur_status);
    ?>
    <div class="order-card">

      <!-- Header -->
      <div class="order-header">
        <div>
          <div class="order-number">
            <?= htmlspecialchars($order['no_pesanan']) ?>
            <?php if ($cur_status === 'pending'): ?>
              <span class="badge bg-danger ms-2" style="font-size:.62rem;vertical-align:middle;">BARU</span>
            <?php endif; ?>
          </div>
          <div class="order-date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])) ?></div>
        </div>
        <div class="order-badges">
          <span class="badge bg-<?= statusClass($cur_status) ?> badge-status"><?= translateStatus($cur_status) ?></span>
          <span class="badge bg-<?= statusClass($order['status_bayaran']) ?> badge-status"><?= translateStatus($order['status_bayaran']) ?></span>
        </div>
      </div>

      <!-- Progress tracker -->
      <?php if ($cur_status !== 'cancelled'): ?>
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
            <div class="track-icon <?= $state ?>"><i class="fas <?= $step_meta[$key]['icon'] ?>"></i></div>
            <div class="track-label <?= $state ?>"><?= $step_meta[$key]['label'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
        <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:8px;font-size:.9rem;">
          <i class="fas fa-times-circle me-2"></i><strong>Pesanan Dibatalkan</strong>
        </div>
      <?php endif; ?>

      <!-- Customer -->
      <div class="customer-info">
        <div class="customer-name"><i class="fas fa-user me-1"></i><?= htmlspecialchars($order['nama']) ?></div>
        <div class="customer-contact">
          <span><i class="fas fa-phone me-1"></i><?= htmlspecialchars($order['no_telefon']) ?></span>
          <span><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($order['email']) ?></span>
        </div>
        <div class="meta-row">
  <span><i class="fas fa-<?= $order['cara_hantar']==='delivery'?'truck':'map-marker-alt' ?> me-1"></i><?= $order['cara_hantar']==='delivery'?'Hantar ke Rumah':'Pickup di Dropspot' ?></span>
  <span><i class="fas fa-money-bill-wave me-1"></i><?= $order['cara_bayar']==='online'?'Online Banking':'COD (Bayar Semasa Terima)' ?></span>
</div>

<?php if (!empty($order['no_tracking']) || !empty($order['jenis_kurier'])): ?>
<div style="margin-top:10px;background:#e8f0fe;border:2px solid #0d6efd;border-radius:10px;padding:11px 15px;display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
  <?php if (!empty($order['jenis_kurier'])): ?>
  <span>
    <i class="fas fa-box me-1" style="color:#0d6efd;"></i>
    <strong style="color:#003399;">Kurier:</strong>
    <span style="font-weight:600;color:#333;"><?= htmlspecialchars($order['jenis_kurier']) ?></span>
  </span>
  <?php endif; ?>
  <?php if (!empty($order['no_tracking'])): ?>
  <span>
    <i class="fas fa-barcode me-1" style="color:#0d6efd;"></i>
    <strong style="color:#003399;">No. Tracking:</strong>
    <span style="font-weight:700;color:#0d6efd;font-family:monospace;letter-spacing:.6px;"><?= htmlspecialchars($order['no_tracking']) ?></span>
  </span>
  <button onclick="navigator.clipboard.writeText('<?= addslashes($order['no_tracking']) ?>').then(()=>alert('✅ Nombor tracking disalin!'))"
          style="background:#0d6efd;color:#fff;border:none;border-radius:16px;padding:4px 13px;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;margin-left:auto;"
          onmouseover="this.style.background='#0b5ed7'"
          onmouseout="this.style.background='#0d6efd'">
    <i class="fas fa-copy"></i> Salin
  </button>
  <?php endif; ?>
</div>
<?php endif; ?>
      </div>

      <!-- Products -->
      <div class="products-section">
        <div class="section-title"><i class="fas fa-box"></i> Produk Anda (<?= count($products) ?> item)</div>
        <?php foreach ($products as $prod): ?>
        <div class="product-item">
          <img src="<?= htmlspecialchars('uploads/'.$prod['gambar_url']) ?>"
               alt="<?= htmlspecialchars($prod['nama_produk'] ?? $prod['nama']) ?>"
               class="product-image" onerror="this.src='assets/img/no-image.png'">
          <div class="product-details">
            <div class="product-name"><?= htmlspecialchars($prod['nama_produk'] ?? $prod['nama']) ?></div>
            <div class="product-price">RM <?= number_format($prod['harga'],2) ?> <span class="product-qty">× <?= $prod['kuantiti'] ?></span></div>
          </div>
          <div class="product-sub">RM <?= number_format($prod['subtotal'],2) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Footer -->
      <div class="order-footer">
        <div class="total-amount">Jumlah Produk Anda: RM <?= number_format($order_total,2) ?></div>
        <div class="action-buttons">
          <?php foreach ($next_opts as $opt): ?>
  <?php if ($opt['value'] === 'shipped'): ?>
    <button type="button"
            class="btn-action <?= $opt['cls'] ?>"
            onclick="openShipModal(<?= $order['id'] ?>)">
      <i class="fas <?= $opt['icon'] ?>"></i> <?= $opt['label'] ?>
    </button>
  <?php else: ?>
    <form method="POST" style="display:inline;"
          onsubmit="return confirm('Sahkan tukar status kepada: <?= addslashes($opt['label']) ?>?');">
      <input type="hidden" name="pesanan_id" value="<?= $order['id'] ?>">
      <input type="hidden" name="new_status"  value="<?= $opt['value'] ?>">
      <button type="submit" name="update_status" class="btn-action <?= $opt['cls'] ?>">
        <i class="fas <?= $opt['icon'] ?>"></i> <?= $opt['label'] ?>
      </button>
    </form>
  <?php endif; ?>
<?php endforeach; ?>
          <a href="detail_pesanan_masuk.php?order_id=<?= $order['id'] ?>" class="btn-action btn-view">
            <i class="fas fa-eye"></i> Butiran Penuh
          </a>
        </div>
      </div>

      

    </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<!-- ── Shipping Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="shipModal" tabindex="-1" aria-labelledby="shipModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:15px;overflow:hidden;">
      <div class="modal-header" style="background:#003399;">
        <h5 class="modal-title text-white" id="shipModalLabel">
          <i class="fas fa-truck me-2"></i>Maklumat Penghantaran
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="shipForm">
        <div class="modal-body p-4">
          <input type="hidden" name="pesanan_id" id="modal_pesanan_id">
          <input type="hidden" name="new_status" value="shipped">

          <div class="mb-3">
            <label class="form-label fw-bold" style="color:#003399;">
              <i class="fas fa-box me-1"></i> Jenis Kurier <span class="text-danger">*</span>
            </label>
            <select name="jenis_kurier" id="modal_kurier" class="form-select" required
                    style="border:2px solid #e9ecef;border-radius:8px;padding:10px;">
              <option value="">-- Pilih Kurier --</option>
              <option value="Pos Laju">Pos Laju</option>
              <option value="J&T Express">J&T Express</option>
              <option value="DHL Express">DHL Express</option>
              <option value="Ninja Van">Ninja Van</option>
              <option value="Shopee Express">Shopee Express</option>
              <option value="Lalamove">Lalamove</option>
              <option value="GDex">GDex</option>
              <option value="Skynet">Skynet</option>
              <option value="City-Link Express">City-Link Express</option>
              <option value="Lain-lain">Lain-lain</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold" style="color:#003399;">
              <i class="fas fa-barcode me-1"></i> Nombor Tracking <span class="text-danger">*</span>
            </label>
            <input type="text" name="no_tracking" id="modal_tracking"
                   class="form-control" required
                   placeholder="Contoh: EP123456789MY"
                   style="border:2px solid #e9ecef;border-radius:8px;padding:10px;font-family:monospace;letter-spacing:.5px;">
            <div class="form-text">Masukkan nombor tracking yang diberikan oleh kurier.</div>
          </div>
        </div>
        <div class="modal-footer" style="border-top:2px solid #f0f0f0;">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Batal
          </button>
          <button type="submit" name="update_status" class="btn rounded-pill px-4 fw-bold"
                  style="background:#0d6efd;color:#fff;">
            <i class="fas fa-truck me-1"></i> Tandakan Dihantar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openShipModal(pesananId) {
  document.getElementById('modal_pesanan_id').value = pesananId;
  document.getElementById('modal_kurier').value   = '';
  document.getElementById('modal_tracking').value = '';
  new bootstrap.Modal(document.getElementById('shipModal')).show();
}

// Confirm before submitting from modal
document.getElementById('shipForm').addEventListener('submit', function(e) {
  const kurier   = document.getElementById('modal_kurier').value.trim();
  const tracking = document.getElementById('modal_tracking').value.trim();
  if (!kurier || !tracking) {
    e.preventDefault();
    alert('Sila lengkapkan jenis kurier dan nombor tracking.');
    return;
  }
  if (!confirm('Sahkan penghantaran dengan maklumat berikut?\n\nKurier: ' + kurier + '\nTracking: ' + tracking)) {
    e.preventDefault();
  }
});
</script>

<script>setTimeout(() => location.reload(), 120000);</script>
<?php include "footer.php"; ?>
</body>
</html>