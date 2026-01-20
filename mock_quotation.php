<!doctype html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Quotation</title>

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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

<form id="quotationForm">
  <div class="quotation-container">

    <!-- HEADER -->
    <div class="header">
      <div class="company-info">
        <div class="input-group"><input type="text" name="company_name" placeholder="Nama Syarikat"></div>
        <div class="input-group"><input type="text" name="company_address" placeholder="Alamat Syarikat"></div>
        <div class="input-group"><input type="text" name="company_phone" placeholder="No Telefon"></div>
        <div class="input-group"><input type="email" name="company_email" placeholder="Email"></div>
      </div>
      <div class="logo-box">📷 Logo</div>
    </div>

    <!-- DOCUMENT INFO -->
    <div class="document-info">
      <div class="document-info-row">
        <span class="document-info-label">Tarikh</span>
        <input type="date" name="quotation_date" class="document-info-input">
      </div>
      <div class="document-info-row">
        <span class="document-info-label">No Quotation</span>
        <input type="text" name="quotation_no" class="document-info-input" placeholder="QT-001">
      </div>
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
        <textarea name="seller_info"></textarea>
      </div>
    </div>

    <!-- TABLE -->
    <table>
      <thead>
        <tr>
          <th width="5%">Bil</th>
          <th width="25%">Item</th>
          <th>Penerangan</th>
          <th width="10%">Qty</th>
          <th width="15%">Jumlah (RM)</th>
          <th width="10%">Tindakan</th>
        </tr>
      </thead>
      <tbody id="itemsTable">
        <tr>
          <td class="text-center"><span class="row-number">1</span></td>
          <td><input type="text" name="item_name[]"></td>
          <td><textarea name="item_desc[]"></textarea></td>
          <td><input type="number" name="item_qty[]" value="1"></td>
          <td><input type="number" name="item_total[]" class="item-total" step="0.01"></td>
          <td class="text-center"><button type="button" class="btn btn-danger delete-row">🗑️</button></td>
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

    <!-- FOOTER -->
    <div class="footer">
      <div>
        <div class="signature-line">Tandatangan Pelanggan</div>
      </div>
      <div>
        <div class="signature-line">Tandatangan Penjual</div>
      </div>
    </div>

    <!-- ACTION -->
    <div class="action-buttons">
      <button type="reset" class="btn btn-secondary">Reset</button>
      <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
      <button type="submit" class="btn btn-primary">Simpan</button>
    </div>

  </div>
</form>

<script>
  const itemsTable = document.getElementById('itemsTable');
  const addRow = document.getElementById('addRow');
  const subtotalEl = document.getElementById('subtotal');
  const taxEl = document.getElementById('tax');
  const grandTotalEl = document.getElementById('grandTotal');

  function calculate() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(i => {
      subtotal += parseFloat(i.value) || 0;
    });
    const tax = subtotal * 0.06;
    subtotalEl.textContent = 'RM ' + subtotal.toFixed(2);
    taxEl.textContent = 'RM ' + tax.toFixed(2);
    grandTotalEl.textContent = 'RM ' + (subtotal + tax).toFixed(2);
  }

  addRow.onclick = () => {
    const row = itemsTable.insertRow();
    row.innerHTML = `
      <td class="text-center"><span class="row-number">${itemsTable.rows.length}</span></td>
      <td><input type="text" name="item_name[]"></td>
      <td><textarea name="item_desc[]"></textarea></td>
      <td><input type="number" name="item_qty[]" value="1"></td>
      <td><input type="number" name="item_total[]" class="item-total" step="0.01"></td>
      <td class="text-center"><button type="button" class="btn btn-danger delete-row">🗑️</button></td>
    `;
  };

  itemsTable.addEventListener('input', e => {
    if (e.target.classList.contains('item-total')) calculate();
  });

  itemsTable.addEventListener('click', e => {
    if (e.target.classList.contains('delete-row')) {
      e.target.closest('tr').remove();
      calculate();
    }
  });
</script>

</body>
</html>
