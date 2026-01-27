<?php
include "connection.php";
include "header.php";

// Get user ID from URL or session
$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['usahawan_id'];

// Check if user is Usahawan
$sql = "SELECT jenis, perniagaan, nama FROM usahawan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Block access if Pengguna
if ($user['jenis'] === 'Pengguna' || $user['perniagaan'] === 'Pengguna') {
    echo "<script>
            alert('⚠️ AKSES DITOLAK!\\n\\nFungsi Pesanan Masuk hanya untuk Usahawan sahaja.\\n\\nAnda perlu mempunyai perniagaan untuk menerima pesanan.');
            window.location = 'profil_usahawan.php?id=" . $user_id . "';
          </script>";
    exit();
}

$stmt->close();
?>

<?php
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $pesanan_id = $_POST['pesanan_id'];
    $new_status = $_POST['new_status'];
    
    $sql = "UPDATE pesanan SET status_pesanan = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $pesanan_id);
    
    if ($stmt->execute()) {
        // Update tarikh based on status
        $date_field = '';
        if ($new_status == 'processing') $date_field = 'tarikh_diproses';
        elseif ($new_status == 'shipped') $date_field = 'tarikh_dihantar';
        elseif ($new_status == 'delivered') $date_field = 'tarikh_selesai';
        
        if ($date_field) {
            $sql_date = "UPDATE pesanan SET $date_field = NOW() WHERE id = ?";
            $stmt_date = $conn->prepare($sql_date);
            $stmt_date->bind_param("i", $pesanan_id);
            $stmt_date->execute();
        }
        
        echo "<script>alert('Status pesanan berjaya dikemaskini!'); window.location.href='pesanan_masuk.php';</script>";
    } else {
        echo "<script>alert('Gagal mengemaskini status!');</script>";
    }
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get orders for products owned by this usahawan
$sql = "SELECT p.*, u.nama, u.telefon, u.email,
        COUNT(DISTINCT pi.id) as jumlah_item,
        SUM(pi.kuantiti) as jumlah_produk
        FROM pesanan p
        INNER JOIN pesanan_item pi ON p.id = pi.pesanan_id
        INNER JOIN produk pr ON pi.produk_id = pr.id
        INNER JOIN usahawan u ON p.usahawan_id = u.id
        WHERE pr.usahawan_id = ?";

if ($filter_status != 'all') {
    $sql .= " AND p.status_pesanan = ?";
}

if (!empty($search)) {
    $sql .= " AND (p.no_pesanan LIKE ? OR u.nama LIKE ?)";
}

$sql .= " GROUP BY p.id ORDER BY p.tarikh_pesanan DESC";

$stmt = $conn->prepare($sql);

if ($filter_status != 'all' && !empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("isss", $usahawan_id, $filter_status, $search_param, $search_param);
} elseif ($filter_status != 'all') {
    $stmt->bind_param("is", $usahawan_id, $filter_status);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("iss", $usahawan_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $usahawan_id);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT CASE WHEN p.status_pesanan = 'pending' THEN p.id END) as pending,
    COUNT(DISTINCT CASE WHEN p.status_pesanan = 'processing' THEN p.id END) as processing,
    COUNT(DISTINCT CASE WHEN p.status_pesanan = 'shipped' THEN p.id END) as shipped,
    COUNT(DISTINCT CASE WHEN p.status_pesanan = 'delivered' THEN p.id END) as delivered,
    COUNT(DISTINCT p.id) as total
    FROM pesanan p
    INNER JOIN pesanan_item pi ON p.id = pi.pesanan_id
    INNER JOIN produk pr ON pi.produk_id = pr.id
    WHERE pr.usahawan_id = ?";
$stmt_stats = $conn->prepare($stats_sql);
$stmt_stats->bind_param("i", $usahawan_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

function translateStatus($status) {
    $translations = [
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'shipped' => 'Dihantar',
        'delivered' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'paid' => 'Dibayar',
        'failed' => 'Gagal'
    ];
    return isset($translations[$status]) ? $translations[$status] : $status;
}

function getStatusClass($status) {
    $classes = [
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'paid' => 'success',
        'failed' => 'danger'
    ];
    return isset($classes[$status]) ? $classes[$status] : 'secondary';
}

function getOrderProducts($conn, $pesanan_id, $usahawan_id) {
    $sql = "SELECT pi.*, pr.nama, pr.gambar_url, pr.harga 
            FROM pesanan_item pi
            INNER JOIN produk pr ON pi.produk_id = pr.id
            WHERE pi.pesanan_id = ? AND pr.usahawan_id = ?
            ORDER BY pi.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pesanan_id, $usahawan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}
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

.stat-icon.pending { color: #ffc107; }
.stat-icon.processing { color: #17a2b8; }
.stat-icon.shipped { color: #007bff; }
.stat-icon.delivered { color: #28a745; }
.stat-icon.total { color: #003399; }

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

.filters-section {
  background: #fff;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 20px;
}

.filters-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  align-items: center;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #003399;
}

.filter-group select,
.filter-group input {
  width: 100%;
  padding: 10px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.95rem;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #003399;
}

.filter-btn {
  background: #003399;
  color: #fff;
  padding: 10px 25px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
  align-self: flex-end;
}

.filter-btn:hover {
  background: #002266;
  transform: translateY(-2px);
}

.reset-btn {
  background: #6c757d;
  color: #fff;
  padding: 10px 25px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
  align-self: flex-end;
  text-decoration: none;
  display: inline-block;
}

.reset-btn:hover {
  background: #5a6268;
  color: #fff;
}

.order-card {
  background: #fff;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 20px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.order-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
  flex-wrap: wrap;
  gap: 10px;
}

.order-number {
  font-size: 1.2rem;
  font-weight: 700;
  color: #003399;
}

.order-date {
  color: #666;
  font-size: 0.9rem;
}

.order-badges {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.customer-info {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
}

.customer-name {
  font-weight: 700;
  color: #003399;
  font-size: 1.1rem;
  margin-bottom: 5px;
}

.customer-contact {
  color: #666;
  font-size: 0.9rem;
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.products-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

.products-title {
  font-weight: 600;
  color: #003399;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.product-item {
  display: flex;
  gap: 15px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 10px;
  margin-bottom: 10px;
  align-items: center;
  transition: background 0.3s;
}

.product-item:hover {
  background: #e9ecef;
}

.product-image {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid #ddd;
}

.product-details {
  flex: 1;
}

.product-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 5px;
}

.product-price {
  color: #666;
  font-size: 0.9rem;
}

.product-quantity {
  color: #003399;
  font-weight: 600;
  margin-left: 10px;
}

.product-subtotal {
  font-weight: 700;
  color: #003399;
  font-size: 1.1rem;
  text-align: right;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 15px;
  border-top: 2px solid #f0f0f0;
  margin-top: 15px;
  flex-wrap: wrap;
  gap: 15px;
}

.total-amount {
  font-size: 1.3rem;
  font-weight: 700;
  color: #003399;
}

.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.status-update-form {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.status-select {
  padding: 8px 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 500;
}

.btn-update {
  background: #28a745;
  color: #fff;
  padding: 8px 20px;
  border-radius: 20px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}

.btn-update:hover {
  background: #218838;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(40,167,69,0.3);
}

.btn-view {
  background: #003399;
  color: #fff;
  padding: 8px 20px;
  border-radius: 20px;
  border: none;
  font-weight: 600;
  text-decoration: none;
  transition: 0.3s;
  display: inline-block;
}

.btn-view:hover {
  background: #002266;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,51,153,0.3);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.empty-state i {
  font-size: 5rem;
  color: #ddd;
  margin-bottom: 20px;
}

.empty-state h3 {
  color: #666;
  margin-bottom: 15px;
}

.alert-new-order {
  background: #fff3cd;
  border-left: 5px solid #ffc107;
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.alert-new-order i {
  font-size: 2rem;
  color: #ffc107;
}

@media (max-width: 768px) {
  .menu-toggle { display: block; }
  nav {
    display: none;
    flex-direction: column;
    background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
    );
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    width: 100%;
  }
  nav.show { display: flex; }
  nav a { text-align: center; padding: 10px; }
  .title { font-size: 1.2rem; }
  
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filters-row {
    flex-direction: column;
  }
  
  .filter-btn,
  .reset-btn {
    width: 100%;
  }
  
  .order-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .order-footer {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .product-item {
    flex-direction: column;
    text-align: center;
  }
  
  .product-image {
    width: 100%;
    max-width: 200px;
    height: auto;
  }
  
  .product-subtotal {
    text-align: center;
  }
  
  .status-update-form {
    width: 100%;
    flex-direction: column;
  }
  
  .status-select {
    width: 100%;
  }
  
  .btn-update {
    width: 100%;
  }
}
</style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-inbox"></i> Pesanan Masuk</h2>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?= $stats['pending'] ?></div>
            <div class="stat-label">Menunggu</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon processing"><i class="fas fa-spinner"></i></div>
            <div class="stat-number"><?= $stats['processing'] ?></div>
            <div class="stat-label">Diproses</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon shipped"><i class="fas fa-truck"></i></div>
            <div class="stat-number"><?= $stats['shipped'] ?></div>
            <div class="stat-label">Dihantar</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon delivered"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?= $stats['delivered'] ?></div>
            <div class="stat-label">Selesai</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon total"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Jumlah Pesanan</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="">
            <div class="filters-row">
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Status Pesanan</label>
                    <select name="status" class="status-select">
                        <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="processing" <?= $filter_status == 'processing' ? 'selected' : '' ?>>Diproses</option>
                        <option value="shipped" <?= $filter_status == 'shipped' ? 'selected' : '' ?>>Dihantar</option>
                        <option value="delivered" <?= $filter_status == 'delivered' ? 'selected' : '' ?>>Selesai</option>
                        <option value="cancelled" <?= $filter_status == 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Cari Pesanan</label>
                    <input type="text" name="search" placeholder="No. pesanan atau nama pelanggan" value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i> Cari
                </button>
                
                <a href="pesanan_masuk.php" class="reset-btn">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- New Orders Alert -->
    <?php if($stats['pending'] > 0): ?>
    <div class="alert-new-order">
        <i class="fas fa-bell"></i>
        <div>
            <strong>Pesanan Baru!</strong>
            <p style="margin: 0; color: #666;">Anda mempunyai <?= $stats['pending'] ?> pesanan yang menunggu untuk diproses.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Tiada Pesanan</h3>
            <p>Tiada pesanan dijumpai dengan kriteria carian anda.</p>
        </div>
    <?php else: ?>
        <?php foreach($orders as $order): ?>
        <?php $products = getOrderProducts($conn, $order['id'], $usahawan_id); ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-number">
                        <?= htmlspecialchars($order['no_pesanan']) ?>
                        <?php if($order['status_pesanan'] == 'pending'): ?>
                        <span class="badge bg-danger" style="font-size: 0.7rem; margin-left: 10px;">BARU</span>
                        <?php endif; ?>
                    </div>
                    <div class="order-date">
                        <i class="far fa-calendar"></i> 
                        <?= date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])) ?>
                    </div>
                </div>
                <div class="order-badges">
                    <span class="badge bg-<?= getStatusClass($order['status_pesanan']) ?>">
                        <?= translateStatus($order['status_pesanan']) ?>
                    </span>
                    <span class="badge bg-<?= getStatusClass($order['status_bayaran']) ?>">
                        <?= translateStatus($order['status_bayaran']) ?>
                    </span>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="customer-info">
                <div class="customer-name">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($order['nama']) ?>
                </div>
                <div class="customer-contact">
                    <span><i class="fas fa-phone"></i> <?= htmlspecialchars($order['no_telefon']) ?></span>
                    <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div style="margin-top: 10px; color: #666; font-size: 0.9rem;">
                    <span style="margin-right: 15px;">
                        <i class="fas fa-<?= $order['cara_hantar'] == 'delivery' ? 'truck' : 'map-marker-alt' ?>"></i> 
                        <?= $order['cara_hantar'] == 'delivery' ? 'Hantar ke Rumah' : 'Pickup di Dropspot' ?>
                    </span>
                    <span>
                        <i class="fas fa-money-bill-wave"></i> 
                        <?= $order['cara_bayar'] == 'online' ? 'Online' : 'COD' ?>
                    </span>
                </div>
            </div>

            <!-- Product Details -->
            <div class="products-section">
                <div class="products-title">
                    <i class="fas fa-box"></i>
                    <span>Produk Anda yang Dipesan (<?= count($products) ?> item)</span>
                </div>
                
                <?php 
                $order_total = 0;
                foreach($products as $product): 
                    $order_total += $product['subtotal'];
                ?>
                <div class="product-item">
                    <img src="<?= htmlspecialchars('uploads/' . $product['gambar_url']) ?>" 
                         alt="<?= htmlspecialchars($product['nama_produk']) ?>" 
                         class="product-image"
                         onerror="this.src='assets/img/no-image.png'">
                    <div class="product-details">
                        <div class="product-name"><?= htmlspecialchars($product['nama_produk']) ?></div>
                        <div class="product-price">
                            RM <?= number_format($product['harga'], 2) ?> 
                            <span class="product-quantity">x <?= $product['kuantiti'] ?></span>
                        </div>
                    </div>
                    <div class="product-subtotal">
                        RM <?= number_format($product['subtotal'], 2) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-footer">
                <div class="total-amount">
                    Jumlah Produk Anda: RM <?= number_format($order_total, 2) ?>
                </div>
                
                <div class="action-buttons">
                    <!-- Status Update Form -->
                    <?php if($order['status_pesanan'] != 'delivered' && $order['status_pesanan'] != 'cancelled'): ?>
                    <form method="POST" class="status-update-form" onsubmit="return confirm('Adakah anda pasti untuk mengemaskini status pesanan ini?');">
                        <input type="hidden" name="pesanan_id" value="<?= $order['id'] ?>">
                        <select name="new_status" class="status-select" required>
                            <option value="">Tukar Status</option>
                            <?php if($order['status_pesanan'] == 'pending'): ?>
                            <option value="processing">Terima & Proses</option>
                            <option value="cancelled">Batalkan</option>
                            <?php elseif($order['status_pesanan'] == 'processing'): ?>
                            <option value="shipped">Tandakan Dihantar</option>
                            <option value="delivered">Tandakan Selesai</option>
                            <?php elseif($order['status_pesanan'] == 'shipped'): ?>
                            <option value="delivered">Tandakan Selesai</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" name="update_status" class="btn-update">
                            <i class="fas fa-check"></i> Kemaskini
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="detail_pesanan_masuk.php?order_id=<?= $order['id'] ?>" class="btn-view">
                        <i class="fas fa-eye"></i> Lihat Butiran
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Auto refresh every 2 minutes to check for new orders
setTimeout(function() {
    location.reload();
}, 120000);
</script>

<?php include "footer.php"; ?>

</body>
</html>