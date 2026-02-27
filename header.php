<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_usahawan = !empty($_SESSION['usahawan_id']);

$current_page = basename($_SERVER['PHP_SELF']);
$show_cart = ($current_page === 'promosi-pasaran.php');

$loginNama  = null;
$loginJenis = null;

if ($is_usahawan) {
    $stmt = $conn->prepare("
        SELECT nama, jenis 
        FROM usahawan 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $_SESSION['usahawan_id']);
    $stmt->execute();
    $stmt->bind_result($loginNama, $loginJenis);
    $stmt->fetch();
    $stmt->close();

    include "usahawan_sidebar.php";


//TROLI UNTUK PAGE PRODUK
$cart_count = 0;

if ($show_cart && isset($_SESSION['usahawan_id'])) {
    $uid = (int)$_SESSION['usahawan_id'];

    $result_cart = $conn->query("
        SELECT COUNT(*) AS total 
        FROM cart 
        WHERE usahawan_id = $uid
    ");

    if ($result_cart) {
        $cart_count = $result_cart->fetch_assoc()['total'];
    }
}
}


?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Usahawan Pahang</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">

  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
      background-attachment: fixed;
      color: #111;
      overflow-x: hidden;
      position: relative;
      margin-top: 90px;
    }

    body::before { /* ... */ }
    body::after { /* ... */ }

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

/* ===== RESPONSIVE HEADER ===== */
@media (max-width: 900px) {

  header {
    flex-wrap: nowrap;
  }

  .title {
    font-size: 1.1rem;
    text-align: left;
    margin-left: 10px;
    flex: 1;
  }

  /* Show hamburger */
  .menu-toggle {
    display: block;
  }

  /* Hide menu by default */
  nav {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: linear-gradient(135deg, #001F3F, #003399);
    flex-direction: column;
    gap: 0;
    display: none;
  }

  nav a {
    padding: 14px 20px;
    border-top: 1px solid rgba(255,255,255,0.15);
  }

  nav.show {
    display: flex;
    animation: slideDown 0.3s ease;
  }
}

/* Smooth dropdown */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-info {
  color: #fff;
  font-size: 0.85rem;
  text-align: right;
  line-height: 1.3;
  opacity: 0.9;
}

.login-info strong {
  font-weight: 600;
}

.login-info small {
  font-size: 0.75rem;
  opacity: 0.85;
}

/* ===== DROPDOWN ===== */
.dropdown {
  position: relative;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
}

.arrow {
  font-size: 0.7rem;
  transition: 0.3s;
}

.dropdown-content {
  position: absolute;
  top: 100%;
  left: 0;
  background: #fff;
  min-width: 200px;
  border-radius: 6px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.15);
  overflow: hidden;

  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: 0.3s ease;
}

.dropdown-content a {
  display: block;
  padding: 10px 14px;
  color: #333;
}

.dropdown-content a:hover {
  background: #f2f2f2;
}

/* Desktop hover */
@media (min-width: 901px) {
  .dropdown:hover .dropdown-content {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  .dropdown:hover .arrow {
    transform: rotate(180deg);
  }
}

/* Mobile style */
@media (max-width: 900px) {

  .dropdown-content {
    position: static;
    background: transparent;
    box-shadow: none;
    opacity: 1;
    visibility: visible;
    transform: none;
    display: none;
  }

  .dropdown-content a {
    color: #fff;
    padding-left: 30px;
  }

  .dropdown.show .dropdown-content {
    display: block;
  }

  .dropdown.show .arrow {
    transform: rotate(180deg);
  }
}

/* ===== CART ICON PREMIUM ===== */
.cart-wrapper {
  position: relative;
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.25s ease;
  margin-left: 15px;
  backdrop-filter: blur(4px);
}

.cart-wrapper i {
  font-size: 20px;
  color: #ffffff;
}

.cart-wrapper:hover {
  background: #ffd700;
  transform: translateY(-2px);
}

.cart-wrapper:hover i {
  color: #001F3F;
}

/* ===== BADGE ===== */
.cart-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 22px;
  height: 22px;
  background: #ff3b30;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(0,0,0,0.4);
  border: 2px solid #001F3F;
}

.logout-btn {
  padding: 8px 12px;
  border-radius: 6px;
  color: #ffffff;
  font-weight: 500;
  opacity: 0.9;
  transition: 0.2s ease;
}

.logout-btn:hover {
  color: #ffd700;
  opacity: 1;
}

  </style>
</head>

<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">

  <h1 class="title">Sistem Usahawan Pahang</h1>

  <button class="menu-toggle" onclick="toggleMenu()" aria-label="Buka Menu">
    ☰
  </button>

  <?php if ($is_usahawan && $loginNama): ?>
  <div class="login-info">
    Login sebagai:<br>
    <strong><?= htmlspecialchars($loginNama) ?></strong> <!---  developer mode for identify user-->
    <small>(<?= htmlspecialchars($loginJenis) ?>)</small>
  </div>
<?php endif; ?>

    <nav id="navMenu">
      <a href="index.php" class="active"><strong>Laman Utama</strong></a>
    
      <div class="dropdown">
        <a href="#" class="dropdown-toggle">
          <strong>Pesanan</strong>
          <i class="fas fa-chevron-down arrow"></i>
        </a>
        <div class="dropdown-content">
          <a href="pesanan_detail.php">Produk</a>
          <a href="customer_booking.php">Servis</a>
        </div>
      </div>

      <?php if ($is_usahawan): ?>
          <a href="logout.php" class="logout-btn">
              <i class="fas fa-sign-out-alt"></i> Logout
          </a>
      <?php else: ?>
          <a href="login.php"><strong>Log Masuk</strong></a>
      <?php endif; ?>

      <?php if ($show_cart): ?>
        <div class="cart-wrapper" onclick="bukaCart()">
            <i class="fas fa-cart-shopping"></i>
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>
</header>


<main class="page-content">

<script>
  function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
  }

  // Tutup menu bila klik luar (mobile)
  document.addEventListener('click', function(e) {
    const nav = document.getElementById('navMenu');
    const toggle = document.querySelector('.menu-toggle');

    if (!nav.contains(e.target) && !toggle.contains(e.target)) {
      nav.classList.remove('show');
    }
  });

    // Dropdown click (mobile)
  document.querySelectorAll('.dropdown-toggle').forEach(item => {
    item.addEventListener('click', function(e) {
      if (window.innerWidth <= 900) {
        e.preventDefault();
        this.parentElement.classList.toggle('show');
      }
    });
  });

  // Auto close dropdown bila klik link
  document.querySelectorAll('.dropdown-content a').forEach(link => {
    link.addEventListener('click', function() {
      document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
      document.getElementById('navMenu').classList.remove('show');
    });
  });

  
</script>

<script>
  function bukaCart(){
      window.location.href = "cart.php";
  }
</script>
