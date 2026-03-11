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

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

/* ── Fetch booking (must belong to user & be completed) ── */
$sql = "SELECT sb.*, s.nama AS nama_servis, s.gambar_servis_url,
               u.nama AS nama_usahawan, u.id AS usahawan_real_id
        FROM servis_booking sb
        JOIN servis s ON sb.service_id = s.id
        JOIN usahawan u ON sb.usahawan_id = u.id
        WHERE sb.id = ? AND sb.nama_pelanggan = ? AND sb.telefon = ? AND sb.status = 'completed'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $booking_id, $user['nama'], $user['telefon']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo "<script>alert('Tempahan tidak dijumpai atau belum selesai.'); window.location.href='customer_booking.php';</script>";
    exit;
}

/* ── Check if already reviewed ── */
$chk = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$chk->bind_param("i", $booking_id);
$chk->execute();
$already_reviewed = $chk->get_result()->num_rows > 0;

/* ── Handle POST ── */
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_reviewed) {
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $komen   = trim($_POST['komen'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Sila pilih rating antara 1 hingga 5 bintang.';
    } elseif (strlen($komen) < 10) {
        $error = 'Ulasan mestilah sekurang-kurangnya 10 aksara.';
    } else {
        $ins = $conn->prepare("INSERT INTO reviews (booking_id, usahawan_id, pelanggan_nama, rating, komen, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $ins->bind_param("iisis", $booking_id, $booking['usahawan_real_id'], $user['nama'], $rating, $komen);
        if ($ins->execute()) {
            $success = true;
            $already_reviewed = true;
        } else {
            $error = 'Ralat semasa menyimpan ulasan. Sila cuba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tulis Ulasan Servis</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #111827;
  --ink-soft: #6B7280;
  --ink-muted: #9CA3AF;
  --bg: #F0F2F5;
  --surface: #fff;
  --border: #E5E7EB;
  --blue: #2563EB;
  --green: #059669;
  --green-lt: #ECFDF5;
  --red: #DC2626;
  --red-lt: #FEF2F2;
  --purple: #7C3AED;
  --purple-lt: #F5F3FF;
  --amber: #F59E0B;
  --radius: 12px;
  --radius-lg: 20px;
  --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
  --shadow-lg: 0 8px 40px rgba(0,0,0,.10);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg);
  color: var(--ink);
  padding-top: 90px;
  min-height: 100vh;
}

.page { max-width: 640px; margin: 0 auto; padding: 36px 20px 80px; }

/* Back link */
.back-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600; color: var(--ink-soft);
  text-decoration: none; margin-bottom: 24px;
  transition: color .2s;
}
.back-link:hover { color: var(--purple); }

/* Service card summary */
.service-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  padding: 22px 24px;
  display: flex; align-items: center; gap: 16px;
  margin-bottom: 28px;
  border-left: 4px solid var(--purple);
}
.service-thumb {
  width: 58px; height: 58px; border-radius: 10px;
  object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;
}
.service-thumb-ph {
  width: 58px; height: 58px; border-radius: 10px;
  background: var(--purple-lt); display: flex; align-items: center;
  justify-content: center; font-size: 26px; flex-shrink: 0;
}
.service-name { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 16px; color: var(--ink); }
.service-meta { font-size: 13px; color: var(--ink-muted); margin-top: 3px; }
.completed-badge {
  margin-left: auto; flex-shrink: 0;
  background: var(--green-lt); color: var(--green);
  border: 1px solid #A7F3D0; border-radius: 999px;
  padding: 5px 13px; font-size: 12px; font-weight: 700;
}

/* Review form card */
.review-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
  animation: fadeUp .4s ease both;
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

.review-header {
  background: linear-gradient(135deg, var(--purple) 0%, #6D28D9 100%);
  padding: 28px 32px;
  text-align: center;
  color: #fff;
}
.review-header .stars-big { font-size: 36px; margin-bottom: 10px; }
.review-header h2 { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 800; }
.review-header p { font-size: 13px; opacity: .8; margin-top: 4px; }

.review-body { padding: 32px; }

/* Star rating input */
.label { font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 10px; display: block; }
.label span { color: var(--red); }

.star-group {
  display: flex; gap: 10px; margin-bottom: 8px;
}
.star-group input[type="radio"] { display: none; }
.star-group label {
  font-size: 36px; cursor: pointer;
  filter: grayscale(1) opacity(.4);
  transition: transform .15s, filter .15s;
  user-select: none;
}
.star-group label:hover,
.star-group label:hover ~ label { /* handled by JS */ }
.star-group input[type="radio"]:checked ~ label,
.star-group label.active {
  filter: none;
}
.star-hint { font-size: 12px; color: var(--ink-muted); margin-bottom: 24px; min-height: 18px; }

/* Textarea */
.textarea-wrap { position: relative; margin-bottom: 24px; }
textarea {
  width: 100%; min-height: 130px;
  padding: 14px 16px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 14px; color: var(--ink);
  resize: vertical; line-height: 1.6;
  transition: border-color .2s, box-shadow .2s;
  background: #FAFAFA;
}
textarea:focus {
  outline: none;
  border-color: var(--purple);
  box-shadow: 0 0 0 3px rgba(124,58,237,.12);
  background: #fff;
}
.char-count {
  position: absolute; bottom: 10px; right: 14px;
  font-size: 11px; color: var(--ink-muted);
}

/* Submit button */
.btn-submit {
  width: 100%; padding: 14px;
  background: linear-gradient(135deg, var(--purple), #6D28D9);
  color: #fff; border: none; border-radius: var(--radius);
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 15px; font-weight: 700;
  cursor: pointer; transition: all .2s;
  box-shadow: 0 4px 14px rgba(124,58,237,.35);
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(124,58,237,.4); }
.btn-submit:active { transform: translateY(0); }

/* Alerts */
.alert {
  padding: 14px 18px; border-radius: var(--radius);
  font-size: 14px; font-weight: 500; margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px;
}
.alert.error { background: var(--red-lt); color: var(--red); border: 1px solid #FCA5A5; }
.alert.success { background: var(--green-lt); color: var(--green); border: 1px solid #A7F3D0; }

/* Already reviewed / success state */
.done-state {
  text-align: center; padding: 40px 24px;
}
.done-icon { font-size: 56px; margin-bottom: 16px; }
.done-state h3 { font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 800; margin-bottom: 8px; }
.done-state p { font-size: 14px; color: var(--ink-muted); line-height: 1.6; }
.btn-back {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  margin-top: 24px; padding: 12px 28px;
  background: var(--purple-lt); color: var(--purple);
  border: 1.5px solid #DDD6FE; border-radius: var(--radius);
  font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700;
  text-decoration: none; transition: all .2s;
}
.btn-back:hover { background: var(--purple); color: #fff; }

/* Aspect chips for quick-select */
.aspect-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.aspect-chip {
  padding: 7px 13px; border-radius: 999px;
  border: 1.5px solid var(--border);
  font-size: 12px; font-weight: 600; color: var(--ink-soft);
  cursor: pointer; transition: all .15s; user-select: none;
  background: #fff;
}
.aspect-chip:hover { border-color: var(--purple); color: var(--purple); }
.aspect-chip.selected { background: var(--purple-lt); border-color: var(--purple); color: var(--purple); }
</style>
</head>
<body>
<div class="page">

  <a href="customer_booking.php" class="back-link">← Kembali ke Tempahan Saya</a>

  <!-- Service Summary -->
  <div class="service-card">
    <?php if (!empty($booking['gambar_servis_url'])): ?>
      <img class="service-thumb" src="uploads/<?= htmlspecialchars($booking['gambar_servis_url']) ?>">
    <?php else: ?>
      <div class="service-thumb-ph">🔧</div>
    <?php endif; ?>
    <div>
      <div class="service-name"><?= htmlspecialchars($booking['nama_servis']) ?></div>
      <div class="service-meta">Usahawan: <strong><?= htmlspecialchars($booking['nama_usahawan']) ?></strong></div>
      <div class="service-meta">ID: #<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?> · <?= date('d M Y', strtotime($booking['tarikh'])) ?></div>
    </div>
    <div class="completed-badge">✓ Selesai</div>
  </div>

  <!-- Review Card -->
  <div class="review-card">

    <?php if ($success || $already_reviewed): ?>
    <!-- Already submitted -->
    <div class="done-state">
      <div class="done-icon">🌟</div>
      <h3><?= $success ? 'Terima Kasih atas Ulasan Anda!' : 'Ulasan Telah Dihantar' ?></h3>
      <p>
        <?= $success
          ? 'Maklum balas anda sangat berharga dan membantu usahawan meningkatkan kualiti perkhidmatan mereka.'
          : 'Anda telah memberikan ulasan untuk tempahan ini sebelum ini.' ?>
      </p>
      <a href="customer_booking.php" class="btn-back">← Kembali ke Tempahan Saya</a>
    </div>

    <?php else: ?>
    <!-- Form -->
    <div class="review-header">
      <div class="stars-big">⭐</div>
      <h2>Kongsi Pengalaman Anda</h2>
      <p>Bantu pelanggan lain membuat pilihan yang bijak</p>
    </div>

    <div class="review-body">

      <?php if ($error): ?>
      <div class="alert error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="reviewForm" novalidate>

        <!-- Star Rating -->
        <label class="label">Rating Keseluruhan <span>*</span></label>
        <div class="star-group" id="starGroup">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
          <label for="star<?= $i ?>" data-val="<?= $i ?>">⭐</label>
          <?php endfor; ?>
        </div>
        <div class="star-hint" id="starHint">Klik bintang untuk beri rating</div>

        <!-- Aspect chips -->
        <label class="label">Apa yang anda suka? <span style="color:var(--ink-muted);font-weight:400">(pilihan)</span></label>
        <div class="aspect-chips" id="aspectChips">
          <div class="aspect-chip" data-text="Kerja berkualiti tinggi">✅ Kerja berkualiti</div>
          <div class="aspect-chip" data-text="Tepat masa">⏰ Tepat masa</div>
          <div class="aspect-chip" data-text="Harga berpatutan">💰 Harga berpatutan</div>
          <div class="aspect-chip" data-text="Komunikasi baik">💬 Komunikasi baik</div>
          <div class="aspect-chip" data-text="Bersih dan kemas">🧹 Bersih & kemas</div>
          <div class="aspect-chip" data-text="Profesional">🎖️ Profesional</div>
        </div>

        <!-- Comment -->
        <label class="label" for="komen">Ulasan Anda <span>*</span></label>
        <div class="textarea-wrap">
          <textarea name="komen" id="komen" maxlength="600"
            placeholder="Kongsikan pengalaman anda secara terperinci. Contoh: Kerja dibuat dengan cepat dan bersih, harga sangat berpatutan..."></textarea>
          <div class="char-count"><span id="charUsed">0</span>/600</div>
        </div>

        <button type="submit" class="btn-submit">
          ⭐ Hantar Ulasan Saya
        </button>

      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
/* ── Star rating ── */
const hints = ['', 'Sangat Kurang Memuaskan', 'Kurang Memuaskan', 'Memuaskan', 'Bagus!', 'Sangat Cemerlang! 🎉'];
const labels = document.querySelectorAll('#starGroup label');
const radios  = document.querySelectorAll('#starGroup input[type="radio"]');
const hint    = document.getElementById('starHint');

// Stars are rendered 5→1 in DOM; we need to highlight correctly
labels.forEach(lbl => {
  lbl.addEventListener('click', () => {
    const val = parseInt(lbl.dataset.val);
    updateStars(val);
    hint.textContent = hints[val];
    hint.style.color = val >= 4 ? 'var(--green)' : val === 3 ? 'var(--amber)' : 'var(--red)';
  });
});

function updateStars(val) {
  labels.forEach(l => {
    l.style.filter = parseInt(l.dataset.val) <= val
      ? 'none'
      : 'grayscale(1) opacity(.35)';
    l.style.transform = parseInt(l.dataset.val) <= val ? 'scale(1.12)' : 'scale(1)';
  });
}

/* ── Aspect chips ── */
const textarea = document.getElementById('komen');
document.querySelectorAll('.aspect-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    chip.classList.toggle('selected');
    rebuildComment();
  });
});

function rebuildComment() {
  const selected = [...document.querySelectorAll('.aspect-chip.selected')]
    .map(c => c.dataset.text);
  if (selected.length === 0) return;
  const existing = textarea.value.trim();
  // Append chips as prefix only if textarea is empty or just has chip text
  const chipText = selected.join('. ') + '. ';
  if (!existing || textarea.dataset.chipSet === 'true') {
    textarea.value = chipText;
    textarea.dataset.chipSet = 'true';
  }
  updateChar();
}

/* ── Char counter ── */
const charUsed = document.getElementById('charUsed');
textarea.addEventListener('input', () => {
  textarea.dataset.chipSet = 'false';
  updateChar();
});
function updateChar() {
  const len = textarea.value.length;
  charUsed.textContent = len;
  charUsed.style.color = len > 550 ? 'var(--red)' : 'var(--ink-muted)';
}

/* ── Form validation ── */
document.getElementById('reviewForm')?.addEventListener('submit', e => {
  const rated = [...radios].some(r => r.checked);
  const text  = textarea.value.trim();
  if (!rated) {
    e.preventDefault();
    hint.textContent = '⚠️ Sila pilih rating bintang dahulu!';
    hint.style.color = 'var(--red)';
    document.getElementById('starGroup').scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  if (text.length < 10) {
    e.preventDefault();
    textarea.style.borderColor = 'var(--red)';
    textarea.focus();
    return;
  }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>