<?php
include "connection.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'header.php';

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk.'); window.location='login.php';</script>";
    exit;
}

$usahawan_id = (int)$_SESSION['usahawan_id'];

if (!isset($_GET['booking_id'])) die("ID tempahan tidak sah.");
$booking_id = (int)$_GET['booking_id'];

/* ── Verify booking belongs to this usahawan ── */
$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis
    FROM servis_booking sb
    JOIN servis s ON sb.service_id = s.id
    WHERE sb.id = ? AND sb.usahawan_id = ?
");
$stmt->bind_param("ii", $booking_id, $usahawan_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) die("Tempahan tidak dijumpai.");

/* ── Load existing items if already quoted ── */
$existing_items = [];
$qi = $conn->prepare("SELECT * FROM quotation_items WHERE booking_id = ? ORDER BY id ASC");
$qi->bind_param("i", $booking_id);
$qi->execute();
$existing_items = $qi->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $names   = $_POST['item_name']  ?? [];
    $descs   = $_POST['item_desc']  ?? [];
    $qtys    = $_POST['item_qty']   ?? [];
    $prices  = $_POST['item_price'] ?? [];
    $nota    = trim($_POST['nota_sebut'] ?? '');

    /* Delete old items */
    $del = $conn->prepare("DELETE FROM quotation_items WHERE booking_id = ?");
    $del->bind_param("i", $booking_id);
    $del->execute();

    $grand_total = 0;

    /* Insert new items */
    $ins = $conn->prepare("
        INSERT INTO quotation_items (booking_id, item_name, item_desc, qty, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($names as $i => $name) {
        if (empty(trim($name))) continue;
        $qty        = max(1, (int)$qtys[$i]);
        $unit_price = round((float)$prices[$i], 2);
        $total      = $qty * $unit_price;
        $grand_total += $total;
        $desc = $descs[$i] ?? '';
        $ins->bind_param("issidd", $booking_id, $name, $desc, $qty, $unit_price, $total);
        $ins->execute();
    }

    /* Update servis_booking */
    $upd = $conn->prepare("
        UPDATE servis_booking
        SET harga_sebut = ?, nota_sebut = ?, status = 'quoted'
        WHERE id = ? AND usahawan_id = ?
    ");
    $upd->bind_param("dsii", $grand_total, $nota, $booking_id, $usahawan_id);
    $upd->execute();

    echo "<script>alert('Sebut harga berjaya dihantar kepada pelanggan!'); window.location='booking_detail.php?id=$booking_id';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jana Sebut Harga — #<?= $booking_id ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#111827;--ink-soft:#6B7280;--ink-muted:#9CA3AF;
  --bg:#F0F2F5;--surface:#fff;--border:#E5E7EB;
  --blue:#2563EB;--blue-dark:#1D4ED8;--blue-lt:#EFF6FF;
  --red:#DC2626;--green:#059669;--amber:#D97706;--amber-lt:#FFFBEB;
  --radius:12px;--radius-lg:20px;
  --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--ink);padding-top:90px;min-height:100vh}

.page{max-width:1000px;margin:0 auto;padding:36px 20px 80px}

/* top bar */
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.back-link{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:var(--ink-soft);text-decoration:none;transition:color .2s}
.back-link:hover{color:var(--blue)}

/* booking summary pill */
.summary-pill{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 22px;margin-bottom:28px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;box-shadow:var(--shadow)}
.pill-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--ink-muted);margin-bottom:3px}
.pill-value{font-size:14px;font-weight:600;color:var(--ink)}

/* card */
.card{background:var(--surface);border-radius:var(--radius-lg);padding:32px;box-shadow:var(--shadow);margin-bottom:24px}
.card-title{font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:var(--ink);margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--border)}

/* table */
.q-table{width:100%;border-collapse:collapse;font-size:14px}
.q-table thead{background:#FAFAFA}
.q-table th{padding:10px 12px;text-align:left;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--ink-muted);border-bottom:2px solid var(--border)}
.q-table td{padding:10px 8px;border-bottom:1px solid var(--border);vertical-align:top}
.q-table tr:last-child td{border-bottom:none}

input[type=text],input[type=number],textarea,select{
  width:100%;padding:9px 11px;border:1.5px solid var(--border);border-radius:8px;
  font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--ink);
  transition:border-color .2s;background:#fff
}
input:focus,textarea:focus{outline:none;border-color:var(--blue)}
textarea{resize:vertical;min-height:54px}
input[readonly]{background:#F9FAFB;color:var(--ink-soft);cursor:default}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 18px;border:none;border-radius:var(--radius);font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}
.btn-add{background:var(--bg);color:var(--ink-soft);border:1.5px dashed var(--border);margin-top:14px}
.btn-add:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-lt)}
.btn-del{background:transparent;color:var(--red);border:1.5px solid #FEE2E2;border-radius:8px;padding:7px 10px;font-size:12px;cursor:pointer;font-weight:600;white-space:nowrap;transition:all .2s}
.btn-del:hover{background:var(--red);color:#fff}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.25)}
.btn-primary:hover{background:var(--blue-dark);transform:translateY(-1px)}
.btn-ghost{background:transparent;color:var(--ink-soft);border:1.5px solid var(--border)}
.btn-ghost:hover{border-color:var(--ink-soft);color:var(--ink)}
.btn-print{background:#111;color:#fff}
.btn-print:hover{background:#333}

/* total box */
.total-box{width:300px;margin-left:auto;background:#FAFAFA;border:1px solid var(--border);border-radius:var(--radius);padding:20px}
.total-row{display:flex;justify-content:space-between;padding:7px 0;font-size:14px;color:var(--ink-soft)}
.total-row.grand{font-weight:700;font-size:17px;color:var(--ink);border-top:2px solid var(--border);padding-top:12px;margin-top:4px}
.grand .amount{color:var(--blue)}

/* nota */
.form-group{margin-bottom:20px}
label{display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:var(--ink)}
.hint{font-size:12px;color:var(--ink-muted);margin-top:4px}

/* alert */
.alert-info{background:var(--amber-lt);border-left:3px solid var(--amber);border-radius:var(--radius);padding:14px 16px;font-size:13px;color:#92400E;margin-bottom:22px;line-height:1.6}

/* action row */
.action-row{display:flex;justify-content:flex-end;gap:12px;margin-top:8px;flex-wrap:wrap}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.page>*{animation:fadeUp .35s ease both}
.card:nth-child(2){animation-delay:.05s}
.card:nth-child(3){animation-delay:.1s}

@media(max-width:640px){
  .total-box{width:100%}
  .q-table{font-size:12px}
  .action-row{flex-direction:column}
}
@media print{.btn,.top-bar,.summary-pill .back-link{display:none}}
</style>
</head>
<body>
<div class="page">

  <!-- TOP BAR -->
  <div class="top-bar">
    <a href="booking_detail.php?id=<?= $booking_id ?>" class="back-link">
      ← Kembali ke Butiran Tempahan
    </a>
    <span style="font-size:13px;color:var(--ink-muted)">Tempahan #<?= str_pad($booking_id,5,'0',STR_PAD_LEFT) ?></span>
  </div>

  <!-- BOOKING SUMMARY -->
  <div class="summary-pill">
    <div>
      <div class="pill-label">Servis</div>
      <div class="pill-value"><?= htmlspecialchars($booking['nama_servis']) ?></div>
    </div>
    <div>
      <div class="pill-label">Pelanggan</div>
      <div class="pill-value"><?= htmlspecialchars($booking['nama_pelanggan']) ?></div>
    </div>
    <div>
      <div class="pill-label">Tarikh Temujanji</div>
      <div class="pill-value"><?= date('d M Y', strtotime($booking['tarikh'])) ?> · <?= $booking['masa'] ?></div>
    </div>
    <div>
      <div class="pill-label">Alamat</div>
      <div class="pill-value"><?= htmlspecialchars($booking['alamat'] ?? '-') ?></div>
    </div>
  </div>

  <form method="POST" id="quotationForm">

  <!-- ITEMS TABLE -->
  <div class="card">
    <div class="card-title">📋 Senarai Item &amp; Caj</div>

    <div class="alert-info">
      ℹ️ Masukkan semua item kerja, alat ganti, dan caj buruh. Pelanggan akan melihat senarai ini sebelum membuat keputusan.
    </div>

    <table class="q-table">
      <thead>
        <tr>
          <th width="4%">#</th>
          <th width="20%">Item</th>
          <th>Penerangan</th>
          <th width="8%">Qty</th>
          <th width="14%">Harga / Unit (RM)</th>
          <th width="14%">Jumlah (RM)</th>
          <th width="8%"></th>
        </tr>
      </thead>
      <tbody id="itemsBody">
        <?php
        $rows = !empty($existing_items) ? $existing_items : [['item_name'=>'','item_desc'=>'','qty'=>1,'unit_price'=>'','total_price'=>'']];
        foreach ($rows as $idx => $item): ?>
        <tr>
          <td style="text-align:center;color:var(--ink-muted);font-weight:600;" class="row-num"><?= $idx+1 ?></td>
          <td><input type="text" name="item_name[]" value="<?= htmlspecialchars($item['item_name']) ?>" placeholder="cth: Bateri"></td>
          <td><textarea name="item_desc[]" placeholder="Penerangan (pilihan)"><?= htmlspecialchars($item['item_desc']) ?></textarea></td>
          <td><input type="number" name="item_qty[]" class="item-qty" value="<?= (int)($item['qty'] ?? 1) ?>" min="1"></td>
          <td><input type="number" name="item_price[]" class="item-price" step="0.01" min="0" value="<?= !empty($item['unit_price']) ? number_format((float)$item['unit_price'],2,'.','') : '' ?>" placeholder="0.00"></td>
          <td><input type="number" name="item_total[]" class="item-total" step="0.01" readonly value="<?= !empty($item['total_price']) ? number_format((float)$item['total_price'],2,'.','') : '' ?>" placeholder="0.00"></td>
          <td style="text-align:center"><button type="button" class="btn-del del-row">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <button type="button" class="btn btn-add" id="addRow">+ Tambah Item</button>

    <!-- TOTAL -->
    <div class="total-box" style="margin-top:28px">
      <div class="total-row">
        <span>Subtotal</span>
        <span id="subtotalEl">RM 0.00</span>
      </div>
      <div class="total-row grand">
        <span>Jumlah Besar</span>
        <span id="grandTotalEl" class="amount">RM 0.00</span>
      </div>
    </div>
  </div>

  <!-- NOTA -->
  <div class="card">
    <div class="card-title">📝 Nota Tambahan (Pilihan)</div>
    <div class="form-group">
      <label>Nota kepada Pelanggan</label>
      <textarea name="nota_sebut" rows="4" placeholder="cth: Anggaran masa siap 2-3 hari bekerja. Harga termasuk kos perjalanan."><?= htmlspecialchars($booking['nota_sebut'] ?? '') ?></textarea>
      <div class="hint">Nota ini akan dipaparkan kepada pelanggan bersama sebut harga.</div>
    </div>

    <!-- ACTIONS -->
    <div class="action-row">
      <button type="button" class="btn btn-ghost" onclick="window.print()">🖨️ Print</button>
      <a href="booking_detail.php?id=<?= $booking_id ?>" class="btn btn-ghost">Batal</a>
      <button type="submit" class="btn btn-primary">📤 Hantar Sebut Harga</button>
    </div>
  </div>

  </form>
</div>

<script>
const body       = document.getElementById('itemsBody');
const subtotalEl = document.getElementById('subtotalEl');
const grandEl    = document.getElementById('grandTotalEl');

function recalc() {
  let sub = 0;
  body.querySelectorAll('tr').forEach(row => {
    const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
    const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
    const tot   = row.querySelector('.item-total');
    const val   = qty * price;
    if (tot) tot.value = val > 0 ? val.toFixed(2) : '';
    sub += val;
  });
  subtotalEl.textContent = 'RM ' + sub.toFixed(2);
  grandEl.textContent    = 'RM ' + sub.toFixed(2);
  renumber();
}

function renumber() {
  body.querySelectorAll('tr').forEach((row, i) => {
    const n = row.querySelector('.row-num');
    if (n) n.textContent = i + 1;
  });
}

function makeRow(n) {
  return `<tr>
    <td style="text-align:center;color:var(--ink-muted);font-weight:600;" class="row-num">${n}</td>
    <td><input type="text" name="item_name[]" placeholder="cth: Bateri"></td>
    <td><textarea name="item_desc[]" placeholder="Penerangan (pilihan)"></textarea></td>
    <td><input type="number" name="item_qty[]" class="item-qty" value="1" min="1"></td>
    <td><input type="number" name="item_price[]" class="item-price" step="0.01" min="0" placeholder="0.00"></td>
    <td><input type="number" name="item_total[]" class="item-total" step="0.01" readonly placeholder="0.00"></td>
    <td style="text-align:center"><button type="button" class="btn-del del-row">✕</button></td>
  </tr>`;
}

document.getElementById('addRow').onclick = () => {
  body.insertAdjacentHTML('beforeend', makeRow(body.rows.length + 1));
};

body.addEventListener('input', e => {
  if (e.target.matches('.item-qty,.item-price')) recalc();
});

body.addEventListener('click', e => {
  if (e.target.matches('.del-row')) {
    if (body.rows.length === 1) { alert('Perlu sekurang-kurangnya satu item.'); return; }
    e.target.closest('tr').remove();
    recalc();
  }
});

/* Validate at least one item has name+price before submit */
document.getElementById('quotationForm').addEventListener('submit', e => {
  const names  = [...body.querySelectorAll('[name="item_name[]"]')].map(i => i.value.trim()).filter(Boolean);
  const prices = [...body.querySelectorAll('.item-price')].map(i => parseFloat(i.value)||0);
  const total  = prices.reduce((a,b)=>a+b,0);
  if (!names.length || total <= 0) {
    e.preventDefault();
    alert('Sila masukkan sekurang-kurangnya satu item dengan harga.');
  }
});

recalc();
</script>
</body>
</html>