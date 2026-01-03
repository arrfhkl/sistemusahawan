<?php
/* =========================================
                 USAHAWAN SIDEBAR 
========================================= */

if (empty($_SESSION['usahawan_id'])) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ================= ROOT ================= */
:root {
  --header-height: 90px;
  --sidebar-width: 260px;

  /* TEMA ASAL */
  --royal-dark: #001F3F;
  --royal-mid: #003399;
  --royal-bright: #0066FF;

  --gold: #FFD700;

  --text-light: #ffffff;
  --text-muted: #dbe3ff;
}

/* ================= TOGGLE BAR ================= */
.usahawan-toggle-bar {
  position: fixed;
  top: calc(var(--header-height) + 16px);
  left: 0;

  height: 34px;
  padding: 0 14px;

  background: linear-gradient(
    135deg,
    var(--royal-dark),
    var(--royal-mid)
  );

  color: var(--text-light);
  border-radius: 0 18px 18px 0;

  display: flex;
  align-items: center;
  gap: 8px;

  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 1.6px;

  cursor: pointer;
  z-index: 910;

  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  transition: background .2s ease, padding .2s ease;
}

.usahawan-toggle-bar span {
  color: var(--gold);
}

/* ================= SIDEBAR ================= */
.usahawan-sidebar {
  position: fixed;
  top: var(--header-height);
  left: 0;

  width: var(--sidebar-width);
  height: calc(100vh - var(--header-height));

  background: linear-gradient(
    180deg,
    var(--royal-dark),
    var(--royal-mid),
    var(--royal-bright)
  );

  padding: 20px 14px;
  z-index: 900;

  box-shadow: 6px 0 20px rgba(0,0,0,0.35);

  transform: translateX(-100%);
  transition: transform .35s ease;

  margin: 0 !important;
}

.usahawan-sidebar * {
  margin-top: 0;
}

/* open */
.usahawan-sidebar.open {
  transform: translateX(0);
}

/* when sidebar open, toggle becomes icon only */
.usahawan-sidebar.open ~ .usahawan-toggle-bar {
  padding: 0 10px;
}

.usahawan-sidebar.open ~ .usahawan-toggle-bar span {
  display: none;
}

/* ================= BRAND ================= */
.usahawan-sidebar .brand {
  text-align: center;
  padding-bottom: 14px;
  margin-bottom: 18px;
  border-bottom: 1px solid rgba(255,255,255,0.25);
}

.usahawan-sidebar .brand h3 {
  color: var(--gold);
  font-size: 1.05rem;
  letter-spacing: 1.2px;
}

.usahawan-sidebar .brand span {
  color: var(--text-muted);
  font-size: 0.85rem;
}

/* ================= MENU ================= */
.usahawan-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.usahawan-menu li {
  margin-bottom: 12px;
}

.usahawan-menu a {
  display: flex;
  align-items: center;
  gap: 12px;

  padding: 12px 14px;
  border-radius: 12px;

  color: var(--text-light);
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;

  background: rgba(255,255,255,0.08);
  transition: background .25s ease, transform .25s ease;
}

.usahawan-menu a:hover {
  background: rgba(255,215,0,0.22);
  transform: translateX(6px);
}

.usahawan-menu a.active {
  background: linear-gradient(90deg, #FFD700, #FFB700);
  color: var(--royal-dark);
  font-weight: 700;
}

/* ================= MOBILE ================= */
@media (max-width: 900px) {
  .usahawan-sidebar {
    transform: translateX(-100%);
  }
  .usahawan-sidebar.open {
    transform: translateX(0);
  }
}
</style>

<!-- ===== SIDEBAR ===== -->
<aside class="usahawan-sidebar" id="usahawanSidebar">

  <div class="brand">
    <h3>USAHAWAN MODE</h3>
    <span>Pengurusan Perniagaan</span>
  </div>

  <ul class="usahawan-menu">
    <li><a href="seller_dashboard.php" class="<?= $current_page=='seller_dashboard.php'?'active':'' ?>">🧭 Dashboard</a></li>
    <li><a href="profile_usahawan2.php" class="<?= $current_page=='profile_usahawan2.php'?'active':'' ?>">👤 Profil</a></li>
    <li><a href="produk_usahawan.php" class="<?= $current_page=='produk_usahawan.php'?'active':'' ?>">📦 Produk</a></li>
    <li><a href="servis_saya.php" class="<?= $current_page=='servis_saya.php'?'active':'' ?>">🛠️ Servis</a></li>
    <li><a href="pesanan_masuk.php" class="<?= $current_page=='pesanan_masuk.php'?'active':'' ?>">🚚 Pesanan</a></li>
    <li><a href="jualan.php" class="<?= $current_page=='jualan.php'?'active':'' ?>">💰 Jualan</a></li>
    <li><a href="laporan.php" class="<?= $current_page=='laporan.php'?'active':'' ?>">📊 Laporan</a></li>
    <li><a href="tetapan_perniagaan.php" class="<?= $current_page=='tetapan_perniagaan.php'?'active':'' ?>">⚙️ Tetapan</a></li>
  </ul>

</aside>

<!-- ===== TOGGLE BAR ===== -->
<div class="usahawan-toggle-bar" onclick="toggleUsahawanSidebar()">
  <i id="usahawanToggleIcon">☰</i>
  <span>USAHAWAN</span>
</div>

<script>
function toggleUsahawanSidebar() {
  const sidebar = document.getElementById("usahawanSidebar");
  const icon = document.getElementById("usahawanToggleIcon");

  sidebar.classList.toggle("open");
  icon.textContent = sidebar.classList.contains("open") ? "◂" : "☰";
}
</script>
