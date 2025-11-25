<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Get order_id from URL
if (!isset($_GET['order_id'])) {
    echo "<script>alert('Pesanan tidak dijumpai.'); window.location.href='usahawan_orders.php';</script>";
    exit;
}

$order_id = $_GET['order_id'];

// Handle status update with notes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $catatan = isset($_POST['catatan']) ? $_POST['catatan'] : '';
    
    $sql = "UPDATE pesanan SET status_pesanan = ?";
    $params = [$new_status];
    $types = "s";
    
    // Update date fields based on status
    if ($new_status == 'processing') {
        $sql .= ", tarikh_diproses = NOW()";
    } elseif ($new_status == 'shipped') {
        $sql .= ", tarikh_dihantar = NOW()";
    } elseif ($new_status == 'delivered') {
        $sql .= ", tarikh_selesai = NOW()";
    } elseif ($new_status == 'cancelled') {
        $sql .= ", tarikh_dibatalkan = NOW(), sebab_batal = ?";
        $params[] = $catatan;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $order_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo "<script>alert('Status pesanan berjaya dikemaskini!'); window.location.href='detail_pesanan_masuk.php?order_id=$order_id';</script>";
    } else {
        echo "<script>alert('Gagal mengemaskini status!');</script>";
    }
}

// Fetch order details with customer info
$sql = "SELECT p.*, u.nama, u.telefon, u.email 
        FROM pesanan p
        INNER JOIN usahawan u ON p.usahawan_id = u.id
        INNER JOIN pesanan_item pi ON p.id = pi.pesanan_id
        INNER JOIN produk pr ON pi.produk_id = pr.id
        WHERE p.id = ? AND pr.usahawan_id = ?
        GROUP BY p.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Pesanan tidak dijumpai atau anda tidak mempunyai akses.'); window.location.href='pesanan_masuk.php';</script>";
    exit;
}

$order = $result->fetch_assoc();

// Fetch only products from this usahawan
$sql = "SELECT pi.*, pr.nama, pr.gambar_url, pr.harga 
        FROM pesanan_item pi
        INNER JOIN produk pr ON pi.produk_id = pr.id
        WHERE pi.pesanan_id = ? AND pr.usahawan_id = ?
        ORDER BY pi.id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = [];
while($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}

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
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Butiran Pesanan - Sistem Usahawan Pahang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  margin: 0;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  margin-top: 90px;
}

body::before {
  content: "";
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 25% 30%, rgba(0, 0, 0, 0.05), transparent 70%),
    radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.15), transparent 70%);
  background-repeat: no-repeat;
  animation: royalWave 25s ease-in-out infinite alternate;
  z-index: -3;
  mix-blend-mode: overlay;
}

body::after {
  content: "";
  position: fixed;
  inset: 0;
  background-color: transparent;
  background-image: url("assets/img/jatapahang.png");
  background-repeat: repeat;
  background-size: 180px 180px;
  background-position: center;
  opacity: 0.15;
  filter: grayscale(5%) brightness(1.3) contrast(1.1);
  animation: watermarkFloat 40s linear infinite;
  z-index: -2;
}

@keyframes watermarkFloat {
  0% { background-position: 0 0; opacity: 0.14; }
  50% { background-position: 80px 60px; opacity: 0.18; }
  100% { background-position: 0 0; opacity: 0.14; }
}

@keyframes royalWave {
  0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
  100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
}

header {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  padding: 15px 20px;
  position: fixed;
  top: 0; left: 0; width: 100%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  flex-wrap: wrap;
}

header img.jata { height: 55px; }

.menu-toggle {
  display: none;
  font-size: 1.8rem;
  cursor: pointer;
  background: none;
  border: none;
  color: #fff;
}

nav {
  display: flex;
  gap: 15px;
}

nav a {
  color: #fff;
  padding: 8px 12px;
  font-weight: 500;
  text-decoration: none;
  transition: 0.3s;
}
nav a:hover, nav a.active { color: #ffd700; }

header .title {
  position: relative;
  color: #ffffffff;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-align: center;
  text-shadow:
    0 1px 0 #b3b3b3,
    0 2px 0 #999,
    0 3px 0 #777,
    0 4px 0 #555,
    0 5px 8px rgba(0,0,0,0.6);
  background: linear-gradient(90deg, #e6e6e6 0%, #bfbfbf 50%, #f2f2f2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
  overflow: hidden;
}

header .title::after {
  content: "";
  position: absolute;
  top: 0; left: -75%;
  width: 50%; height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.6) 50%,
    rgba(255,255,255,0) 100%
  );
  animation: textshine 4s linear infinite;
}

@keyframes textshine {
  0% { left: -75%; }
  100% { left: 125%; }
}

@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.container { 
  max-width: 1100px; 
  margin: auto; 
  padding: 20px; 
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #6c757d;
  color: #fff;
  padding: 10px 20px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  margin-bottom: 20px;
  transition: 0.3s;
}

.back-btn:hover {
  background: #5a6268;
  color: #fff;
  transform: translateX(-5px);
}

.page-header {
  background: #fff;
  border-radius: 15px;
  padding: 30px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}

.order-number-large {
  font-size: 2rem;
  font-weight: 700;
  color: #003399;
  margin-bottom: 10px;
}

.order-date-large {
  color: #666;
  font-size: 1.1rem;
  margin-bottom: 20px;
}

.status-badges {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.status-badge-large {
  padding: 10px 25px;
  border-radius: 25px;
  font-weight: 600;
  font-size: 1rem;
}

.detail-section {
  background: #fff;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  margin-bottom: 20px;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #003399;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 3px solid #003399;
  display: flex;
  align-items: center;
  gap: 10px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 15px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-label {
  font-size: 0.9rem;
  color: #666;
  font-weight: 500;
}

.info-value {
  font-size: 1.1rem;
  color: #333;
  font-weight: 600;
}

.address-box {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  border-left: 4px solid #003399;
}

.address-box p {
  margin: 5px 0;
  line-height: 1.6;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}

.products-table thead {
  background: #003399;
  color: #fff;
}

.products-table th {
  padding: 15px;
  text-align: left;
  font-weight: 600;
}

.products-table td {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.products-table tbody tr:hover {
  background: #f8f9fa;
}

.product-img-small {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid #ddd;
}

.product-name-cell {
  font-weight: 600;
  color: #333;
}

.price-cell {
  color: #666;
}

.quantity-cell {
  color: #003399;
  font-weight: 600;
}

.subtotal-cell {
  color: #003399;
  font-weight: 700;
}

.summary-box {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #dee2e6;
}

.summary-row:last-child {
  border-bottom: none;
  font-size: 1.3rem;
  font-weight: 700;
  color: #003399;
  padding-top: 15px;
  border-top: 3px solid #003399;
}

.summary-label {
  font-weight: 500;
}

.summary-value {
  font-weight: 600;
}

.status-update-section {
  background: #fff3cd;
  border-left: 5px solid #ffc107;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.status-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-top: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-weight: 600;
  color: #003399;
}

.form-group select,
.form-group textarea {
  padding: 10px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.95rem;
}

.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #003399;
}

.btn-submit {
  background: #28a745;
  color: #fff;
  padding: 12px 30px;
  border-radius: 25px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
  align-self: flex-start;
}

.btn-submit:hover {
  background: #218838;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(40,167,69,0.3);
}

.btn-print {
  background: #17a2b8;
  color: #fff;
  padding: 12px 30px;
  border-radius: 25px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.3s;
}

.btn-print:hover {
  background: #138496;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(23,162,184,0.3);
}

.action-buttons-group {
  display: flex;
  gap: 15px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.timeline {
  margin-top: 20px;
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #dee2e6;
}

.timeline-item {
  position: relative;
  padding-bottom: 30px;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -25px;
  top: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #fff;
  border: 3px solid #003399;
  z-index: 1;
}

.timeline-item.active::before {
  background: #003399;
}

.timeline-content {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 10px;
}

.timeline-title {
  font-weight: 600;
  color: #003399;
  margin-bottom: 5px;
}

.timeline-date {
  color: #666;
  font-size: 0.9rem;
}

footer {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  color: #fff;
  padding: 30px 20px;
  margin-top: 40px;
  text-align: center;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

footer .footer-content {
  max-width: 1100px;
  margin: auto;
  position: relative;
  z-index: 1;
}

footer img {
  height: 60px;
  margin-bottom: 15px;
  filter: drop-shadow(0 3px 5px rgba(0,0,0,0.4));
}

footer p,
footer .copyright,
footer strong {
  color: #f8f8f8;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-shadow:
    0 1px 0 #ccc,
    0 2px 0 #aaa,
    0 3px 0 #888,
    0 4px 0 #666,
    0 5px 0 #444,
    0 6px 6px rgba(0,0,0,0.6);
}

footer .copyright {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.2);
  font-size: 0.85rem;
  color: #ddd;
}

@media print {
  header, footer, .back-btn, .status-update-section, nav, .menu-toggle, .action-buttons-group {
    display: none !important;
  }
  
  body {
    background: #fff;
    margin: 0;
    padding: 20px;
  }
  
  body::before, body::after {
    display: none;
  }
  
  .container {
    max-width: 100%;
    padding: 0;
  }
  
  .detail-section {
    box-shadow: none;
    border: 1px solid #ddd;
    page-break-inside: avoid;
  }
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
  
  .order-number-large {
    font-size: 1.5rem;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .products-table {
    font-size: 0.9rem;
  }
  
  .products-table th,
  .products-table td {
    padding: 10px 5px;
  }
  
  .action-buttons-group {
    flex-direction: column;
  }
  
  .btn-print,
  .btn-submit {
    width: 100%;
    justify-content: center;
  }
}
</style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="usahawan_orders.php" class="active"><strong>Pesanan Masuk</strong></a>
  </nav>
</header>

<div class="container">
    <a href="pesanan_masuk.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali ke Pesanan Masuk
    </a>

    <div class="page-header">
        <div class="order-number-large">
            <?= htmlspecialchars($order['no_pesanan']) ?>
            <?php if($order['status_pesanan'] == 'pending'): ?>
            <span class="badge bg-danger" style="font-size: 0.8rem; margin-left: 15px;">BARU</span>
            <?php endif; ?>
        </div>
        <div class="order-date-large">
            <i class="far fa-calendar"></i> 
            Tarikh Pesanan: <?= date('d F Y, h:i A', strtotime($order['tarikh_pesanan'])) ?>
        </div>
        <div class="status-badges">
            <span class="badge bg-<?= getStatusClass($order['status_pesanan']) ?> status-badge-large">
                <i class="fas fa-shopping-bag"></i> Status Pesanan: <?= translateStatus($order['status_pesanan']) ?>
            </span>
            <span class="badge bg-<?= getStatusClass($order['status_bayaran']) ?> status-badge-large">
                <i class="fas fa-credit-card"></i> Status Bayaran: <?= translateStatus($order['status_bayaran']) ?>
            </span>
        </div>
    </div>

    <!-- Status Update Form (Only if not completed or cancelled) -->
    <?php if($order['status_pesanan'] != 'delivered' && $order['status_pesanan'] != 'cancelled'): ?>
    <div class="status-update-section">
        <h4 style="margin: 0 0 5px 0; color: #856404;">
            <i class="fas fa-edit"></i> Kemaskini Status Pesanan
        </h4>
        <p style="margin: 0; color: #856404; font-size: 0.9rem;">
            Sila kemaskini status pesanan mengikut kemajuan pemprosesan anda.
        </p>
        
        <form method="POST" class="status-form" onsubmit="return confirm('Adakah anda pasti untuk mengemaskini status pesanan ini?');">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            
            <div class="form-group">
                <label><i class="fas fa-tasks"></i> Status Baru</label>
                <select name="new_status" required>
                    <option value="">-- Pilih Status Baru --</option>
                    <?php if($order['status_pesanan'] == 'pending'): ?>
                    <option value="processing">✓ Terima & Proses Pesanan</option>
                    <option value="cancelled">✗ Batalkan Pesanan</option>
                    <?php elseif($order['status_pesanan'] == 'processing'): ?>
                    <option value="shipped">🚚 Tandakan Sudah Dihantar</option>
                    <option value="delivered">✓ Tandakan Sudah Selesai</option>
                    <?php elseif($order['status_pesanan'] == 'shipped'): ?>
                    <option value="delivered">✓ Tandakan Sudah Selesai</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Catatan (Opsional)</label>
                <textarea name="catatan" rows="3" placeholder="Masukkan catatan atau sebab pembatalan..."></textarea>
            </div>
            
            <button type="submit" name="update_status" class="btn-submit">
                <i class="fas fa-check-circle"></i> Kemaskini Status
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Customer Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user"></i>
            Maklumat Pelanggan
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label"><i class="fas fa-user-circle"></i> Nama Penuh</span>
                <span class="info-value"><?= htmlspecialchars($order['nama']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label"><i class="fas fa-phone"></i> No. Telefon</span>
                <span class="info-value">
                    <a href="tel:<?= htmlspecialchars($order['telefon']) ?>" style="color: #003399; text-decoration: none;">
                        <?= htmlspecialchars($order['telefon']) ?>
                    </a>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label"><i class="fas fa-envelope"></i> email</span>
                <span class="info-value">
                    <a href="mailto:<?= htmlspecialchars($order['email']) ?>" style="color: #003399; text-decoration: none;">
                        <?= htmlspecialchars($order['email']) ?>
                    </a>
                </span>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-info-circle"></i>
            Maklumat Pesanan
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label"><i class="fas fa-truck"></i> Cara Penghantaran</span>
                <span class="info-value">
                    <?= $order['cara_hantar'] == 'delivery' ? 'Hantar ke Rumah' : 'Pickup di Dropspot' ?>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label"><i class="fas fa-money-bill-wave"></i> Cara Bayaran</span>
                <span class="info-value">
                    <?= $order['cara_bayar'] == 'online' ? 'Bayaran Online' : 'Cash on Delivery (COD)' ?>
                </span>
            </div>
            
            <?php if($order['cara_bayar'] == 'online' && !empty($order['rujukan_bayaran'])): ?>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-receipt"></i> Rujukan Bayaran</span>
                <span class="info-value"><?= htmlspecialchars($order['rujukan_bayaran']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delivery Address -->
    <?php if($order['cara_hantar'] == 'delivery'): ?>
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-map-marker-alt"></i>
            Alamat Penghantaran
        </div>
        
        <div class="address-box">
            <p><strong><?= htmlspecialchars($order['nama_penerima'] ?? $order['nama']) ?></strong></p>
            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($order['no_telefon_penerima'] ?? $order['telefon']) ?></p>
            <p style="margin-top: 10px;"><?= nl2br(htmlspecialchars($order['alamat'])) ?></p>
            <?php if(!empty($order['poskod'])): ?>
            <p><?= htmlspecialchars($order['poskod']) ?> <?= htmlspecialchars($order['bandar'] ?? '') ?></p>
            <?php endif; ?>
            <?php if(!empty($order['negeri'])): ?>
            <p><?= htmlspecialchars($order['negeri']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products List -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-box-open"></i>
            Produk Anda yang Dipesan
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th style="text-align: center;">Kuantiti</th>
                    <th style="text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach($order_items as $item): 
                    $item_total = $item['subtotal'];
                    $subtotal += $item_total;
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?= htmlspecialchars('uploads/' . $item['gambar_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['nama_produk']) ?>" 
                                 class="product-img-small"
                                 onerror="this.src='assets/img/no-image.png'">
                            <span class="product-name-cell"><?= htmlspecialchars($item['nama_produk']) ?></span>
                        </div>
                    </td>
                    <td class="price-cell">RM <?= number_format($item['harga'], 2) ?></td>
                    <td class="quantity-cell" style="text-align: center;"><?= $item['kuantiti'] ?></td>
                    <td class="subtotal-cell" style="text-align: right;">RM <?= number_format($item_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <span class="summary-label">JUMLAH PRODUK ANDA:</span>
                <span class="summary-value">RM <?= number_format($subtotal, 2) ?></span>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066ff;">
            <p style="margin: 0; color: #004085; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> 
                <strong>Nota:</strong> Jumlah di atas hanya untuk produk anda sahaja. Pelanggan mungkin membeli produk dari usahawan lain dalam pesanan yang sama.
            </p>
        </div>
    </div>

    <!-- Order Timeline -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-history"></i>
            Timeline Pesanan
        </div>
        
        <div class="timeline">
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Diterima</div>
                    <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])) ?></div>
                </div>
            </div>
            
            <?php if($order['status_pesanan'] != 'pending' && $order['status_pesanan'] != 'cancelled'): ?>
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Sedang Diproses</div>
                    <div class="timeline-date">
                        <?= !empty($order['tarikh_kemaskini']) ? date('d/m/Y H:i', strtotime($order['tarikh_kemaskini'])) : '-' ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($order['status_pesanan'] == 'shipped' || $order['status_pesanan'] == 'delivered'): ?>
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Dihantar</div>
                    <div class="timeline-date">
                        <?= !empty($order['tarikh_dihantar']) ? date('d/m/Y H:i', strtotime($order['tarikh_dihantar'])) : '-' ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($order['status_pesanan'] == 'delivered'): ?>
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Selesai</div>
                    <div class="timeline-date">
                        <?= !empty($order['tarikh_selesai']) ? date('d/m/Y H:i', strtotime($order['tarikh_selesai'])) : '-' ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($order['status_pesanan'] == 'cancelled'): ?>
            <div class="timeline-item active">
                <div class="timeline-content">
                    <div class="timeline-title" style="color: #dc3545;">Pesanan Dibatalkan</div>
                    <div class="timeline-date">
                        <?= !empty($order['tarikh_dibatalkan']) ? date('d/m/Y H:i', strtotime($order['tarikh_dibatalkan'])) : '-' ?>
                    </div>
                    <?php if(!empty($order['sebab_batal'])): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #fff; border-radius: 5px; border-left: 3px solid #dc3545;">
                        <strong>Sebab:</strong> <?= htmlspecialchars($order['sebab_batal']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Notes -->
    <?php if(!empty($order['nota_pesanan'])): ?>
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-sticky-note"></i>
            Catatan Pesanan dari Pelanggan
        </div>
        <div class="address-box">
            <p><?= nl2br(htmlspecialchars($order['nota_pesanan'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons-group">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Cetak Butiran Pesanan
        </button>
    </div>
</div>

<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | email: info@pahang.gov.my</p>
    <div class="copyright">
      © <?= date("Y") ?> Kerajaan Negeri Pahang. Hak cipta terpelihara.
    </div>
  </div>
</footer>

<script>
function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
}
</script>

</body>
</html>