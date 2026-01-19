<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Quotation</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            padding: 30px;
        }

        .quotation-container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ddd;
        }

        /* ================= HEADER ================= */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            margin: 0;
            font-size: 24px;
        }

        .company-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .logo {
            width: 120px;
            height: 120px;
            border: 1px dashed #aaa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #777;
        }

        .quotation-meta {
            text-align: right;
            margin-top: 20px;
        }

        .quotation-meta p {
            margin: 5px 0;
            font-size: 14px;
        }

        hr {
            margin: 30px 0;
            border: none;
            border-top: 1px solid #ccc;
        }

        /* ================= PARTY INFO ================= */
        .party-info {
            display: flex;
            justify-content: space-between;
        }

        .party-box {
            width: 45%;
        }

        .party-box h3 {
            margin-bottom: 10px;
            font-size: 16px;
            border-bottom: 1px solid #000;
            display: inline-block;
        }

        .party-box p {
            font-size: 14px;
            line-height: 1.6;
        }

        /* ================= TABLE ================= */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 14px;
        }

        table th {
            background: #f0f0f0;
            text-align: center;
        }

        table td {
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ================= TOTAL ================= */
        .total-section {
            margin-top: 20px;
            width: 40%;
            margin-left: auto;
        }

        .total-section table td {
            border: none;
            padding: 6px 0;
        }

        .total-section .label {
            text-align: left;
        }

        .total-section .amount {
            text-align: right;
        }

        .grand-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .stamp {
            width: 180px;
            height: 120px;
            border: 1px dashed #aaa;
            text-align: center;
            padding-top: 45px;
            font-size: 13px;
            color: #777;
        }

        .signature {
            text-align: right;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="quotation-container">

    <!-- HEADER -->
    <div class="header">
        <div class="company-info">
            <h1>Nama Company</h1>
            <p>Alamat Company / HQ</p>
            <p>Telefon: 012-3456789</p>
            <p>Email: company@email.com</p>
        </div>

        <div class="logo">
            Logo Company
        </div>
    </div>

    <div class="quotation-meta">
        <p><strong>Tarikh:</strong> 19 Januari 2026</p>
        <p><strong>Quotation No:</strong> QT-0001</p>
    </div>

    <hr>

    <!-- PARTY INFO -->
    <div class="party-info">
        <div class="party-box">
            <h3>Disediakan Untuk:</h3>
            <p>
                Nama Pelanggan<br>
                Alamat Pelanggan<br>
                Bandar, Negeri
            </p>
        </div>

        <div class="party-box">
            <h3>Disediakan Oleh:</h3>
            <p>
                Nama Company<br>
                Alamat Company / HQ<br>
                Bandar, Negeri
            </p>
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th style="width:5%">Bil</th>
                <th style="width:25%">Item</th>
                <th>Description</th>
                <th style="width:10%">Kuantiti</th>
                <th style="width:15%">Total (RM)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Servis A</td>
                <td>Penerangan servis secara ringkas</td>
                <td class="text-center">1</td>
                <td class="text-right">300.00</td>
            </tr>

            <tr>
                <td class="text-center">2</td>
                <td>Servis B</td>
                <td>Penerangan servis tambahan</td>
                <td class="text-center">2</td>
                <td class="text-right">200.00</td>
            </tr>
        </tbody>
    </table>

    <!-- TOTAL -->
    <div class="total-section">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="amount">RM 500.00</td>
            </tr>
            <tr>
                <td class="label">Cukai (6%)</td>
                <td class="amount">RM 30.00</td>
            </tr>
            <tr>
                <td class="label grand-total">Grand Total</td>
                <td class="amount grand-total">RM 530.00</td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="stamp">
            Cop Rasmi
        </div>

        <div class="signature">
            <p>__________________________</p>
            <p>Tandatangan</p>
            <p>Tarikh:</p>
        </div>
    </div>

</div>

</body>
</html>
