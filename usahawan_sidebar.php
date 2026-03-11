<?php
/* =========================================
                 USAHAWAN SIDEBAR 
========================================= */

if (empty($_SESSION['usahawan_id'])) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);

// ── NOTIFICATION COUNTS ──────────────────────────────────────────
$uid = (int) $_SESSION['usahawan_id'];
$pesanan_count = 0;
$booking_count = 0;

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pesanan WHERE usahawan_id = ? AND status_pesanan NOT IN ('completed', 'cancelled')");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->bind_result($pesanan_count);
    $stmt->fetch();
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM servis_booking WHERE usahawan_id = ? AND status NOT IN ('completed', 'cancelled')");
    $stmt2->bind_param("i", $uid);
    $stmt2->execute();
    $stmt2->bind_result($booking_count);
    $stmt2->fetch();
    $stmt2->close();
}

$total_notif = $pesanan_count + $booking_count;
// ─────────────────────────────────────────────────────────────────
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

/* label "USAHAWAN" dalam toggle bar */
.usahawan-toggle-bar .toggle-label {
  color: var(--gold);
}

/* badge pada toggle bar */
.toggle-notif-badge {
  position: absolute;
  top: -6px;
  right: -115px;
  background: #e53935;
  color: #fff;
  font-size: 0.6rem;
  font-weight: 700;
  min-width: 17px;
  height: 17px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #fff;
  line-height: 1;
  pointer-events: none;
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

/* ================= NOTIFICATION BADGE (TAMBAHAN SAHAJA) ================= */
.notif-badge {
  margin-left: auto;
  background: #e53935;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  border-radius: 50px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
  line-height: 1;
  flex-shrink: 0;
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
    <li><a href="chat_room.php" class="<?= $current_page=='chat_room.php'?'active':'' ?>">💬 Chat</a></li>
    <li><a href="profile_usahawan2.php" class="<?= $current_page=='profile_usahawan2.php'?'active':'' ?>">👤 Profil</a></li>
    <li><a href="produk_usahawan.php" class="<?= $current_page=='produk_usahawan.php'?'active':'' ?>">📦 Produk</a></li>
    <li>
      <a href="pesanan_masuk.php" class="<?= $current_page=='pesanan_masuk.php'?'active':'' ?>">
        🚚 Pesanan Produk
        <?php if ($pesanan_count > 0): ?>
          <span class="notif-badge"><?= $pesanan_count > 99 ? '99+' : $pesanan_count ?></span>
        <?php endif; ?>
      </a>
    </li>
    <li><a href="servis_usahawan.php" class="<?= $current_page=='servis_usahawan.php'?'active':'' ?>">🛠️ Servis</a></li>
    <li>
      <a href="seller_booking.php" class="<?= $current_page=='seller_booking.php'?'active':'' ?>">
        📖 Tempahan Servis
        <?php if ($booking_count > 0): ?>
          <span class="notif-badge"><?= $booking_count > 99 ? '99+' : $booking_count ?></span>
        <?php endif; ?>
      </a>
    </li>
    <li><a href="laporan_perniagaan.php" class="<?= $current_page=='laporan_perniagaan.php'?'active':'' ?>">📊 Laporan</a></li>
  </ul>

</aside>

<!-- ===== TOGGLE BAR ===== -->
<div class="usahawan-toggle-bar" onclick="toggleUsahawanSidebar()">
  <span style="position:relative; display:inline-flex; align-items:center;">
    <i id="usahawanToggleIcon">☰</i>
    <?php if ($total_notif > 0): ?>
      <span class="toggle-notif-badge" id="usahawanToggleBadge"><?= $total_notif > 99 ? '99+' : $total_notif ?></span>
    <?php endif; ?>
  </span>
  <span class="toggle-label" id="usahawanToggleLabel">USAHAWAN</span>
</div>

<script>
function toggleUsahawanSidebar() {
  const sidebar  = document.getElementById("usahawanSidebar");
  const icon     = document.getElementById("usahawanToggleIcon");
  const label    = document.getElementById("usahawanToggleLabel");
  const toggleEl = document.querySelector(".usahawan-toggle-bar");

  const isOpen = sidebar.classList.toggle("open");

  const badge    = document.getElementById("usahawanToggleBadge");

  icon.textContent        = isOpen ? "◂" : "☰";
  label.style.display     = isOpen ? "none" : "";
  toggleEl.style.padding  = isOpen ? "0 10px" : "";
  if (badge) badge.style.display = isOpen ? "none" : "";
}
</script>