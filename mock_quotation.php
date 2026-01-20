<!doctype html>
<html lang="ms">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Quotation</title>
  <script src="/_sdk/element_sdk.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            margin: 0;
            min-height: 100%;
        }

        html {
            height: 100%;
        }

        /* Container */
        .quotation-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 50px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
            font-size: 13px;
            color: #9ca3af;
            background: #f9fafb;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .logo-box:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Form Elements */
        input, textarea {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #fafafa;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: #9ca3af;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-group {
            margin-bottom: 12px;
        }

        /* Document Info */
        .document-info {
            text-align: right;
            margin-top: 25px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 12px;
        }

        .document-info-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .document-info-row:last-child {
            margin-bottom: 0;
        }

        .document-info-label {
            font-weight: 600;
            color: #4b5563;
            min-width: 120px;
            text-align: right;
        }

        .document-info-input {
            width: 200px;
        }

        /* Divider */
        .divider {
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            margin: 35px 0;
            border: none;
            border-radius: 1px;
        }

        /* Party Section */
        .party-section {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .party-box {
            flex: 1;
        }

        .party-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 35px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .items-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 16px 12px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
        }

        .items-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px;
            font-size: 14px;
            background: #ffffff;
            vertical-align: top;
        }

        .items-table tbody tr:hover td {
            background: #f8f9ff;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .col-bil {
            width: 5%;
            text-align: center;
        }

        .col-item {
            width: 25%;
        }

        .col-desc {
            width: 35%;
        }

        .col-qty {
            width: 10%;
            text-align: center;
        }

        .col-total {
            width: 15%;
            text-align: right;
        }

        .col-action {
            width: 10%;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .row-number {
            font-weight: 600;
            color: #667eea;
        }

        /* Table Inputs */
        .table-input {
            padding: 10px;
            font-size: 13px;
        }

        .table-textarea {
            min-height: 60px;
            padding: 10px;
            font-size: 13px;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 12px;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .btn-add {
            background: #ecfdf5;
            color: #059669;
            margin-top: 15px;
        }

        .btn-add:hover {
            background: #d1fae5;
        }

        /* Total Section */
        .total-section {
            margin-top: 30px;
            margin-left: auto;
            width: 320px;
            background: #f8f9ff;
            border-radius: 12px;
            padding: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .total-row:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 5px;
            border-top: 2px solid #667eea;
        }

        .total-label {
            font-weight: 500;
            color: #6b7280;
        }

        .total-value {
            font-weight: 600;
            color: #1f2937;
        }

        .grand-total-label {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
        }

        .grand-total-value {
            font-weight: 700;
            color: #667eea;
            font-size: 20px;
        }

        .total-input {
            width: 120px;
            text-align: right;
            padding: 8px 12px;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 40px;
        }

        .signature-box {
            flex: 1;
        }

        .signature-label {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 40px;
        }

        .signature-line {
            border-top: 2px solid #1f2937;
            padding-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        /* Notes Section */
        .notes-section {
            margin-top: 35px;
        }

        .notes-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .quotation-container {
                padding: 25px;
            }

            .header {
                flex-direction: column-reverse;
                align-items: center;
            }

            .logo-box {
                margin-bottom: 20px;
            }

            .party-section {
                flex-direction: column;
                gap: 25px;
            }

            .document-info {
                text-align: left;
            }

            .document-info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .document-info-label {
                text-align: left;
            }

            .document-info-input {
                width: 100%;
            }

            .total-section {
                width: 100%;
            }

            .footer {
                flex-direction: column;
                gap: 30px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .items-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
  <style>@view-transition { navigation: auto; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
  <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
 </head>
 <body>
  <form id="quotationForm">
   <div class="quotation-container"><!-- HEADER -->
    <div class="header">
     <div class="company-info">
      <div class="input-group"><input type="text" name="company_name" placeholder="Nama Syarikat" required>
      </div>
      <div class="input-group"><input type="text" name="company_address" placeholder="Alamat Syarikat">
      </div>
      <div class="input-group"><input type="text" name="company_phone" placeholder="No. Telefon">
      </div>
      <div class="input-group"><input type="email" name="company_email" placeholder="Alamat Email">
      </div>
     </div>
     <div class="logo-box"><span>📷 Muat Naik Logo</span>
     </div>
    </div>
    <div class="document-info">
     <div class="document-info-row"><span class="document-info-label">Tarikh:</span> <input type="date" name="quotation_date" class="document-info-input" required>
     </div>
     <div class="document-info-row"><span class="document-info-label">No. Sebut Harga:</span> <input type="text" name="quotation_no" class="document-info-input" placeholder="QT-001" required>
     </div>
    </div>
    <hr class="divider"><!-- PARTY INFO -->
    <div class="party-section">
     <div class="party-box">
      <h4 class="party-title">📋 Disediakan Untuk</h4><textarea name="customer_info" placeholder="Nama &amp; alamat pelanggan"></textarea>
     </div>
     <div class="party-box">
      <h4 class="party-title">🏢 Disediakan Oleh</h4><textarea name="seller_info" placeholder="Maklumat syarikat anda"></textarea>
     </div>
    </div><!-- ITEMS TABLE -->
    <table class="items-table">
     <thead>
      <tr>
       <th class="col-bil">Bil</th>
       <th class="col-item">Item</th>
       <th class="col-desc">Penerangan</th>
       <th class="col-qty">Kuantiti</th>
       <th class="col-total">Jumlah (RM)</th>
       <th class="col-action">Tindakan</th>
      </tr>
     </thead>
     <tbody id="itemsTableBody">
      <tr>
       <td class="text-center"><span class="row-number">1</span></td>
       <td><input type="text" name="item_name[]" class="table-input" placeholder="Nama item"></td>
       <td><textarea name="item_desc[]" class="table-textarea" placeholder="Penerangan item"></textarea></td>
       <td><input type="number" name="item_qty[]" class="table-input text-center" placeholder="0" min="0" value="1"></td>
       <td><input type="number" name="item_total[]" class="table-input text-right item-total" placeholder="0.00" min="0" step="0.01"></td>
       <td class="text-center"><button type="button" class="btn btn-danger btn-delete">🗑️</button></td>
      </tr>
     </tbody>
    </table><button type="button" class="btn btn-add" id="addRowBtn">➕ Tambah Item</button> <!-- TOTAL SECTION -->
    <div class="total-section">
     <div class="total-row"><span class="total-label">Jumlah Kecil</span> <span class="total-value" id="subtotalDisplay">RM 0.00</span>
     </div>
     <div class="total-row"><span class="total-label">Diskaun (%)</span> <input type="number" name="discount" class="total-input" id="discountInput" value="0" min="0" max="100">
     </div>
     <div class="total-row"><span class="total-label">SST (6%)</span> <span class="total-value" id="taxDisplay">RM 0.00</span>
     </div>
     <div class="total-row"><span class="grand-total-label">Jumlah Besar</span> <span class="grand-total-value" id="grandTotalDisplay">RM 0.00</span>
     </div>
    </div><!-- NOTES -->
    <div class="notes-section">
     <h4 class="notes-title">📝 Nota / Terma &amp; Syarat</h4><textarea name="notes" placeholder="Masukkan nota atau terma &amp; syarat..."></textarea>
    </div><!-- FOOTER -->
    <div class="footer">
     <div class="signature-box">
      <p class="signature-label">Tandatangan Pelanggan</p>
      <div class="signature-line">
       Nama &amp; Tarikh
      </div>
     </div>
     <div class="signature-box">
      <p class="signature-label">Tandatangan Penjual</p>
      <div class="signature-line">
       Nama &amp; Tarikh
      </div>
     </div>
    </div><!-- ACTION BUTTONS -->
    <div class="action-buttons"><button type="button" class="btn btn-secondary" id="resetBtn">🔄 Set Semula</button> <button type="button" class="btn btn-primary" id="printBtn">🖨️ Cetak</button> <button type="submit" class="btn btn-primary">💾 Simpan Quotation</button>
    </div>
   </div>
  </form>
  <script>
    const defaultConfig = {
        page_title: 'Create Quotation'
    };

    let config = { ...defaultConfig };

    async function onConfigChange(newConfig) {
        document.title = newConfig.page_title || defaultConfig.page_title;
    }

    function mapToCapabilities(config) {
        return {
            recolorables: [],
            borderables: [],
            fontEditable: undefined,
            fontSizeable: undefined
        };
    }

    function mapToEditPanelValues(config) {
        return new Map([
            ['page_title', config.page_title || defaultConfig.page_title]
        ]);
    }

    // Initialize SDK
    if (window.elementSdk) {
        window.elementSdk.init({
            defaultConfig,
            onConfigChange,
            mapToCapabilities,
            mapToEditPanelValues
        });
    }

    // Table functionality
    const itemsTableBody = document.getElementById('itemsTableBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const taxDisplay = document.getElementById('taxDisplay');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');
    const discountInput = document.getElementById('discountInput');
    const resetBtn = document.getElementById('resetBtn');
    const printBtn = document.getElementById('printBtn');
    const quotationForm = document.getElementById('quotationForm');

    let rowCount = 1;

    // Add new row
    addRowBtn.addEventListener('click', function() {
        rowCount++;
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="text-center"><span class="row-number">${rowCount}</span></td>
            <td><input type="text" name="item_name[]" class="table-input" placeholder="Nama item"></td>
            <td><textarea name="item_desc[]" class="table-textarea" placeholder="Penerangan item"></textarea></td>
            <td><input type="number" name="item_qty[]" class="table-input text-center" placeholder="0" min="0" value="1"></td>
            <td><input type="number" name="item_total[]" class="table-input text-right item-total" placeholder="0.00" min="0" step="0.01"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-delete">🗑️</button></td>
        `;
        itemsTableBody.appendChild(newRow);
        updateRowNumbers();
    });

    // Delete row
    itemsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
            const row = e.target.closest('tr');
            if (itemsTableBody.querySelectorAll('tr').length > 1) {
                row.remove();
                updateRowNumbers();
                calculateTotals();
            }
        }
    });

    // Update row numbers
    function updateRowNumbers() {
        const rows = itemsTableBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const numberSpan = row.querySelector('.row-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
        });
        rowCount = rows.length;
    }

    // Calculate totals
    function calculateTotals() {
        const totalInputs = document.querySelectorAll('.item-total');
        let subtotal = 0;
        
        totalInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            subtotal += value;
        });

        const discount = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotal * (discount / 100);
        const afterDiscount = subtotal - discountAmount;
        const tax = afterDiscount * 0.06;
        const grandTotal = afterDiscount + tax;

        subtotalDisplay.textContent = 'RM ' + subtotal.toFixed(2);
        taxDisplay.textContent = 'RM ' + tax.toFixed(2);
        grandTotalDisplay.textContent = 'RM ' + grandTotal.toFixed(2);
    }

    // Listen for input changes
    itemsTableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-total')) {
            calculateTotals();
        }
    });

    discountInput.addEventListener('input', calculateTotals);

    // Reset form
    resetBtn.addEventListener('click', function() {
        quotationForm.reset();
        
        // Keep only one row
        const rows = itemsTableBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            if (index > 0) row.remove();
        });
        
        rowCount = 1;
        calculateTotals();
    });

    // Print
    printBtn.addEventListener('click', function() {
        window.print();
    });

    // Form submit
    quotationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(quotationForm);
        const data = {};
        
        formData.forEach((value, key) => {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        
        // Show success message
        const successMsg = document.createElement('div');
        successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 16px 24px; border-radius: 8px; font-weight: 600; z-index: 1000; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);';
        successMsg.textContent = '✅ Quotation berjaya disimpan!';
        document.body.appendChild(successMsg);
        
        setTimeout(() => {
            successMsg.remove();
        }, 3000);
    });

    // Set default date to today
    const dateInput = document.querySelector('input[name="quotation_date"]');
    if (dateInput) {
        dateInput.valueAsDate = new Date();
    }
</script>
 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9c0ac907664d068e',t:'MTc2ODg3MjQwMy4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>