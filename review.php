<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['usahawan_id'];

$userStmt = $conn->prepare("SELECT nama, telefon FROM usahawan WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) die("Maklumat pengguna tidak ditemui.");

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

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

$chk = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$chk->bind_param("i", $booking_id);
$chk->execute();
$already_reviewed = $chk->get_result()->num_rows > 0;

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_reviewed) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $komen  = trim($_POST['komen'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Sila pilih rating.';
    } elseif (strlen($komen) < 10) {
        $error = 'Ulasan terlalu pendek (minimum 10 aksara).';
    } else {
        $uploaded = [];
        if (!empty($_FILES['gambar']['name'][0])) {
            $targetDir = "uploads/reviews/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp) {
                if ($_FILES['gambar']['error'][$key] !== 0) continue;
                $ext = strtolower(pathinfo($_FILES['gambar']['name'][$key], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
                $fname = uniqid("rev_") . "." . $ext;
                if (move_uploaded_file($tmp, $targetDir . $fname)) {
                    $uploaded[] = $fname;
                    if (count($uploaded) >= 5) break;
                }
            }
        }
        $gambar_json = !empty($uploaded) ? json_encode($uploaded) : null;

        $ins = $conn->prepare("INSERT INTO reviews (booking_id, usahawan_id, pelanggan_nama, rating, komen, gambar, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $ins->bind_param("iisiss", $booking_id, $booking['usahawan_real_id'], $user['nama'], $rating, $komen, $gambar_json);

        if ($ins->execute()) { $success = true; $already_reviewed = true; }
        else $error = 'Ralat. Sila cuba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penilaian Perkhidmatan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --navy:        #001f3f;
    --blue:        #003399;
    --accent:      #0055cc;
    --accent-soft: #dbeafe;
    --gold:        #b8860b;
    --gold-light:  #f5d98a;
    --success:     #16a34a;
    --success-bg:  #dcfce7;
    --danger:      #dc2626;
    --danger-bg:   #fee2e2;
    --surface:     #ffffff;
    --surface2:    #f8fafd;
    --border:      #e2e8f0;
    --text:        #0f172a;
    --text-mid:    #334155;
    --text-muted:  #64748b;
    --radius-lg:   16px;
    --radius-md:   10px;
    --shadow:      0 4px 24px rgba(0,31,63,0.08);
    --shadow-lg:   0 12px 48px rgba(0,31,63,0.14);
    --transition:  0.25s cubic-bezier(0.4,0,0.2,1);
  }

  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: #eef2f7;
    color: var(--text);
    margin-top: 90px;
    min-height: 100vh;
    position: relative;
  }
  body::after {
    content: "";
    position: fixed; inset: 0;
    background-image: url("assets/img/jatapahang.png");
    background-repeat: repeat;
    background-size: 160px;
    opacity: 0.04;
    z-index: -1;
    pointer-events: none;
  }

  .tp-wrapper {
    max-width: 600px;
    margin: 36px auto 80px;
    padding: 0 18px;
  }

  /* ── CARD ── */
  .form-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
    animation: fadeUp 0.4s ease both;
  }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .form-card-header {
    background: var(--surface2);
    border-bottom: 1px solid var(--border);
    padding: 18px 28px;
    display: flex; align-items: center; gap: 10px;
  }
  .section-num {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--navy), var(--blue));
    color: #fff; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
  }
  .form-card-header h3 { font-size: 0.95rem; font-weight: 700; color: var(--navy); }
  .form-card-header p  { font-size: 0.78rem; color: var(--text-muted); }

  .form-card-body { padding: 28px; }

  /* ── SERVICE ROW ── */
  .service-row {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 24px;
  }
  .svc-thumb {
    width: 46px; height: 46px;
    border-radius: 8px; border: 1px solid var(--border);
    background: #e8f0fb;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; overflow: hidden; flex-shrink: 0;
  }
  .svc-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .svc-name { font-weight: 700; font-size: 0.9rem; color: var(--navy); }
  .svc-meta { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
  .svc-badge {
    margin-left: auto;
    font-size: 0.7rem; font-weight: 700;
    color: var(--success); background: var(--success-bg);
    border: 1px solid #86efac; border-radius: 20px;
    padding: 3px 10px; white-space: nowrap;
  }

  /* ── FORM GROUPS ── */
  .form-group { margin-bottom: 22px; }
  .form-group:last-child { margin-bottom: 0; }
  .form-group > label {
    display: flex; align-items: center; gap: 7px;
    font-size: 0.8rem; font-weight: 800;
    color: var(--navy); text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 10px;
  }
  .form-group > label i { color: var(--accent); font-size: 0.78rem; }
  .opt {
    font-weight: 400; color: var(--text-muted);
    text-transform: none; letter-spacing: 0;
    font-size: 0.75rem; margin-left: 2px;
  }

  /* ── STARS ── */
  .stars { display: flex; gap: 6px; margin-bottom: 6px; }
  .stars input { display: none; }
  .stars label {
    font-size: 32px; cursor: pointer;
    color: #d1dbe8; transition: color .15s, transform .15s;
    line-height: 1; font-weight: 400;
    text-transform: none; letter-spacing: 0;
  }
  .stars label.on { color: #f59e0b; }
  .stars label:hover { transform: scale(1.1); }
  .star-hint { font-size: 0.78rem; color: var(--text-muted); min-height: 18px; margin-bottom: 4px; }

  .divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

  /* ── TEXTAREA ── */
  .form-group textarea {
    width: 100%; min-height: 120px;
    padding: 12px 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.92rem; color: var(--text);
    line-height: 1.6; resize: vertical;
    background: var(--surface2);
    transition: all var(--transition);
  }
  .form-group textarea::placeholder { color: #b0bec5; }
  .form-group textarea:focus {
    outline: none; border-color: var(--accent);
    background: #fff; box-shadow: 0 0 0 4px rgba(0,85,204,0.1);
  }
  .char-counter { text-align: right; font-size: 0.72rem; color: var(--text-muted); margin-top: 5px; }

  /* ── UPLOAD ZONE ── */
  .upload-zone {
    border: 2px dashed #c8d6e8;
    border-radius: var(--radius-md);
    padding: 20px; text-align: center;
    cursor: pointer;
    background: linear-gradient(135deg, #f0f6ff, #f8faff);
    transition: all var(--transition);
    position: relative;
  }
  .upload-zone:hover, .upload-zone.drag-over {
    border-color: var(--accent); background: #eef4ff;
  }
  .upload-zone input[type="file"] {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer;
    width: 100%; height: 100%;
  }
  .uz-icon { font-size: 1.8rem; color: #93b4d8; margin-bottom: 6px; transition: color var(--transition); }
  .upload-zone:hover .uz-icon { color: var(--accent); }
  .uz-title { font-size: 0.85rem; font-weight: 700; color: var(--navy); margin-bottom: 2px; }
  .uz-sub   { font-size: 0.75rem; color: var(--text-muted); }
  .uz-tags  { display: flex; justify-content: center; gap: 5px; margin-top: 8px; flex-wrap: wrap; }
  .uz-tags span {
    background: #e0ecff; color: var(--accent);
    border-radius: 20px; padding: 2px 8px;
    font-size: 0.68rem; font-weight: 700;
  }

  /* ── PHOTO PREVIEW (replaces upload zone) ── */
  .photo-preview-wrap { display: none; }
  .photo-preview-wrap.visible { display: block; }

  .preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
  }

  /* Individual photo tile */
  .photo-tile {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--success);
  }
  .photo-tile img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
  }
  .photo-tile .ph-remove {
    position: absolute; top: 4px; right: 4px;
    width: 22px; height: 22px;
    background: rgba(220,38,38,0.85); color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.6rem; cursor: pointer;
    transition: background var(--transition); z-index: 2;
  }
  .photo-tile .ph-remove:hover { background: var(--danger); }

  /* "Add more" tile — only shown when < 5 photos */
  .photo-add-tile {
    aspect-ratio: 1;
    border-radius: 8px;
    border: 2px dashed #c8d6e8;
    background: var(--surface2);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 4px; cursor: pointer;
    transition: all var(--transition);
    position: relative;
    font-size: 0.68rem; font-weight: 700;
    color: var(--text-muted);
  }
  .photo-add-tile:hover { border-color: var(--accent); color: var(--accent); background: #eef4ff; }
  .photo-add-tile i { font-size: 1.1rem; }
  .photo-add-tile input {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer;
    width: 100%; height: 100%;
  }

  /* ── ALERT ── */
  .alert-error {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    background: var(--danger-bg); border: 1px solid #fca5a5;
    border-radius: var(--radius-md);
    font-size: 0.85rem; color: var(--danger); font-weight: 500;
    margin-bottom: 20px;
  }

  /* ── ACTIONS ── */
  .actions { display: flex; justify-content: flex-end; align-items: center; gap: 14px; margin-top: 4px; }
  .btn-cancel-link {
    font-size: 0.85rem; color: var(--text-muted);
    text-decoration: none; font-weight: 600;
    transition: color var(--transition);
  }
  .btn-cancel-link:hover { color: var(--navy); }
  .btn-submit {
    padding: 13px 28px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: #fff; border: none;
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem; font-weight: 800;
    cursor: pointer; display: flex; align-items: center; gap: 8px;
    transition: all var(--transition);
    box-shadow: 0 4px 14px rgba(34,197,94,0.35);
    letter-spacing: 0.3px;
  }
  .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(34,197,94,0.4); }
  .btn-submit:active { transform: translateY(0); }

  /* ── SUCCESS ── */
  .success-body { padding: 48px 28px; text-align: center; }
  .success-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: var(--success-bg); border: 3px solid #86efac;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; color: var(--success); margin: 0 auto 16px;
  }
  .success-body h3 { font-family: 'DM Serif Display', serif; font-size: 1.25rem; color: var(--navy); margin-bottom: 6px; }
  .success-body p  { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; }
  .btn-return {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 11px 22px; border: 2px solid var(--border);
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.88rem; font-weight: 700;
    color: var(--text-mid); text-decoration: none;
    transition: all var(--transition);
  }
  .btn-return:hover { border-color: var(--navy); color: var(--navy); }
</style>
</head>
<body>

<div class="tp-wrapper">
  <div class="form-card">

    <div class="form-card-header">
      <div class="section-num"><i class="fas fa-pen" style="font-size:0.7rem"></i></div>
      <div>
        <h3>Rating & Review</h3>
        <p>Tempahan #<?= str_pad($booking_id, 5, '0', STR_PAD_LEFT) ?></p>
      </div>
    </div>

    <div class="form-card-body">

      <div class="service-row">
        <div class="svc-thumb">
          <?php if (!empty($booking['gambar_servis_url'])): ?>
            <img src="uploads/<?= htmlspecialchars($booking['gambar_servis_url']) ?>">
          <?php else: ?>🔧<?php endif; ?>
        </div>
        <div>
          <div class="svc-name"><?= htmlspecialchars($booking['nama_servis']) ?></div>
          <div class="svc-meta"><?= htmlspecialchars($booking['nama_usahawan']) ?> · <?= date('d M Y', strtotime($booking['tarikh'])) ?></div>
        </div>
        <div class="svc-badge"><i class="fas fa-check"></i> Selesai</div>
      </div>

      <?php if ($success || $already_reviewed): ?>
      <div class="success-body">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h3><?= $success ? 'Penilaian Dihantar' : 'Sudah Dinilai' ?></h3>
        <p><?= $success ? 'Terima kasih atas maklum balas anda.' : 'Anda telah menilai tempahan ini sebelum ini.' ?></p>
        <a href="customer_booking.php" class="btn-return">
          <i class="fas fa-arrow-left"></i> Kembali ke Tempahan
        </a>
      </div>

      <?php else: ?>

      <?php if ($error): ?>
      <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="rf" novalidate>

        <!-- Rating -->
        <div class="form-group">
          <label> Rating</label>
          <div class="stars" id="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <input type="radio" name="rating" id="s<?= $i ?>" value="<?= $i ?>">
            <label for="s<?= $i ?>" data-v="<?= $i ?>">★</label>
            <?php endfor; ?>
          </div>
          <div class="star-hint" id="stxt"></div>
        </div>

        <hr class="divider">

        <!-- Review -->
        <div class="form-group">
          <label> Review</label>
          <textarea name="komen" id="komen" maxlength="600"
            placeholder="Kongsikan pengalaman anda…"></textarea>
          <div class="char-counter"><span id="cn">0</span> / 600</div>
        </div>

        <hr class="divider">

        <!-- Photo Upload -->
        <div class="form-group">
          <label> Gambar <span class="opt">(Optional)</span>
          </label>

          <!-- Upload zone — hidden once photos are added -->
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="gambar[]" id="photoInput"
                   accept="image/jpeg,image/png,image/webp"
                   multiple onchange="handlePhotos(event)">
            <div class="uz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="uz-title">Klik atau seret gambar ke sini</div>
            <div class="uz-sub">Kongsi gambar hasil kerja</div>
            <div class="uz-tags"><span>JPG</span><span>PNG</span><span>WEBP</span></div>
          </div>

          <!-- Preview grid — shown once photos are added -->
          <div class="photo-preview-wrap" id="previewWrap">
            <div class="preview-grid" id="previewGrid"></div>
          </div>
        </div>

        <div class="actions">
          <a href="customer_booking.php" class="btn-cancel-link">Batal</a>
          <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Hantar
          </button>
        </div>

      </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
/* ── Stars ── */
const hints = ['','Sangat Lemah','Kurang Memuaskan','Memuaskan','Bagus','Cemerlang'];
const lbls  = document.querySelectorAll('#stars label');
const stxt  = document.getElementById('stxt');
let picked  = 0;

lbls.forEach(l => {
  l.addEventListener('mouseenter', () => paint(+l.dataset.v));
  l.addEventListener('mouseleave', () => paint(picked));
  l.addEventListener('click', () => {
    picked = +l.dataset.v; paint(picked);
    stxt.textContent = hints[picked];
    stxt.style.color = picked >= 4 ? '#16a34a' : picked === 3 ? '#d97706' : '#dc2626';
  });
});
function paint(v) { lbls.forEach(l => l.classList.toggle('on', +l.dataset.v <= v)); }

/* ── Char counter ── */
const ta = document.getElementById('komen'), cn = document.getElementById('cn');
ta?.addEventListener('input', () => cn.textContent = ta.value.length);

/* ── Photo upload ── */
const MAX = 5;
let files = [];

function handlePhotos(e) {
  Array.from(e.target.files).forEach(f => {
    if (files.length < MAX && !files.find(x => x.name === f.name && x.size === f.size))
      files.push(f);
  });
  render();
  syncInput();
  toggle();
}

function removePhoto(i) {
  files.splice(i, 1);
  render();
  syncInput();
  toggle();
}

function syncInput() {
  const dt = new DataTransfer();
  files.forEach(f => dt.items.add(f));
  document.getElementById('photoInput').files = dt.files;
}

/* Show preview / hide upload zone based on file count */
function toggle() {
  const hasFiles = files.length > 0;
  document.getElementById('uploadZone').style.display  = hasFiles ? 'none' : '';
  document.getElementById('previewWrap').classList.toggle('visible', hasFiles);
}

function render() {
  const grid = document.getElementById('previewGrid');
  grid.innerHTML = '';

  files.forEach((f, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const tile = document.createElement('div');
      tile.className = 'photo-tile';
      tile.innerHTML = `
        <img src="${e.target.result}" alt="">
        <div class="ph-remove" onclick="removePhoto(${i})">
          <i class="fas fa-times"></i>
        </div>`;
      // Insert before the "add more" tile
      const addTile = grid.querySelector('.photo-add-tile');
      grid.insertBefore(tile, addTile || null);
    };
    reader.readAsDataURL(f);
  });

  // Show "add more" tile only if under max
  if (files.length < MAX) {
    const add = document.createElement('div');
    add.className = 'photo-add-tile';
    add.innerHTML = `
      <i class="fas fa-plus"></i>
      <span>Tambah</span>
      <input type="file" accept="image/jpeg,image/png,image/webp"
             multiple onchange="handlePhotos(event)">`;
    grid.appendChild(add);
  }
}

/* ── Drag effects on upload zone ── */
const zone = document.getElementById('uploadZone');
zone?.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone?.addEventListener('drop',      () => zone.classList.remove('drag-over'));

/* ── Validation ── */
document.getElementById('rf')?.addEventListener('submit', e => {
  if (!picked) {
    e.preventDefault();
    stxt.textContent = 'Sila pilih rating terlebih dahulu.';
    stxt.style.color = '#dc2626';
    return;
  }
  if (ta.value.trim().length < 10) {
    e.preventDefault();
    ta.style.borderColor = '#dc2626';
    ta.focus();
  }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>