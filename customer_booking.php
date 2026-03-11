<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['usahawan_id'];

/* ── User info ── */
$userStmt = $conn->prepare("SELECT nama, telefon FROM usahawan WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) die("Maklumat pengguna tidak ditemui.");

/* ── Handle Accept / Reject quotation ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {

    $bid    = (int)$_POST['booking_id'];
    $action = $_POST['action'];

    $check = $conn->prepare("SELECT id FROM servis_booking WHERE id=? AND nama_pelanggan=? AND telefon=?");
    $check->bind_param("iss", $bid, $user['nama'], $user['telefon']);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        if ($action === 'accept') {
            $upd = $conn->prepare("UPDATE servis_booking SET status='approved' WHERE id=?");
            $upd->bind_param("i", $bid);
            $upd->execute();
            echo "<script>window.location.href='servis_stripe_checkout.php?booking_id=$bid';</script>";
            exit;

        } elseif ($action === 'reject') {
            $upd = $conn->prepare("UPDATE servis_booking SET status='rejected' WHERE id=?");
            $upd->bind_param("i", $bid);
            $upd->execute();
            echo "<script>window.location.href='customer_booking.php?rejected=1';</script>";
            exit;
        }
    }
}

/* ── Fetch all bookings for this customer ── */
$sql = "SELECT sb.*, s.nama AS nama_servis, s.gambar_servis_url,
               u.nama AS nama_usahawan, u.telefon AS telefon_usahawan
        FROM servis_booking sb
        JOIN servis s ON sb.service_id = s.id
        JOIN usahawan u ON sb.usahawan_id = u.id
        WHERE sb.nama_pelanggan = ? AND sb.telefon = ?
        ORDER BY sb.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user['nama'], $user['telefon']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Pre-fetch all reviewed booking IDs for this customer ── */
$reviewed_ids = [];
if (!empty($bookings)) {
$rchk = $conn->prepare("
    SELECT booking_id FROM reviews 
    WHERE pelanggan_nama = ?
");
    $rchk->bind_param("s", $user['nama']);
    $rchk->execute();
    $rrows = $rchk->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rrows as $rr) {
        $reviewed_ids[] = (int)$rr['booking_id'];
    }
}

/* ── Status config ── */
$status_cfg = [
    'pending'     => ['label'=>'Menunggu Respon',      'bg'=>'#FEF3C7','color'=>'#92400E'],
    'inspection'  => ['label'=>'Pemeriksaan On-Site',  'bg'=>'#DBEAFE','color'=>'#1E40AF'],
    'quoted'      => ['label'=>'Sebut Harga Diterima',  'bg'=>'#EDE9FE','color'=>'#5B21B6'],
    'approved'    => ['label'=>'Dipersetujui',          'bg'=>'#D1FAE5','color'=>'#065F46'],
    'paid'        => ['label'=>'Bayaran Disahkan',      'bg'=>'#D1FAE5','color'=>'#065F46'],
    'in_progress' => ['label'=>'Kerja Sedang Berjalan', 'bg'=>'#DBEAFE','color'=>'#1E40AF'],
    'completed'   => ['label'=>'Selesai ✓',            'bg'=>'#D1FAE5','color'=>'#065F46'],
    'rejected'    => ['label'=>'Ditolak',               'bg'=>'#FEE2E2','color'=>'#991B1B'],
    'cancelled'   => ['label'=>'Dibatalkan',            'bg'=>'#F3F4F6','color'=>'#374151'],
];

function cfg($status, $key, $all) {
    return $all[$status][$key] ?? ($key==='label' ? ucfirst($status) : '#eee');
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tempahan Servis Saya</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#111827;--ink-soft:#6B7280;--ink-muted:#9CA3AF;
  --bg:#F0F2F5;--surface:#fff;--border:#E5E7EB;
  --blue:#2563EB;--blue-lt:#EFF6FF;
  --green:#059669;--green-lt:#ECFDF5;
  --red:#DC2626;--red-lt:#FEF2F2;
  --amber:#D97706;--amber-lt:#FFFBEB;
  --teal:#0D9488;
  --radius:12px;--radius-lg:20px;
  --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
  --shadow-lg:0 8px 40px rgba(0,0,0,.10);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--ink);padding-top:90px;min-height:100vh}

.page{max-width:860px;margin:0 auto;padding:36px 20px 80px}

.page-header{margin-bottom:32px}
.page-title{font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:var(--ink)}
.page-sub{font-size:14px;color:var(--ink-muted);margin-top:4px}

.flash{padding:14px 18px;border-radius:var(--radius);font-size:14px;font-weight:500;margin-bottom:24px;display:flex;align-items:center;gap:10px}
.flash.success{background:var(--green-lt);color:var(--green);border:1px solid #A7F3D0}
.flash.info{background:var(--amber-lt);color:var(--amber);border:1px solid #FDE68A}

.booking-card{background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden;transition:box-shadow .2s}
.booking-card:hover{box-shadow:var(--shadow-lg)}

.card-top{padding:22px 26px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;border-bottom:1px solid var(--border)}

.servis-info{display:flex;gap:14px;align-items:center}
.servis-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid var(--border);background:var(--bg);flex-shrink:0}
.servis-thumb-ph{width:52px;height:52px;border-radius:10px;background:var(--blue-lt);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.servis-name{font-family:'Sora',sans-serif;font-weight:700;font-size:16px;color:var(--ink);margin-bottom:3px}
.servis-meta{font-size:13px;color:var(--ink-muted)}

.status-badge{display:inline-flex;align-items:center;padding:5px 13px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;flex-shrink:0}

.card-body{padding:20px 26px}
.info-chips{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.chip{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:7px 12px;font-size:12px;color:var(--ink-soft)}
.chip strong{color:var(--ink);display:block;font-size:13px}

.quotation-box{background:#FAFBFF;border:1.5px solid #C7D7FD;border-radius:var(--radius);padding:20px 22px;margin-bottom:18px}
.q-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.q-title{font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:var(--blue);display:flex;align-items:center;gap:7px}
.q-total-pill{background:var(--blue);color:#fff;border-radius:999px;padding:5px 16px;font-size:14px;font-weight:700}

.q-table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:14px}
.q-table th{padding:8px 10px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--ink-muted);border-bottom:2px solid var(--border)}
.q-table td{padding:9px 10px;border-bottom:1px solid #EEF2FF;color:var(--ink-soft)}
.q-table td:first-child{color:var(--ink);font-weight:500}
.q-table tr:last-child td{border-bottom:none}

.q-nota{background:#EFF6FF;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--blue);line-height:1.6;margin-bottom:14px}
.q-nota strong{display:block;margin-bottom:3px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--ink-muted)}

.q-actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 20px;border:none;border-radius:var(--radius);font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}
.btn-accept{background:var(--green);color:#fff;box-shadow:0 4px 12px rgba(5,150,105,.2)}
.btn-accept:hover{background:#047857;transform:translateY(-1px)}
.btn-reject{background:transparent;color:var(--red);border:1.5px solid #FCA5A5}
.btn-reject:hover{background:var(--red-lt)}
.btn-chat{background:var(--teal);color:#fff;box-shadow:0 4px 12px rgba(13,148,136,.2)}
.btn-chat:hover{background:#0f766e}
.btn-review{background:#7C3AED;color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.25)}
.btn-review:hover{background:#6D28D9;transform:translateY(-1px)}

/* ── Reviewed (greyed) state ── */
.btn-reviewed{
  background:#E5E7EB;color:#9CA3AF;
  border:1.5px solid #D1D5DB;
  cursor:default;pointer-events:none;
}
.btn-reviewed:hover{transform:none;box-shadow:none}

.btn-sm{padding:8px 14px;font-size:13px}

.empty{text-align:center;padding:70px 20px;background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow)}
.empty-icon{font-size:52px;margin-bottom:16px}
.empty h3{font-family:'Sora',sans-serif;font-size:20px;margin-bottom:8px}
.empty p{color:var(--ink-muted);font-size:14px}

.mini-track{display:flex;align-items:center;gap:0;margin-bottom:16px;overflow-x:auto;padding-bottom:4px}
.m-step{display:flex;align-items:center}
.m-dot{width:8px;height:8px;border-radius:50%;background:var(--border);flex-shrink:0}
.m-dot.done{background:var(--green)}
.m-dot.current{background:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.2)}
.m-line{width:28px;height:2px;background:var(--border);flex-shrink:0}
.m-line.done{background:var(--green)}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.booking-card{animation:fadeUp .35s ease both}
.booking-card:nth-child(2){animation-delay:.05s}
.booking-card:nth-child(3){animation-delay:.1s}
.booking-card:nth-child(4){animation-delay:.15s}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:var(--radius-lg);padding:36px 32px;max-width:420px;width:90%;animation:modalIn .25s ease both;box-shadow:var(--shadow-lg);text-align:center}
@keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-icon{font-size:46px;margin-bottom:14px}
.modal h3{font-family:'Sora',sans-serif;font-size:19px;font-weight:700;margin-bottom:8px}
.modal p{font-size:14px;color:var(--ink-soft);line-height:1.6;margin-bottom:26px}
.modal-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}

@media(max-width:600px){.card-top{flex-direction:column}.q-actions{flex-direction:column}}
</style>
</head>
<body>
<div class="page">

  <div class="page-header">
    <h1 class="page-title">🔧 Tempahan Servis Saya</h1>
    <p class="page-sub">Pantau status servis dan urus sebut harga anda di sini.</p>
  </div>

  <?php if (isset($_GET['rejected'])): ?>
  <div class="flash info">❌ Anda telah menolak sebut harga. Usahawan akan dimaklumkan.</div>
  <?php endif; ?>

  <?php if (empty($bookings)): ?>
  <div class="empty">
    <div class="empty-icon">🛠️</div>
    <h3>Tiada Tempahan Servis</h3>
    <p>Anda belum membuat sebarang tempahan servis.</p>
  </div>

  <?php else: ?>
  <?php foreach ($bookings as $b):
    $status  = $b['status'];
    $bg      = cfg($status, 'bg', $status_cfg);
    $clr     = cfg($status, 'color', $status_cfg);
    $lbl     = cfg($status, 'label', $status_cfg);

    /* Check if this booking already has a review */
    $has_review = in_array((int)$b['id'], $reviewed_ids);

    /* Load quotation items if status is quoted/beyond */
    $q_items = [];
    if (in_array($status, ['quoted','approved','paid','in_progress','completed'])) {
        $qi = $conn->prepare("SELECT * FROM quotation_items WHERE booking_id=? ORDER BY id ASC");
        $qi->bind_param("i", $b['id']);
        $qi->execute();
        $q_items = $qi->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $all_steps   = ['pending','inspection','quoted','approved','paid','in_progress','completed'];
    $current_idx = array_search($status, $all_steps);
  ?>

  <div class="booking-card">
    <div class="card-top">
      <div class="servis-info">
        <?php if (!empty($b['gambar_servis_url'])): ?>
          <img class="servis-thumb" src="uploads/<?= htmlspecialchars($b['gambar_servis_url']) ?>">
        <?php else: ?>
          <div class="servis-thumb-ph">🔧</div>
        <?php endif; ?>
        <div>
          <div class="servis-name"><?= htmlspecialchars($b['nama_servis']) ?></div>
          <div class="servis-meta">
            Usahawan: <strong><?= htmlspecialchars($b['nama_usahawan']) ?></strong> ·
            <?= date('d M Y', strtotime($b['tarikh'])) ?> · <?= $b['masa'] ?>
          </div>
          <div class="servis-meta" style="margin-top:2px">ID: #<?= str_pad($b['id'],5,'0',STR_PAD_LEFT) ?></div>
        </div>
      </div>
      <div class="status-badge" style="background:<?= $bg ?>;color:<?= $clr ?>">
        <?= $lbl ?>
      </div>
    </div>

    <div class="card-body">

      <?php if (!in_array($status, ['rejected','cancelled'])): ?>
      <div class="mini-track" style="margin-bottom:18px">
        <?php foreach ($all_steps as $si => $sk):
          $is_done    = $current_idx !== false && $si < $current_idx;
          $is_current = $si === $current_idx;
        ?>
        <div class="m-step">
          <div class="m-dot <?= $is_done?'done':($is_current?'current':'') ?>"></div>
          <?php if ($si < count($all_steps)-1): ?>
          <div class="m-line <?= $is_done?'done':'' ?>"></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="info-chips">
        <div class="chip"><strong><?= htmlspecialchars($b['alamat'] ?? '-') ?></strong>Alamat</div>
        <div class="chip"><strong><?= htmlspecialchars($b['telefon_usahawan']) ?></strong>Telefon Usahawan</div>
        <?php if (!empty($b['harga_sebut'])): ?>
        <div class="chip"><strong style="color:var(--blue)">RM <?= number_format($b['harga_sebut'],2) ?></strong>Jumlah Sebut Harga</div>
        <?php endif; ?>
      </div>

      <?php if ($status === 'quoted' && !empty($q_items)): ?>
      <div class="quotation-box">
        <div class="q-header">
          <div class="q-title">📄 Sebut Harga daripada Usahawan</div>
          <div class="q-total-pill">RM <?= number_format($b['harga_sebut'],2) ?></div>
        </div>
        <table class="q-table">
          <thead>
            <tr>
              <th>#</th><th>Item</th><th>Penerangan</th>
              <th style="text-align:center">Qty</th>
              <th style="text-align:right">Harga/Unit</th>
              <th style="text-align:right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($q_items as $qi => $item): ?>
            <tr>
              <td><?= $qi+1 ?></td>
              <td><?= htmlspecialchars($item['item_name']) ?></td>
              <td><?= htmlspecialchars($item['item_desc'] ?? '') ?></td>
              <td style="text-align:center"><?= (int)$item['qty'] ?></td>
              <td style="text-align:right">RM <?= number_format($item['unit_price'],2) ?></td>
              <td style="text-align:right;font-weight:600;color:var(--ink)">RM <?= number_format($item['total_price'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!empty($b['nota_sebut'])): ?>
        <div class="q-nota">
          <strong>Nota daripada Usahawan</strong>
          <?= nl2br(htmlspecialchars($b['nota_sebut'])) ?>
        </div>
        <?php endif; ?>
        <div class="q-actions">
          <button class="btn btn-accept" onclick="openAccept(<?= $b['id'] ?>)">
            ✅ Terima &amp; Teruskan ke Pembayaran
          </button>
          <button class="btn btn-reject btn-sm" onclick="openReject(<?= $b['id'] ?>)">
            ✕ Tolak
          </button>
        </div>
      </div>

      <?php elseif (in_array($status, ['approved','paid','in_progress','completed']) && !empty($q_items)): ?>
      <div class="quotation-box" style="border-color:var(--border);background:#FAFAFA">
        <div class="q-header">
          <div class="q-title" style="color:var(--green)">✅ Sebut Harga Dipersetujui</div>
          <div class="q-total-pill" style="background:var(--green)">RM <?= number_format($b['harga_sebut'],2) ?></div>
        </div>
        <table class="q-table">
          <thead>
            <tr><th>#</th><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Jumlah</th></tr>
          </thead>
          <tbody>
            <?php foreach ($q_items as $qi => $item): ?>
            <tr>
              <td><?= $qi+1 ?></td>
              <td><?= htmlspecialchars($item['item_name']) ?></td>
              <td style="text-align:center"><?= (int)$item['qty'] ?></td>
              <td style="text-align:right;font-weight:600">RM <?= number_format($item['total_price'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- Action buttons row -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">

        <a href="chat_room.php?user_id=<?= $b['usahawan_id'] ?>" class="btn btn-chat btn-sm">
          💬 Chat Usahawan
        </a>

        <?php if ($status === 'completed'): ?>
          <?php if ($has_review): ?>
            <!-- Already reviewed — greyed out, not clickable -->
            <span class="btn btn-reviewed btn-sm">
              ✓ Ulasan Dihantar
            </span>
          <?php else: ?>
            <!-- Not yet reviewed — active purple button -->
            <a href="review.php?booking_id=<?= $b['id'] ?>" class="btn btn-review btn-sm">
               Tulis Ulasan
            </a>
          <?php endif; ?>
        <?php endif; ?>

      </div>

    </div>
  </div>

  <?php endforeach; ?>
  <?php endif; ?>

</div>

<!-- Accept Modal -->
<div id="modal_accept" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">✅</div>
    <h3>Terima Sebut Harga?</h3>
    <p>Anda bersetuju dengan caj yang ditetapkan. Anda akan diarahkan ke halaman pembayaran seterusnya.</p>
    <form method="POST" id="acceptForm">
      <input type="hidden" name="action" value="accept">
      <input type="hidden" name="booking_id" id="acceptBookingId">
      <div class="modal-actions">
        <button type="submit" class="btn btn-accept" style="width:100%;margin:0">✅ Ya, Teruskan</button>
        <button type="button" class="btn btn-reject" style="width:100%;margin:0;border-color:var(--border);color:var(--ink-soft)" onclick="closeModal('modal_accept')">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Modal -->
<div id="modal_reject" class="modal-overlay">
  <div class="modal">
    <div class="modal-icon">❌</div>
    <h3>Tolak Sebut Harga?</h3>
    <p>Adakah anda pasti untuk menolak sebut harga ini? Usahawan akan dimaklumkan dan tempahan akan ditutup.</p>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="action" value="reject">
      <input type="hidden" name="booking_id" id="rejectBookingId">
      <div class="modal-actions">
        <button type="submit" class="btn btn-reject" style="width:100%;margin:0;background:var(--red);color:#fff;border:none">❌ Ya, Tolak</button>
        <button type="button" class="btn" style="width:100%;margin:0;background:var(--bg);border:1.5px solid var(--border)" onclick="closeModal('modal_reject')">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAccept(id) {
  document.getElementById('acceptBookingId').value = id;
  document.getElementById('modal_accept').classList.add('open');
}
function openReject(id) {
  document.getElementById('rejectBookingId').value = id;
  document.getElementById('modal_reject').classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target===el) el.classList.remove('open'); });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>