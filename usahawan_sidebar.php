<?php
// ===============================
// GLOBAL HEADER (USER + SELLER)
// ===============================
include "header.php";

// ===============================
// CHECK ROLE USAHAWAN
// ===============================
$is_usahawan = isset($_SESSION['usahawan_id']);
?>

<style>
/* =================================================
   USAHAWAN SIDEBAR – PREMIUM ANIMATED VERSION
================================================= */

/* ===== ROOT OFFSET ===== */
:root {
  --header-height: 90px;
  --sidebar-width: 260px;
}

/* ===== SIDEBAR BASE ===== */
.usahawan-sidebar {
  position: fixed;
  top: var(--header-height);
  left: 0;
  width: var(--sidebar-width);
  height: calc(100vh - var(--header-height));
  background: linear-gradient(
    180deg,
    #001F3F 0%,
    #003399 35%,
    #002855 100%
  );
  padding: 22px 14px;
  overflow-y: auto;
  box-shadow: 6px 0 25px rgba(0,0,0,0.25);
  z-index: 900;

  /* animation */
  transform: translateX(0);
  animation: sidebarFadeIn 0.6s ease forwards;
}

/* ===== ENTRY ANIMATION ===== */
@keyframes sidebarFadeIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* ===== BRAND ===== */
.usahawan-sidebar .brand {
  text-align: center;
  margin-bottom: 28px;
  animation: glowPulse 3s ease-in-out infinite;
}

@keyframes glowPulse {
  0%,100% { text-shadow: 0 0 0 rgba(255,215,0,0); }
  50% { text-shadow: 0 0 10px rgba(255,215,0,0.6); }
}

.usahawan-sidebar .brand h3 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #ffd700;
  letter-spacing: 1px;
}

.usahawan-sidebar .brand span {
  font-size: 0.85rem;
  color: #dbe3ff;
}

/* ===== MENU ===== */
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
  color: #ffffff;
  text-decoration: none;
  font-weight: 500;
  background: rgba(255,255,255,0.06);
  position: relative;
  overflow: hidden;
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
}

/* ===== HOVER SHINE EFFECT ===== */
.usahawan-menu a::before {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    120deg,
    transparent,
    rgba(255,255,255,0.35),
    transparent
  );
  transition: left 0.5s ease;
}

.usahawan-menu a:hover::before {
  left: 120%;
}

.usahawan-menu a:hover {
  background: rgba(255,215,0,0.2);
  transform: translateX(6px) scale(1.02);
}

/* ===== ACTIVE ===== */
.usahawan-menu a.active {
  background: linear-gradient(90deg, #ffd700, #ffb700);
  color: #002855;
  font-weight: 700;
}

/* ===== ICON ===== */
.usahawan-menu i {
  font-size: 1.2rem;
  width: 24px;
  text-align: center;
}

/* ===== MOBILE TOGGLE ===== */
.usahawan-toggle {
  display: none;
  position: fixed;
  top: calc(var(--header-height) + 10px);
  left: 15px;
  z-index: 1200;
  background: linear-gradient(135deg, #003399, #001F3F);
  color: #fff;
  border: none;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 1rem;
  box-shadow: 0 6px 20px rgba(0,0,0,0.35);
  cursor: pointer;
  animation: bounceIn 0.8s ease;
}

@keyframes bounceIn {
  0% { transform: scale(0.8); opacity: 0; }
  60% { transform: scale(1.1); }
  100% { transform: scale(1); opacity: 1; }
}

/* ===== MOBILE MODE ===== */
@media (max-width: 900px) {

  .usahawan-sidebar {
    transform: translateX(-100%);
  }

  .usahawan-sidebar.show {
    transform: translateX(0);
  }

  .usahawan-toggle {
    display: block;
  }
}
</style>

<?php if ($is_usahawan): ?>
<!-- ===== USAHAWAN SIDEBAR ===== -->
<aside class="usahawan-sidebar" id="usahawanSidebar">

  <div class="brand">
    <h3>USAHAWAN MODE</h3>
    <span>Pengurusan Perniagaan</span>
  </div>

  <ul class="usahawan-menu">

    <li>
      <a href="seller_dashboard.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'seller_dashboard.php' ? 'active' : '' ?>">
        <i>🧭</i> Dashboard
      </a>
    </li>

    <li>
      <a href="profile_usahawan2.php?id=<?= $_SESSION['usahawan_id'] ?>"
         class="<?= basename($_SERVER['PHP_SELF']) == 'profile_usahawan2.php' ? 'active' : '' ?>">
        <i>👤</i> Profil
      </a>
    </li>

    <li>
      <a href="produk_usahawan.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'produk_usahawan.php' ? 'active' : '' ?>">
        <i>📦</i> Produk
      </a>
    </li>

    <li>
      <a href="servis_saya.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'servis_saya.php' ? 'active' : '' ?>">
        <i>🛠️</i> Servis
      </a>
    </li>

    <li>
      <a href="pesanan_masuk.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'pesanan_masuk.php' ? 'active' : '' ?>">
        <i>🚚</i> Pesanan
      </a>
    </li>

    <li>
      <a href="jualan.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'jualan.php' ? 'active' : '' ?>">
        <i>💰</i> Jualan
      </a>
    </li>

    <li>
      <a href="laporan.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>">
        <i>📊</i> Laporan
      </a>
    </li>

    <li>
      <a href="tetapan_perniagaan.php"
         class="<?= basename($_SERVER['PHP_SELF']) == 'tetapan_perniagaan.php' ? 'active' : '' ?>">
        <i>⚙️</i> Tetapan
      </a>
    </li>

  </ul>
</aside>

<button class="usahawan-toggle" onclick="toggleUsahawanSidebar()">☰ Menu</button>
<?php endif; ?>

<script>
function toggleUsahawanSidebar() {
  document.getElementById("usahawanSidebar")?.classList.toggle("show");
}
</script>
