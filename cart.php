<?php
session_start();
include "connection.php";

// Semak jika pengguna belum login
if (!isset($_SESSION['usahawan_id'])) {
  echo "<script>alert('Sila log masuk untuk lihat troli anda.'); window.location.href='login.php';</script>";
  exit;
}

$user_id = $_SESSION['usahawan_id'];
$sql = "SELECT c.*, p.nama, p.harga, p.gambar_url 
        FROM cart c 
        JOIN produk p ON c.produk_id = p.id
        WHERE c.usahawan_id = $user_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Troli Saya - Sistem Usahawan Pahang</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">

<style>
/* Jika tidak asingkan, boleh paste terus CSS header/footer di sini */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ===== Background Premium dengan Watermark Jata Pahang ===== */
body {
  margin: 0;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  margin-top: 90px;
}

/* ✨ Cahaya lembut keemasan & hitam bergerak */
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

/* 🏛️ Multiple Watermark Jata Pahang - lebih jelas */
body::after {
  content: "";
  position: fixed;
  inset: 0;
  background-color: transparent;
  background-image: url("assets/img/jatapahang.png");
  background-repeat: repeat;
  background-size: 180px 180px;
  background-position: center;
  opacity: 0.15; /* 🔆 Naikkan dari 0.07 → 0.15 supaya lebih nampak */
  filter: grayscale(5%) brightness(1.3) contrast(1.1);
  animation: watermarkFloat 40s linear infinite;
  z-index: -2;
}

/* 🌫️ Animasi lembut watermark */
@keyframes watermarkFloat {
  0% { background-position: 0 0; opacity: 0.14; }
  50% { background-position: 80px 60px; opacity: 0.18; }
  100% { background-position: 0 0; opacity: 0.14; }
}

/* 🪄 Efek cahaya bergerak lembut */
@keyframes royalWave {
  0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
  100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
}

/* ===== Kad (card) Optional ===== */
.card {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 215, 0, 0.4);
  border-radius: 14px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 25px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}


/* ===== Header ===== */
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
.title { color: #fff; font-size: 1.4rem; font-weight: 700; }

.menu-toggle {
  display: none;
  font-size: 1.8rem;
  cursor: pointer;
  background: none;
  border: none;
  color: #fff;
}

/* ===== Navbar ===== */
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

/* ===== 3D Metallic Title ===== */
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

/* Subtle animated shine */
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

/* ===== Metallic Shine Animation ===== */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


/* ===== CART TABLE DESIGN ===== */
main {
  max-width: 1100px;
  margin: 40px auto;
  background: rgba(255,255,255,0.85);
  border-radius: 15px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  padding: 30px;
  backdrop-filter: blur(6px);
}

h3 {
  text-align: center;
  margin-bottom: 25px;
  color: #003399;
  text-shadow: 0 1px 1px rgba(0,0,0,0.1);
  font-weight: 700;
}

.table {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
}

.table thead {
  background: linear-gradient(135deg, #003399, #0066FF);
  color: #fff;
}

.table th, .table td {
  vertical-align: middle;
  text-align: center;
  border-color: #ddd;
}

.table img {
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* ===== CHECKOUT BUTTON ===== */
.checkout-btn {
  display: block;
  width: 220px;
  margin: 30px auto 10px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #FFD700, #FFC107);
  color: #111;
  font-weight: 700;
  font-size: 1rem;
  border: none;
  border-radius: 30px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
}

.checkout-btn:hover {
  background: linear-gradient(135deg, #FFB300, #FFEB3B);
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.25);
}

/* Kosong message */
.empty {
  text-align: center;
  font-size: 1.1rem;
  color: #444;
  padding: 40px 0;
}

/* ===== Footer ===== */
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

/* ===== 3D Metallic Text ===== */
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
  transition: transform 0.3s ease, text-shadow 0.3s ease;
}

/* Glow and depth on hover */
footer p:hover,
footer strong:hover {
  transform: translateY(-2px);
  text-shadow:
    0 1px 0 #fff,
    0 2px 0 #ddd,
    0 3px 0 #bbb,
    0 4px 0 #999,
    0 5px 0 #777,
    0 8px 12px rgba(0, 0, 0, 0.7),
    0 0 10px rgba(255, 255, 255, 0.3);
}

/* Copyright (subtle) */
footer .copyright {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.2);
  font-size: 0.85rem;
  color: #ddd;
  text-shadow:
    0 1px 0 #999,
    0 2px 0 #666,
    0 3px 3px rgba(0,0,0,0.6);
}

/* Metallic Shine Animation */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .slideshow-container { height: 300px; }
  .function-btn { min-height: 130px; }
  .function-btn i { font-size: 2rem; }
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
  animation: metalshine 6s linear infinite;
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    width: 100%;
  }
  nav.show { display: flex; }
  nav a { text-align: center; padding: 10px; font-size: 1rem; }
  .title { font-size: 1.2rem; }
  .slideshow-container { height: 220px; }
  .function-grid { gap: 18px; }
  .function-btn { min-height: 110px; padding: 18px; }
  .function-btn i { font-size: 1.8rem; }
  .function-btn span { font-size: 0.9rem; }
}

@media (max-width: 480px) {
  .slideshow-container { height: 180px; }
  .function-btn { padding: 15px; }
}

.checkout-btn {
  display: block;
  width: 250px;
  margin: 30px auto 10px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #FFD700, #FFC107);
  color: #111;
  font-weight: 700;
  font-size: 1rem;
  border: none;
  border-radius: 30px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
}
.checkout-btn:hover {
  background: linear-gradient(135deg, #FFB300, #FFEB3B);
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.25);
}

.delete-btn {
  background: #ff4d4d;
  border: none;
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
.delete-btn:hover {
  background: #d93025;
}
</style>
</head>

<body>
<!-- ===== HEADER ===== -->
<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="cart.php" class="active"><strong>🛒 Troli</strong></a>
  </nav>
</header>

<!-- ===== CART CONTENT ===== -->
<main>
  <h3>🛒 Troli Anda</h3>

  <?php if ($result && $result->num_rows > 0): ?>
  <form id="checkoutForm" method="POST" action="checkout.php">
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr>
          <th>Pilih</th>
          <th>Gambar</th>
          <th>Nama Produk</th>
          <th>Harga (RM)</th>
          <th>Kuantiti</th>
          <th>Jumlah (RM)</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $total = 0;
        while($row = $result->fetch_assoc()): 
          $subtotal = $row['harga'] * $row['kuantiti'];
          $total += $subtotal;
        ?>
        <tr id="row<?= $row['id'] ?>" data-subtotal="<?= $subtotal ?>">
  <td><input type="checkbox" name="selected_items[]" value="<?= $row['id'] ?>" onchange="updateTotal()"></td>
  <td><img src="uploads/<?= htmlspecialchars($row['gambar_url']) ?>" width="80" height="80" alt=""></td>
  <td><?= htmlspecialchars($row['nama']) ?></td>
  <td><?= number_format($row['harga'], 2) ?></td>
  <td><?= htmlspecialchars($row['kuantiti']) ?></td>
  <td><?= number_format($subtotal, 2) ?></td>
  <td><button type="button" class="delete-btn" onclick="padamItem(<?= $row['id'] ?>)">Padam</button></td>
</tr>

        <?php endwhile; ?>
        <tr style="font-weight:bold; background:#fafafa;">
  <td colspan="5" class="text-end">Jumlah Keseluruhan (Item Dipilih):</td>
  <td colspan="2">RM <span id="totalSelected">0.00</span></td>
</tr>

      </tbody>
    </table>
  </div>

  <button type="submit" class="checkout-btn">💳 Checkout Item Dipilih</button>
  </form>

  <?php else: ?>
    <p class="empty">Troli anda masih kosong.</p>
  <?php endif; ?>
</main>

<!-- ===== FOOTER ===== -->
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
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}

// ===== Padam item dari cart =====
function padamItem(cartId) {
  if (confirm("Adakah anda pasti mahu padam item ini dari troli?")) {
    fetch("delete_cart.php?id=" + cartId)
      .then(response => response.text())
      .then(data => {
        alert(data);
        const row = document.getElementById("row" + cartId);
        if (row) row.remove();
        updateTotal(); // kira semula selepas padam
      })
      .catch(err => alert("Ralat: " + err));
  }
}

// ===== Kira semula jumlah berdasarkan item dipilih =====
function updateTotal() {
  const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
  let total = 0;
  checkboxes.forEach(cb => {
    if (cb.checked) {
      const row = cb.closest('tr');
      total += parseFloat(row.dataset.subtotal);
    }
  });
  document.getElementById('totalSelected').textContent = total.toFixed(2);
}

// ===== Semak item dipilih sebelum checkout =====
document.getElementById("checkoutForm").addEventListener("submit", function(e){
  const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
  if (checkboxes.length === 0) {
    alert("Sila pilih sekurang-kurangnya satu item untuk checkout.");
    e.preventDefault();
  }
});
</script>

</body>
</html>
