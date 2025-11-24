<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Ambil semua pesanan pengguna dengan JOIN ke butiran pesanan dan produk
$sql = "SELECT p.*, 
        COUNT(bp.id) as jumlah_item,
        SUM(bp.kuantiti) as jumlah_produk
        FROM pesanan p
        LEFT JOIN pesanan_item bp ON p.id = bp.pesanan_id
        WHERE p.usahawan_id = ? 
        GROUP BY p.id
        ORDER BY p.tarikh_pesanan DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while($row = $result->fetch_assoc()) {
    $orders[] = $row;
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

// Fungsi untuk mendapatkan butiran produk pesanan
function getOrderProducts($conn, $pesanan_id) {
    $sql = "SELECT bp.*, pr.nama, pr.gambar_url, pr.harga 
            FROM pesanan_item bp
            INNER JOIN produk pr ON bp.produk_id = pr.id
            WHERE bp.pesanan_id = ?
            ORDER BY bp.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pesanan_id);
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
<title>Pesanan Saya - Sistem Usahawan Pahang</title>
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

.order-body {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.order-label {
  font-size: 0.85rem;
  color: #666;
  font-weight: 500;
}

.order-value {
  font-size: 1rem;
  color: #333;
  font-weight: 600;
}

/* Product Details Section */
.products-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

.products-title-static {
  font-weight: 600;
  color: #003399;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 8px;
}

.products-list-static {
  margin-top: 15px;
}

.no-products {
  text-align: center;
  padding: 30px;
  color: #999;
}

.no-products i {
  font-size: 3rem;
  margin-bottom: 10px;
  display: block;
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
}

.total-amount {
  font-size: 1.3rem;
  font-weight: 700;
  color: #003399;
}

.view-details-btn {
  background: #003399;
  color: #fff;
  padding: 8px 20px;
  border-radius: 20px;
  border: none;
  font-weight: 600;
  text-decoration: none;
  transition: 0.3s;
}

.view-details-btn:hover {
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

.empty-state a {
  display: inline-block;
  margin-top: 15px;
  background: #FFD700;
  color: #003399;
  padding: 12px 30px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}

.empty-state a:hover {
  background: #FFC107;
  transform: translateY(-2px);
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
  
  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .order-footer {
    flex-direction: column;
    gap: 15px;
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
    <a href="my_orders.php" class="active"><strong>Pesanan Saya</strong></a>
  </nav>
</header>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-shopping-bag"></i> Pesanan Saya</h2>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Tiada Pesanan</h3>
            <p>Anda belum membuat sebarang pesanan lagi.</p>
            <a href="promosi-pasaran.php"><i class="fas fa-shopping-cart"></i> Mula Membeli-belah</a>
        </div>
    <?php else: ?>
        <?php foreach($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-number"><?= htmlspecialchars($order['no_pesanan']) ?></div>
                    <div class="order-date">
                        <i class="far fa-calendar"></i> 
                        <?= date('d/m/Y H:i', strtotime($order['tarikh_pesanan'])) ?>
                    </div>
                </div>
                <div>
                    <span class="badge bg-<?= getStatusClass($order['status_pesanan']) ?>">
                        <?= translateStatus($order['status_pesanan']) ?>
                    </span>
                </div>
            </div>

            <div class="order-body">
                <div class="order-info">
                    <span class="order-label">Status Bayaran</span>
                    <span class="order-value">
                        <span class="badge bg-<?= getStatusClass($order['status_bayaran']) ?>">
                            <?= translateStatus($order['status_bayaran']) ?>
                        </span>
                    </span>
                </div>
                <div class="order-info">
                    <span class="order-label">Cara Penghantaran</span>
                    <span class="order-value">
                        <?= $order['cara_hantar'] == 'delivery' ? 'Hantar ke Rumah' : 'Pickup di Dropspot' ?>
                    </span>
                </div>
                <div class="order-info">
                    <span class="order-label">Cara Bayaran</span>
                    <span class="order-value">
                        <?= $order['cara_bayar'] == 'online' ? 'Online' : 'COD' ?>
                    </span>
                </div>
                <div class="order-info">
                    <span class="order-label">Jumlah Item</span>
                    <span class="order-value">
                        <?= $order['jumlah_item'] ?> jenis produk (<?= $order['jumlah_produk'] ?> unit)
                    </span>
                </div>
            </div>

            <!-- Product Details Section - Always Visible -->
            <div class="products-section">
                <div class="products-title-static">
                    <i class="fas fa-box"></i>
                    <span>Produk yang Dipesan</span>
                </div>
                
                <div class="products-list-static">
                    <?php 
                    $products = getOrderProducts($conn, $order['id']);
                    if (!empty($products)):
                        foreach($products as $product): 
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
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>Tiada butiran produk</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-footer">
                <div class="total-amount">
                    Jumlah: RM <?= number_format($order['jumlah_bayaran'], 2) ?>
                </div>
                <a href="detail_penuh_pesanan.php?order_id=<?= $order['id'] ?>" class="view-details-btn">
                    <i class="fas fa-eye"></i> Lihat Butiran Penuh
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | Emel: info@pahang.gov.my</p>
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