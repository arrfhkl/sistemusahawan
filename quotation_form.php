<?php
include "connection.php";
include "header.php";

$conn->query("SET time_zone = '+08:00'");

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$user_id = (int)$_SESSION['usahawan_id']; // ✅ WAJIB DI SINI

/* Load seller (usahawan) info */
$stmtSeller = $conn->prepare("
  SELECT nama, perniagaan, alamat, telefon, email
  FROM usahawan
  WHERE id = ?
  LIMIT 1
");
$stmtSeller->bind_param("i", $user_id);
$stmtSeller->execute();
$seller = $stmtSeller->get_result()->fetch_assoc();

if (!$seller) {
  die("Maklumat usahawan tidak dijumpai");
}

$user_id = (int)$_SESSION['usahawan_id'];
$quotation_id = (int)($_GET['quotation_id'] ?? 0);
$chat_id = (int)($_GET['chat_id'] ?? 0);

if ($quotation_id <= 0 || $chat_id <= 0) {
  die("Permintaan tidak sah");
}

/* Load quotation */
$stmt = $conn->prepare("
  SELECT q.*, s.nama AS servis_nama
  FROM quotation q
  JOIN servis s ON s.id = q.servis_id
  WHERE q.id = ?
    AND q.chat_id = ?
    AND q.seller_id = ?
    AND q.status = 'requested'
  LIMIT 1
");
$stmt->bind_param("iii", $quotation_id, $chat_id, $user_id);
$stmt->execute();
$quotation = $stmt->get_result()->fetch_assoc();

$quotation_no = 'QT-' . str_pad($quotation['id'], 5, '0', STR_PAD_LEFT);

if (!$quotation) {
  die("Quotation tidak dijumpai atau telah diproses");
}
?>

<!doctype html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Respond Quotation</title>

  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
        * {
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
    }

    body {
      padding: 30px;
    }

    /* ================= CONTAINER ================= */
    .quotation-container {
      max-width: 900px;
      margin: auto;
      background: #ffffff;
      padding: 50px;
      border-radius: 16px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }

    /* ================= FORM ELEMENT ================= */
    input, textarea {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      background: #fafafa;
      font-family: inherit;
    }

    input:focus, textarea:focus {
      outline: none;
      border-color: #667eea;
      background: #fff;
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    .input-group {
      margin-bottom: 12px;
    }

    /* ================= HEADER ================= */
    .header {
      display: flex;
      justify-content: space-between;
      gap: 30px;
    }

    .company-info {
      flex: 1;
    }

    .logo-box {
      width: 130px;
      height: 130px;
      border: 2px dashed #d1d5db;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #9ca3af;
      cursor: pointer;
      background: #f9fafb;
    }

    /* ================= DOCUMENT INFO ================= */
    .document-info {
      margin-top: 25px;
      padding: 20px;
      background: #f8f9ff;
      border-radius: 12px;
      text-align: right;
    }

    .document-info-row {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-bottom: 10px;
    }

    .document-info-label {
      font-weight: 600;
      min-width: 130px;
      text-align: right;
    }

    .document-info-input {
      width: 200px;
    }

    /* ================= DIVIDER ================= */
    .divider {
      height: 2px;
      background: linear-gradient(90deg, #667eea, #764ba2);
      margin: 35px 0;
      border: none;
    }

    /* ================= PARTY ================= */
    .party-section {
      display: flex;
      gap: 40px;
    }

    .party-box {
      flex: 1;
    }

    .party-title {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 12px;
      border-bottom: 2px solid #667eea;
      display: inline-block;
      padding-bottom: 6px;
    }

    /* ================= TABLE ================= */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 35px;
      border-radius: 12px;
      overflow: hidden;
    }

    th {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      padding: 14px;
      font-size: 14px;
      text-align: left;
    }

    td {
      padding: 12px;
      border-bottom: 1px solid #e5e7eb;
      vertical-align: top;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }

    .row-number {
      font-weight: 600;
      color: #667eea;
    }

    .btn {
      padding: 12px 22px;
      font-size: 14px;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
    }

    .btn-secondary {
      background: #f3f4f6;
    }

    .btn-danger {
      background: #fee2e2;
      color: #dc2626;
      padding: 8px 12px;
    }

    .btn-add {
      margin-top: 15px;
      background: #ecfdf5;
      color: #059669;
    }

    /* ================= TOTAL ================= */
    .total-section {
      margin-top: 30px;
      margin-left: auto;
      width: 320px;
      background: #f8f9ff;
      padding: 20px;
      border-radius: 12px;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px dashed #e5e7eb;
    }

    .total-row:last-child {
      border-bottom: none;
      border-top: 2px solid #667eea;
      padding-top: 15px;
    }

    .grand-total {
      font-size: 20px;
      font-weight: 700;
      color: #667eea;
    }

    /* ================= FOOTER ================= */
    .footer {
      margin-top: 45px;
      display: flex;
      gap: 40px;
    }

    .signature-line {
      border-top: 2px solid #111827;
      margin-top: 40px;
      padding-top: 8px;
      font-size: 13px;
      color: #6b7280;
    }

    .action-buttons {
      margin-top: 40px;
      padding-top: 30px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    @media (max-width: 768px) {
      body { padding: 15px; }
      .quotation-container { padding: 25px; }
      .header, .party-section, .footer, .action-buttons {
        flex-direction: column;
      }
      .document-info { text-align: left; }
      .document-info-row { flex-direction: column; align-items: flex-start; }
      .document-info-input, .total-section { width: 100%; }
    }
  </style>
</head>

<body>
  <br><br>
    <form method="post" action="submit_quotation.php">

  <!-- 🔑 HIDDEN KEYS -->
  <input type="hidden" name="quotation_id" value="<?= (int)$quotation['id'] ?>">
  <input type="hidden" name="chat_id" value="<?= (int)$chat_id ?>">

  <div class="quotation-container">

    <h3>
      Quotation untuk servis:
      <strong><?= htmlspecialchars($quotation['servis_nama']) ?></strong>
    </h3>

    <!-- COMPANY INFO -->
    <div class="header">
        <div class="company-info">

          <input
            value="<?= htmlspecialchars($seller['perniagaan']) ?>"
            readonly
          >

          <textarea readonly><?= htmlspecialchars($seller['alamat']) ?></textarea>

          <input
            value="<?= htmlspecialchars($seller['telefon']) ?>"
            readonly
          >

          <input
            value="<?= htmlspecialchars($seller['email']) ?>"
            readonly
          >

        </div>

      <div class="logo-box">📷 Logo</div>
    </div>

    <!-- DOCUMENT INFO -->
    <div class="document-info">
      <input type="date" name="quotation_date" required>
      <input value="<?= $quotation_no ?>" readonly>
    </div>

    <hr class="divider">

    <!-- PARTY -->
    <div class="party-section">
      <div class="party-box">
        <h4 class="party-title">Disediakan Untuk</h4>
        <textarea name="customer_info"></textarea>
      </div>
      <div class="party-box">
        <h4 class="party-title">Disediakan Oleh</h4>
        <textarea readonly>
        <?= htmlspecialchars($seller['nama']) ?>

        <?= htmlspecialchars($seller['perniagaan']) ?>

        <?= htmlspecialchars($seller['telefon']) ?>
        <?= htmlspecialchars($seller['email']) ?>
        </textarea>
      </div>
    </div>

    <!-- ITEMS -->
    <table>
      <thead>
        <tr>
          <th>Bil</th>
          <th>Item</th>
          <th>Penerangan</th>
          <th>Qty</th>
          <th>Harga (RM)</th>
          <th>Jumlah (RM)</th>
          <th></th>
        </tr>
      </thead>

      <tbody id="itemsTable">
        <tr>
          <td class="text-center row-number">1</td>
          <td><input name="item_name[]" required></td>
          <td><textarea name="item_desc[]"></textarea></td>

          <td>
            <input type="number" name="item_qty[]" class="item-qty" value="1" min="1">
          </td>

          <td>
            <input type="number" name="item_price[]" class="item-price" step="0.01" min="0">
          </td>

          <td>
            <input type="number" name="item_total[]" class="item-total" step="0.01" readonly>
          </td>

          <td class="text-center">
            <button type="button" class="btn btn-danger delete-row">🗑️</button>
          </td>
        </tr>
      </tbody>

    </table>

    <button type="button" class="btn btn-add" id="addRow">➕ Tambah Item</button>

    <!-- TOTAL -->
    <div class="total-section">
      <div class="total-row">
        <span>Subtotal</span>
        <span id="subtotal">RM 0.00</span>
      </div>
      <div class="total-row">
        <span>SST (6%)</span>
        <span id="tax">RM 0.00</span>
      </div>
      <div class="total-row">
        <span>Jumlah Besar</span>
        <span class="grand-total" id="grandTotal">RM 0.00</span>
      </div>
    </div>

    <!-- ACTION -->
    <div class="action-buttons">
      <button type="reset" class="btn btn-secondary">Reset</button>
      <button type="submit" class="btn btn-primary">
        Hantar Quotation
      </button>
    </div>

  </div>
</form>

<script>
const itemsTable = document.getElementById('itemsTable');
const addRow = document.getElementById('addRow');
const subtotalEl = document.getElementById('subtotal');
const taxEl = document.getElementById('tax');
const grandTotalEl = document.getElementById('grandTotal');

function calculateRow(row) {
  const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
  const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
  const totalInput = row.querySelector('.item-total');
  const total = qty * price;
  totalInput.value = total.toFixed(2);
}

function calculateAll() {
  let subtotal = 0;

  itemsTable.querySelectorAll('tr').forEach(row => {
    calculateRow(row);
    subtotal += parseFloat(row.querySelector('.item-total')?.value) || 0;
  });

  const tax = subtotal * 0.06;
  subtotalEl.textContent = 'RM ' + subtotal.toFixed(2);
  taxEl.textContent = 'RM ' + tax.toFixed(2);
  grandTotalEl.textContent = 'RM ' + (subtotal + tax).toFixed(2);
}

addRow.onclick = () => {
  const row = itemsTable.insertRow();
  row.innerHTML = `
    <td class="text-center row-number">${itemsTable.rows.length}</td>
    <td><input name="item_name[]" required></td>
    <td><textarea name="item_desc[]"></textarea></td>

    <td><input type="number" name="item_qty[]" class="item-qty" value="1" min="1"></td>
    <td><input type="number" name="item_price[]" class="item-price" step="0.01" min="0"></td>
    <td><input type="number" name="item_total[]" class="item-total" step="0.01" readonly></td>

    <td class="text-center">
      <button type="button" class="btn btn-danger delete-row">🗑️</button>
    </td>
  `;
};

itemsTable.addEventListener('input', e => {
  if (
    e.target.classList.contains('item-qty') ||
    e.target.classList.contains('item-price')
  ) {
    calculateAll();
  }
});

itemsTable.addEventListener('click', e => {
  if (e.target.classList.contains('delete-row')) {
    e.target.closest('tr').remove();

    [...itemsTable.rows].forEach((row, i) => {
      row.querySelector('.row-number').textContent = i + 1;
    });

    document.addEventListener('DOMContentLoaded', calculateAll);
  }
});
</script>

</body>

</html>
