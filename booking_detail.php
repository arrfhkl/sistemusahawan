<?php
include 'connection.php';
include 'header.php';

/* ================= SESSION CHECK ================= */
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila login dahulu'); window.location='login.php';</script>";
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

if (!isset($_GET['id'])) die("ID tempahan tidak sah.");
$booking_id = (int) $_GET['id'];

/* ================= GET BOOKING DATA ================= */
$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis, s.gambar_servis_url
    FROM servis_booking sb
    JOIN servis s ON sb.service_id = s.id
    WHERE sb.id = ? AND sb.usahawan_id = ?
");
$stmt->bind_param("ii", $booking_id, $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) die("Tempahan tidak dijumpai.");
$data = $result->fetch_assoc();

/* ================= UPDATE STATUS ================= */
if (isset($_POST['update_status'])) {
    $status_baru = $_POST['status'];
    $update = $conn->prepare("UPDATE servis_booking SET status=? WHERE id=? AND usahawan_id=?");
    $update->bind_param("sii", $status_baru, $booking_id, $usahawan_id);
    $update->execute();

    /* If usahawan confirms cash payment, mark as paid */
    if ($status_baru === 'paid') {
        $mark = $conn->prepare("UPDATE servis_booking SET payment_method='cash', payment_status='paid' WHERE id=?");
        $mark->bind_param("i", $booking_id);
        $mark->execute();
    }

    echo "<script>window.location='booking_detail.php?id=$booking_id';</script>";
    exit;
}

/* ================= STATUS CONFIG ================= */
$status = $data['status'];

/* Steps shown in progress bar — terminal states not in bar */
$steps = [
    'pending'     => ['label' => 'Tempahan\nDiterima',   'icon' => '📋'],
    'inspection'  => ['label' => 'Pemeriksaan\nOn-Site', 'icon' => '🔍'],
    'quoted'      => ['label' => 'Sebut Harga\nDihantar', 'icon' => '📄'],
    'approved'    => ['label' => 'Dipersetujui',         'icon' => '✅'],
    'paid'        => ['label' => 'Bayaran\nDisahkan',    'icon' => '💳'],
    'in_progress' => ['label' => 'Kerja\nSedang Jalan',  'icon' => '🔧'],
    'completed'   => ['label' => 'Selesai',              'icon' => '🎉'],
];

$step_keys    = array_keys($steps);
$current_step = array_search($status, $step_keys);
$is_terminal  = in_array($status, ['rejected', 'cancelled']);

$status_badge = [
    'pending'     => ['bg'=>'#FEF3C7','color'=>'#92400E','text'=>'Menunggu Tindakan'],
    'inspection'  => ['bg'=>'#DBEAFE','color'=>'#1E40AF','text'=>'Pemeriksaan On-Site'],
    'quoted'      => ['bg'=>'#EDE9FE','color'=>'#5B21B6','text'=>'Sebut Harga Dihantar'],
    'approved'    => ['bg'=>'#D1FAE5','color'=>'#065F46','text'=>'Dipersetujui Pelanggan'],
    'paid'        => ['bg'=>'#D1FAE5','color'=>'#065F46','text'=>'Bayaran Disahkan'],
    'in_progress' => ['bg'=>'#DBEAFE','color'=>'#1E40AF','text'=>'Kerja Sedang Berjalan'],
    'completed'   => ['bg'=>'#D1FAE5','color'=>'#065F46','text'=>'Selesai ✓'],
    'rejected'    => ['bg'=>'#FEE2E2','color'=>'#991B1B','text'=>'Ditolak'],
    'cancelled'   => ['bg'=>'#F3F4F6','color'=>'#374151','text'=>'Dibatalkan'],
];

$badge = $status_badge[$status] ?? ['bg'=>'#eee','color'=>'#333','text'=>ucfirst($status)];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Butiran Tempahan #<?= $booking_id ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">

<style>
:root {
  --bg:        #F0F2F5;
  --surface:   #FFFFFF;
  --border:    #E5E7EB;
  --ink:       #111827;
  --ink-soft:  #6B7280;
  --ink-muted: #9CA3AF;
  --blue:      #2563EB;
  --blue-dark: #1D4ED8;
  --blue-lt:   #EFF6FF;
  --green:     #059669;
  --green-lt:  #ECFDF5;
  --amber:     #D97706;
  --amber-lt:  #FFFBEB;
  --red:       #DC2626;
  --red-lt:    #FEF2F2;
  --teal:      #0D9488;
  --teal-lt:   #F0FDFA;
  --radius:    12px;
  --radius-lg: 20px;
  --shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
  --shadow-lg: 0 8px 40px rgba(0,0,0,.10);
  --ease:      cubic-bezier(.4,0,.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg);
  color: var(--ink);
  padding-top: 80px;
  min-height: 100vh;
}

/* ── LAYOUT ── */
.page {
  max-width: 1140px;
  margin: 0 auto;
  padding: 36px 20px 80px;
}

/* ── TOP BAR ── */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--ink-soft);
  text-decoration: none;
  transition: color .2s;
}
.back-link:hover { color: var(--blue); }
.back-link svg { width:16px; height:16px; }

.booking-id {
  font-size: 13px;
  color: var(--ink-muted);
  font-weight: 500;
}

/* ── HEADER CARD ── */
.header-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  box-shadow: var(--shadow);
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.servis-thumb {
  width: 80px;
  height: 80px;
  border-radius: var(--radius);
  object-fit: cover;
  border: 1px solid var(--border);
  flex-shrink: 0;
  background: var(--bg);
}

.servis-thumb-placeholder {
  width: 80px;
  height: 80px;
  border-radius: var(--radius);
  background: var(--blue-lt);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  flex-shrink: 0;
}

.header-text { flex: 1; }

.servis-name {
  font-family: 'Sora', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 4px;
}

.pelanggan-name {
  font-size: 14px;
  color: var(--ink-soft);
  margin-bottom: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}

/* ── PROGRESS TRACKER ── */
.progress-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 32px 32px 28px;
  box-shadow: var(--shadow);
  margin-bottom: 28px;
  overflow-x: auto;
}

.progress-track {
  display: flex;
  align-items: flex-start;
  min-width: 620px;
  position: relative;
}

.progress-track::before {
  content: '';
  position: absolute;
  top: 19px;
  left: calc(100% / 14);
  right: calc(100% / 14);
  height: 2px;
  background: var(--border);
  z-index: 0;
}

.p-step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

.p-step-line {
  position: absolute;
  top: 19px;
  left: 50%;
  right: -50%;
  height: 2px;
  background: var(--border);
  z-index: 0;
}

.p-step:last-child .p-step-line { display: none; }

.p-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--bg);
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  position: relative;
  z-index: 2;
  transition: all .3s var(--ease);
}

.p-step.done .p-circle {
  background: var(--green);
  border-color: var(--green);
  font-size: 14px;
  color: white;
}

.p-step.done .p-step-line { background: var(--green); }

.p-step.current .p-circle {
  background: var(--blue);
  border-color: var(--blue);
  box-shadow: 0 0 0 5px rgba(37,99,235,.15);
}

.p-label {
  font-size: 11px;
  font-weight: 500;
  color: var(--ink-muted);
  text-align: center;
  margin-top: 10px;
  line-height: 1.4;
  white-space: pre-line;
}

.p-step.done .p-label,
.p-step.current .p-label {
  color: var(--ink-soft);
  font-weight: 600;
}

.p-step.current .p-label { color: var(--blue); }

/* Terminal state banner */
.terminal-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 28px;
}

.terminal-banner.rejected { background: var(--red-lt); color: var(--red); }
.terminal-banner.cancelled { background: #F3F4F6; color: #374151; }

/* ── CONTENT GRID ── */
.grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}

/* ── CARDS ── */
.card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 28px;
  box-shadow: var(--shadow);
}

.card + .card { margin-top: 20px; }

.card-title {
  font-family: 'Sora', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 24px;
}

.info-item {}

.info-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .6px;
  text-transform: uppercase;
  color: var(--ink-muted);
  margin-bottom: 4px;
}

.info-value {
  font-size: 14px;
  font-weight: 500;
  color: var(--ink);
  line-height: 1.5;
}

.divider {
  height: 1px;
  background: var(--border);
  margin: 22px 0;
}

.preview-img {
  width: 100%;
  max-width: 280px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  margin-top: 8px;
}

/* ── ACTION PANEL ── */
.action-panel { position: sticky; top: 100px; }

.action-title {
  font-family: 'Sora', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 6px;
}

.action-hint {
  font-size: 13px;
  color: var(--ink-soft);
  margin-bottom: 20px;
  line-height: 1.5;
  padding: 12px;
  background: var(--amber-lt);
  border-radius: var(--radius);
  border-left: 3px solid var(--amber);
}

/* ── BUTTONS ── */
.btn {
  width: 100%;
  padding: 13px 16px;
  border: none;
  border-radius: var(--radius);
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all .2s var(--ease);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  margin-bottom: 10px;
}

.btn:last-child { margin-bottom: 0; }

.btn:hover { transform: translateY(-1px); }
.btn:active { transform: translateY(0); }

.btn-blue   { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.25); }
.btn-blue:hover { background: var(--blue-dark); }

.btn-green  { background: var(--green); color: #fff; box-shadow: 0 4px 14px rgba(5,150,105,.2); }
.btn-green:hover { background: #047857; }

.btn-red    { background: var(--red); color: #fff; box-shadow: 0 4px 14px rgba(220,38,38,.2); }
.btn-red:hover { background: #b91c1c; }

.btn-teal   { background: var(--teal); color: #fff; box-shadow: 0 4px 14px rgba(13,148,136,.2); }
.btn-teal:hover { background: #0f766e; }

.btn-amber  { background: var(--amber); color: #fff; box-shadow: 0 4px 14px rgba(217,119,6,.2); }
.btn-amber:hover { background: #b45309; }

.btn-ghost  { background: transparent; color: var(--ink-soft); border: 1.5px solid var(--border); }
.btn-ghost:hover { border-color: var(--ink-soft); color: var(--ink); background: var(--bg); }

.btn-disabled {
  background: var(--bg);
  color: var(--ink-muted);
  cursor: not-allowed;
  border: 1.5px dashed var(--border);
  box-shadow: none;
}
.btn-disabled:hover { transform: none; }

/* ── PAYMENT METHODS ── */
.pay-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}

.pay-card {
  border: 2px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 12px;
  text-align: center;
  cursor: pointer;
  transition: all .2s;
  background: var(--bg);
}

.pay-card:hover,
.pay-card.selected {
  border-color: var(--blue);
  background: var(--blue-lt);
}

.pay-card .icon { font-size: 22px; margin-bottom: 4px; }
.pay-card .label { font-size: 12px; font-weight: 600; color: var(--ink-soft); }
.pay-card.selected .label { color: var(--blue); }

/* ── MODAL ── */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}

.modal-overlay.open { display: flex; }

.modal {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 36px 32px;
  max-width: 420px;
  width: 90%;
  animation: modalIn .25s var(--ease) both;
  box-shadow: var(--shadow-lg);
  text-align: center;
}

@keyframes modalIn {
  from { opacity:0; transform: scale(.95) translateY(10px); }
  to   { opacity:1; transform: scale(1) translateY(0); }
}

.modal-icon { font-size: 48px; margin-bottom: 16px; }

.modal h3 {
  font-family: 'Sora', sans-serif;
  font-size: 19px;
  font-weight: 700;
  margin-bottom: 10px;
}

.modal p {
  font-size: 14px;
  color: var(--ink-soft);
  line-height: 1.6;
  margin-bottom: 28px;
}

.modal-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* ── WHAT HAPPENS NEXT ── */
.next-steps {
  margin-top: 14px;
  padding: 16px;
  background: var(--bg);
  border-radius: var(--radius);
}

.next-steps-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  color: var(--ink-muted);
  margin-bottom: 12px;
}

.next-step-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 13px;
  color: var(--ink-soft);
  line-height: 1.4;
}

.next-step-item:last-child { margin-bottom: 0; }

.step-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--surface);
  border: 1.5px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
  color: var(--ink-muted);
}

/* ── PAGE ANIM ── */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

.header-card  { animation: fadeUp .4s ease both; }
.progress-card{ animation: fadeUp .4s .08s ease both; }
.grid         { animation: fadeUp .4s .14s ease both; }

/* ── RESPONSIVE ── */
@media (max-width: 860px) {
  .grid { grid-template-columns: 1fr; }
  .action-panel { position: static; }
  .info-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="page">

  <!-- TOP BAR -->
  <div class="top-bar">
    <a href="seller_booking.php" class="back-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Kembali ke Senarai Tempahan
    </a>
    <span class="booking-id">Tempahan #<?= str_pad($booking_id, 5, '0', STR_PAD_LEFT) ?></span>
  </div>

  <!-- HEADER CARD -->
  <div class="header-card">
    <?php if (!empty($data['gambar_servis_url'])): ?>
      <img class="servis-thumb" src="uploads/<?= htmlspecialchars($data['gambar_servis_url']) ?>">
    <?php else: ?>
      <div class="servis-thumb-placeholder">🔧</div>
    <?php endif; ?>

    <div class="header-text">
      <div class="servis-name"><?= htmlspecialchars($data['nama_servis']) ?></div>
      <div class="pelanggan-name">Pelanggan: <strong><?= htmlspecialchars($data['nama_pelanggan']) ?></strong></div>
      <div class="status-badge" style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
        <?= $badge['text'] ?>
      </div>
    </div>

    <div style="text-align:right;font-size:13px;color:var(--ink-muted);">
      <div><?= date("d M Y", strtotime($data['created_at'] ?? 'now')) ?></div>
      <div style="margin-top:4px;"><?= $data['tarikh'] ?> · <?= $data['masa'] ?></div>
    </div>
  </div>

  <!-- PROGRESS TRACKER -->
  <?php if (!$is_terminal): ?>
  <div class="progress-card">
    <div class="progress-track">
      <?php foreach ($steps as $key => $step):
        $idx = array_search($key, $step_keys);
        $is_done    = $idx < $current_step;
        $is_current = $idx === $current_step;
        $class = $is_done ? 'done' : ($is_current ? 'current' : '');
      ?>
      <div class="p-step <?= $class ?>">
        <div class="p-step-line"></div>
        <div class="p-circle">
          <?php if ($is_done): ?>✓
          <?php else: ?><?= $step['icon'] ?><?php endif; ?>
        </div>
        <div class="p-label"><?= $step['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="terminal-banner <?= $status ?>">
    <?= $status === 'rejected' ? '❌' : '🚫' ?>
    Tempahan ini telah <?= $status === 'rejected' ? 'ditolak' : 'dibatalkan' ?>.
    Tiada tindakan lanjut diperlukan.
  </div>
  <?php endif; ?>

  <!-- MAIN GRID -->
  <div class="grid">

    <!-- LEFT: DETAILS -->
    <div>
      <!-- Customer Info -->
      <div class="card">
        <div class="card-title">👤 Maklumat Pelanggan</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Nama</div>
            <div class="info-value"><?= htmlspecialchars($data['nama_pelanggan']) ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Telefon</div>
            <div class="info-value"><?= htmlspecialchars($data['telefon']) ?></div>
          </div>
          <div class="info-item" style="grid-column:span 2;">
            <div class="info-label">Alamat</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($data['alamat'] ?? '-')) ?></div>
          </div>
        </div>
      </div>

      <!-- Service Info -->
      <div class="card">
        <div class="card-title">🔧 Maklumat Servis & Masalah</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Tarikh Temujanji</div>
            <div class="info-value"><?= $data['tarikh'] ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Masa</div>
            <div class="info-value"><?= $data['masa'] ?></div>
          </div>
          <div class="info-item" style="grid-column:span 2;">
            <div class="info-label">Penerangan Masalah</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($data['masalah'] ?? '-')) ?></div>
          </div>
        </div>

        <?php if (!empty($data['imej'])): ?>
        <div class="divider"></div>
        <div class="info-label" style="margin-bottom:8px;">Imej Lampiran</div>
        <img src="uploads/<?= htmlspecialchars($data['imej']) ?>" class="preview-img">
        <?php endif; ?>
      </div>

      <!-- Quotation Info (if quoted/beyond) -->
      <?php if (in_array($status, ['quoted','approved','paid','in_progress','completed']) && !empty($data['harga_sebut'])): ?>
      <div class="card">
        <div class="card-title">📄 Maklumat Sebut Harga</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Jumlah Sebut Harga</div>
            <div class="info-value" style="font-size:22px;font-weight:700;color:var(--blue);">
              RM <?= number_format($data['harga_sebut'], 2) ?>
            </div>
          </div>
          <div class="info-item">
            <div class="info-label">Kaedah Bayaran</div>
            <div class="info-value"><?= !empty($data['payment_method']) ? ucfirst($data['payment_method']) : '—' ?></div>
          </div>
          <?php if (!empty($data['nota_sebut'])): ?>
          <div class="info-item" style="grid-column:span 2;">
            <div class="info-label">Nota Usahawan</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($data['nota_sebut'])) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: ACTION PANEL -->
    <div class="action-panel">
      <div class="card">

        <?php if ($status === 'pending'): ?>
          <div class="action-title">Tindakan Diperlukan</div>
          <div class="action-hint">
            ⚠️ Pelanggan menunggu respon anda. Terima tempahan ini untuk menetapkan tarikh pemeriksaan on-site.
          </div>

          <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <button type="button" class="btn btn-green" onclick="openModal('approve_inspection')">
              ✅ Terima &amp; Jadualkan Pemeriksaan
            </button>
            <button type="button" class="btn btn-red" onclick="openModal('reject')">
              ❌ Tolak Tempahan
            </button>
          </form>

          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal" style="margin-top:4px;">
            💬 Chat Pelanggan
          </a>

          <div class="next-steps">
            <div class="next-steps-title">Apa yang berlaku seterusnya?</div>
            <div class="next-step-item">
              <div class="step-dot">1</div>
              Terima tempahan → tetapkan jadual pemeriksaan
            </div>
            <div class="next-step-item">
              <div class="step-dot">2</div>
              Pergi ke lokasi pelanggan, periksa masalah sebenar
            </div>
            <div class="next-step-item">
              <div class="step-dot">3</div>
              Jana &amp; hantar sebut harga yang tepat
            </div>
          </div>

        <?php elseif ($status === 'inspection'): ?>
          <div class="action-title">Pemeriksaan On-Site</div>
          <div class="action-hint">
            🔍 Pergi ke lokasi pelanggan dan periksa masalah sebenar sebelum membuat sebut harga.
          </div>

          <a href="mock_quotation.php?booking_id=<?= $data['id'] ?>" class="btn btn-blue">
            📄 Jana Sebut Harga
          </a>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>
          <form method="POST" style="margin-top:4px;">
            <input type="hidden" name="update_status" value="1">
            <button name="status" value="cancelled" type="button" class="btn btn-ghost" onclick="openModal('cancel')">
              🚫 Batalkan Tempahan
            </button>
          </form>

          <div class="next-steps">
            <div class="next-steps-title">Langkah seterusnya</div>
            <div class="next-step-item">
              <div class="step-dot">→</div>
              Selepas pemeriksaan, klik "Jana Sebut Harga" untuk masukkan jumlah &amp; hantar kepada pelanggan
            </div>
          </div>

        <?php elseif ($status === 'quoted'): ?>
          <div class="action-title">Menunggu Keputusan Pelanggan</div>
          <div class="action-hint">
            ⏳ Sebut harga telah dihantar. Pelanggan sedang mempertimbangkan. Anda boleh chat untuk follow up.
          </div>

          <a href="mock_quotation.php?booking_id=<?= $data['id'] ?>" class="btn btn-ghost">
            📝 Semak / Kemaskini Sebut Harga
          </a>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>
          <form method="POST" style="margin-top:4px;">
            <input type="hidden" name="update_status" value="1">
            <button name="status" value="cancelled" type="button" class="btn btn-ghost" onclick="openModal('cancel')">
              🚫 Batalkan Tempahan
            </button>
          </form>

        <?php elseif ($status === 'approved'): ?>
          <div class="action-title">Pelanggan Bersetuju!</div>
          <div class="action-hint">
            ✅ Pelanggan telah menerima sebut harga. Tunggu bayaran atau sahkan bayaran tunai.
          </div>

          <!-- Payment options -->
          <div style="margin-bottom:14px;">
            <div class="info-label" style="margin-bottom:8px;">Sahkan Kaedah Bayaran:</div>
            <div class="pay-options">
              <div class="pay-card" onclick="selectPay(this,'online')">
                <div class="icon">💳</div>
                <div class="label">Online</div>
              </div>
              <div class="pay-card" onclick="selectPay(this,'cash')">
                <div class="icon">💵</div>
                <div class="label">Tunai</div>
              </div>
            </div>
            <input type="hidden" id="payMethodInput" value="">
          </div>

          <button class="btn btn-green" onclick="openModal('confirm_payment')">
            💳 Sahkan Bayaran Diterima
          </button>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>

        <?php elseif ($status === 'paid'): ?>
          <div class="action-title">Bayaran Sah — Mula Kerja</div>
          <div class="action-hint">
            💳 Bayaran telah disahkan. Anda boleh mula menjalankan servis sekarang.
          </div>

          <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <button name="status" value="in_progress" class="btn btn-blue">
              🔧 Mula Jalankan Servis
            </button>
          </form>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>

        <?php elseif ($status === 'in_progress'): ?>
          <div class="action-title">Servis Sedang Berjalan</div>
          <div class="action-hint">
            🔧 Kerja sedang dijalankan. Tandakan selesai apabila semua kerja telah siap dan pelanggan berpuas hati.
          </div>

          <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <button name="status" value="completed" class="btn btn-green" type="button" onclick="openModal('complete')">
              🎉 Tandakan Selesai
            </button>
          </form>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>

        <?php elseif ($status === 'completed'): ?>
          <div class="action-title">Servis Selesai ✓</div>
          <div style="text-align:center;padding:20px 0;">
            <div style="font-size:52px;margin-bottom:10px;">🎉</div>
            <div style="font-size:14px;color:var(--ink-soft);line-height:1.6;">
              Tempahan ini telah selesai dengan jayanya.<br>
              Pelanggan akan menerima notifikasi untuk memberi penilaian.
            </div>
          </div>
          <a href="chat_room.php?user_id=<?= $data['pelanggan_id'] ?>" class="btn btn-teal">
            💬 Chat Pelanggan
          </a>

        <?php else: /* rejected / cancelled */ ?>
          <div class="action-title">Tempahan Tidak Aktif</div>
          <div style="font-size:14px;color:var(--ink-muted);line-height:1.6;margin-bottom:16px;">
            Tempahan ini telah <?= $status === 'rejected' ? 'ditolak' : 'dibatalkan' ?> dan tidak boleh diubah lagi.
          </div>

        <?php endif; ?>

        <!-- Always: back button -->
        <a href="seller_booking.php" class="btn btn-ghost" style="margin-top:6px;">
          ← Kembali ke Senarai
        </a>

      </div>
    </div>
  </div>
</div>

<!-- ============ MODALS ============ -->

<!-- Approve Inspection Modal -->
<div id="modal_approve_inspection" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">🔍</div>
    <h3>Terima &amp; Jadualkan Pemeriksaan</h3>
    <p>
      Anda akan menerima tempahan daripada <strong><?= htmlspecialchars($data['nama_pelanggan']) ?></strong> untuk servis <strong><?= htmlspecialchars($data['nama_servis']) ?></strong>.<br><br>
      Pastikan anda sudah bersedia untuk pergi ke lokasi pelanggan bagi tujuan pemeriksaan.
    </p>
    <div class="modal-actions">
      <form method="POST" style="display:contents;">
        <input type="hidden" name="update_status" value="1">
        <button name="status" value="inspection" class="btn btn-green" style="width:100%;margin:0;">
          ✅ Ya, Terima
        </button>
      </form>
      <button class="btn btn-ghost" style="width:100%;margin:0;" onclick="closeModal('modal_approve_inspection')">
        Batal
      </button>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="modal_reject" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">❌</div>
    <h3>Tolak Tempahan?</h3>
    <p>
      Adakah anda pasti untuk menolak tempahan daripada <strong><?= htmlspecialchars($data['nama_pelanggan']) ?></strong>?
      Tindakan ini tidak boleh dibatalkan.
    </p>
    <div class="modal-actions">
      <form method="POST" style="display:contents;">
        <input type="hidden" name="update_status" value="1">
        <button name="status" value="rejected" class="btn btn-red" style="width:100%;margin:0;">
          ❌ Ya, Tolak
        </button>
      </form>
      <button class="btn btn-ghost" style="width:100%;margin:0;" onclick="closeModal('modal_reject')">
        Batal
      </button>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div id="modal_cancel" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">🚫</div>
    <h3>Batalkan Tempahan?</h3>
    <p>Adakah anda pasti untuk membatalkan tempahan ini? Pelanggan akan dimaklumkan.</p>
    <div class="modal-actions">
      <form method="POST" style="display:contents;">
        <input type="hidden" name="update_status" value="1">
        <button name="status" value="cancelled" class="btn btn-red" style="width:100%;margin:0;">
          🚫 Ya, Batalkan
        </button>
      </form>
      <button class="btn btn-ghost" style="width:100%;margin:0;" onclick="closeModal('modal_cancel')">
        Kembali
      </button>
    </div>
  </div>
</div>

<!-- Confirm Payment Modal -->
<div id="modal_confirm_payment" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">💳</div>
    <h3>Sahkan Bayaran Diterima</h3>
    <p>
      Sahkan bahawa anda telah menerima bayaran daripada <strong><?= htmlspecialchars($data['nama_pelanggan']) ?></strong>.
      Selepas ini, anda boleh terus mula menjalankan servis.
    </p>
    <div class="modal-actions">
      <form method="POST" style="display:contents;" id="paymentForm">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="status" value="paid">
        <input type="hidden" name="payment_method" id="payMethodHidden" value="cash">
        <button type="submit" class="btn btn-green" style="width:100%;margin:0;">
          ✅ Sahkan Bayaran
        </button>
      </form>
      <button class="btn btn-ghost" style="width:100%;margin:0;" onclick="closeModal('modal_confirm_payment')">
        Batal
      </button>
    </div>
  </div>
</div>

<!-- Complete Modal -->
<div id="modal_complete" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">🎉</div>
    <h3>Tandakan Servis Selesai?</h3>
    <p>
      Pastikan semua kerja telah siap dan pelanggan berpuas hati sebelum menandakan selesai.
    </p>
    <div class="modal-actions">
      <form method="POST" style="display:contents;">
        <input type="hidden" name="update_status" value="1">
        <button name="status" value="completed" class="btn btn-green" style="width:100%;margin:0;">
          🎉 Ya, Selesai!
        </button>
      </form>
      <button class="btn btn-ghost" style="width:100%;margin:0;" onclick="closeModal('modal_complete')">
        Belum Lagi
      </button>
    </div>
  </div>
</div>

<script>
function openModal(name) {
  document.getElementById('modal_' + name).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

/* Close on backdrop click */
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});

function selectPay(el, method) {
  document.querySelectorAll('.pay-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('payMethodInput').value = method;
  document.getElementById('payMethodHidden').value = method;
}

/* Pre-select cash by default */
document.addEventListener('DOMContentLoaded', function() {
  const cashCard = document.querySelector('.pay-card:last-child');
  if (cashCard) selectPay(cashCard, 'cash');
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>