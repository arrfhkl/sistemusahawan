<?php
session_start();
require 'stripe-php/init.php';
require 'connection.php';

\Stripe\Stripe::setApiKey('sk_test_51SSyVKGbRah2rwXWBgdH4IanXzobmPMY6sEGinsOQQn1p6jenf74YK0L18K82P84OVxFEzECHwqbvbpfuVUSmHO100XSiafaMV');

// Validate access
if (!isset($_GET['session_id'])) {
    die("Invalid access.");
}

if (!isset($_SESSION['usahawan_id'])) {
    die("User not logged in.");
}

if (!isset($_SESSION['pending_order'])) {
    die("No pending order found.");
}

$session_id = $_GET['session_id'];
$order_data = $_SESSION['pending_order'];

try {
    // Verify payment with Stripe
    \Stripe\ApiRequestor::setHttpClient(
        new \Stripe\HttpClient\CurlClient([CURLOPT_SSL_VERIFYPEER => false])
    );
    
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    // Check if payment was successful
    if ($session->payment_status !== 'paid') {
        die("Payment not completed.");
    }

    // Start database transaction
    $conn->begin_transaction();

    // Prepare data for pesanan table
    $usahawan_id = $order_data['usahawan_id'];
    $no_pesanan = 'ORD' . date('Ymd') . rand(1000, 9999);
    $nama_pelanggan = $order_data['nama_pelanggan'];
    $no_telefon = $order_data['no_telefon'];
    $alamat = $order_data['alamat'];
    $nota = $order_data['nota'];
    $cara_hantar = $order_data['cara_hantar'];
    $cara_bayar = $order_data['cara_bayar'];
    $jumlah_bayaran = $order_data['total_amount'];

    // Insert into pesanan table
    $sql_pesanan = "INSERT INTO pesanan 
                    (usahawan_id, no_pesanan, nama_pelanggan, no_telefon, alamat, 
                     nota, cara_hantar, cara_bayar, jumlah_bayaran, 
                     status_pesanan, status_bayaran, stripe_session_id, tarikh_pesanan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', ?, NOW())";
    
    $stmt_pesanan = $conn->prepare($sql_pesanan);
    $stmt_pesanan->bind_param(
        "isssssssds",
        $usahawan_id,
        $no_pesanan,
        $nama_pelanggan,
        $no_telefon,
        $alamat,
        $nota,
        $cara_hantar,
        $cara_bayar,
        $jumlah_bayaran,
        $session_id
    );
    
    if (!$stmt_pesanan->execute()) {
        throw new Exception("Failed to create order: " . $stmt_pesanan->error);
    }

    // Get the inserted order ID
    $pesanan_id = $conn->insert_id;

    // Insert items into pesanan_item table
    $sql_item = "INSERT INTO pesanan_item 
                 (pesanan_id, produk_id, nama_produk, gambar_url, harga, kuantiti, subtotal) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_item = $conn->prepare($sql_item);

    foreach ($order_data['cart_items'] as $item) {
        $stmt_item->bind_param(
            "iissdid",
            $pesanan_id,
            $item['produk_id'],
            $item['nama_produk'],
            $item['gambar_url'],
            $item['harga'],
            $item['kuantiti'],
            $item['subtotal']
        );
        
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to insert order item: " . $stmt_item->error);
        }
    }

    // Delete items from cart
    $cart_ids = array_column($order_data['cart_items'], 'cart_id');
    $cart_ids_list = implode(',', $cart_ids);
    
    $sql_delete_cart = "DELETE FROM cart WHERE id IN ($cart_ids_list) AND usahawan_id = ?";
    $stmt_delete = $conn->prepare($sql_delete_cart);
    $stmt_delete->bind_param("i", $usahawan_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Failed to clear cart: " . $stmt_delete->error);
    }

    // Commit transaction
    $conn->commit();

    // Clear pending order from session
    unset($_SESSION['pending_order']);

    // Display success page
    ?>
    <!DOCTYPE html>
    <html lang="ms">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pembayaran Berjaya - Sistem Usahawan Pahang</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            /* ===== Background Premium dengan Watermark Jata Pahang ===== */
            body {
                margin: 0;
                background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
                background-attachment: fixed;
                color: #111;
                overflow-x: hidden;
                position: relative;
                font-family: Arial, sans-serif;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* ✨ Cahaya lembut keemasan & hitam bergerak */
            body::before {
                content: "";
                position: fixed;
                inset: 0;
                background:
                    radial-gradient(circle at 25% 30%, rgba(0, 0, 0, 0.05), transparent 70%),
                    radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.15), transparent 70%);
                background-repeat: no-repeat;
                animation: royalWave 25s ease-in-out infinite alternate;
                z-index: -3;
                mix-blend-mode: overlay;
            }

            /* 🏛️ Multiple Watermark Jata Pahang */
            body::after {
                content: "";
                position: fixed;
                inset: 0;
                background-color: transparent;
                background-image: url("assets/img/jatapahang.png");
                background-repeat: repeat;
                background-size: 180px 180px;
                background-position: center;
                opacity: 0.15;
                filter: grayscale(5%) brightness(1.3) contrast(1.1);
                animation: watermarkFloat 40s linear infinite;
                z-index: -2;
            }

            @keyframes watermarkFloat {
                0% { background-position: 0 0; opacity: 0.14; }
                50% { background-position: 80px 60px; opacity: 0.18; }
                100% { background-position: 0 0; opacity: 0.14; }
            }

            @keyframes royalWave {
                0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
                100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
            }

            .success-container {
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                text-align: center;
                max-width: 700px;
                width: 100%;
                animation: slideIn 0.5s ease-out;
                border: 1px solid rgba(255, 215, 0, 0.3);
                backdrop-filter: blur(10px);
            }

            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            .success-icon {
                width: 90px;
                height: 90px;
                background: linear-gradient(135deg, #4CAF50, #45a049);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 25px;
                animation: checkmark 0.5s ease-out 0.3s both;
                box-shadow: 0 5px 20px rgba(76, 175, 80, 0.4);
            }

            .success-icon::after {
                content: "✓";
                color: white;
                font-size: 55px;
                font-weight: bold;
            }

            @keyframes checkmark {
                from { transform: scale(0) rotate(-180deg); }
                to { transform: scale(1) rotate(0deg); }
            }

            h1 {
                color: #003399;
                margin-bottom: 10px;
                font-size: 2.2rem;
                font-weight: 700;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            }

            .subtitle {
                color: #555;
                margin-bottom: 30px;
                font-size: 1.1rem;
                line-height: 1.6;
            }

            .order-details {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                padding: 30px;
                border-radius: 15px;
                margin: 30px 0;
                text-align: left;
                border: 1px solid rgba(255, 215, 0, 0.3);
                box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            }

            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 14px 0;
                border-bottom: 1px solid rgba(0, 51, 153, 0.1);
            }

            .detail-row:last-child {
                border-bottom: none;
            }

            .detail-label {
                color: #666;
                font-weight: 600;
                font-size: 0.95rem;
            }

            .detail-value {
                color: #003399;
                font-weight: 700;
                text-align: right;
            }

            .total-row {
                background: linear-gradient(135deg, #003399 0%, #0066FF 100%);
                margin: -30px -30px 0 -30px;
                padding: 20px 30px;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 -3px 10px rgba(0,0,0,0.1);
            }

            .total-row .detail-label,
            .total-row .detail-value {
                color: #fff;
                font-size: 1.4rem;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            }

            .btn {
                display: inline-block;
                padding: 14px 35px;
                margin: 10px 5px;
                border-radius: 25px;
                text-decoration: none;
                font-weight: 700;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                font-size: 1rem;
            }

            .btn-primary {
                background: linear-gradient(135deg, #003399 0%, #0066FF 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(0, 51, 153, 0.4);
            }

            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(0, 51, 153, 0.6);
                background: linear-gradient(135deg, #0066FF 0%, #003399 100%);
            }

            .btn-secondary {
                background: white;
                color: #003399;
                border: 2px solid #003399;
                box-shadow: 0 4px 15px rgba(0, 51, 153, 0.2);
            }

            .btn-secondary:hover {
                background: #003399;
                color: white;
                transform: translateY(-3px);
            }

            .shipping-info {
                background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
                border-left: 5px solid #FFD700;
                padding: 20px;
                margin: 25px 0;
                border-radius: 10px;
                text-align: left;
                box-shadow: 0 3px 10px rgba(255, 193, 7, 0.2);
            }

            .shipping-info strong {
                color: #856404;
                font-size: 1.1rem;
                display: block;
                margin-bottom: 12px;
            }

            .shipping-info li {
                color: #333;
                margin: 8px 0;
                font-weight: 500;
                list-style: none;
                padding-left: 20px;
                position: relative;
            }

            .shipping-info li::before {
                content: "•";
                color: #FFD700;
                font-weight: bold;
                font-size: 1.3rem;
                position: absolute;
                left: 0;
            }

            .status-badge {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 600;
            }

            .badge-pending {
                background: linear-gradient(135deg, #ff9800, #ff6f00);
                color: white;
            }

            .badge-paid {
                background: linear-gradient(135deg, #4CAF50, #45a049);
                color: white;
            }

            @media (max-width: 768px) {
                .success-container {
                    padding: 25px;
                }
                
                h1 {
                    font-size: 1.8rem;
                }
                
                .btn {
                    width: 100%;
                    margin: 8px 0;
                }
                
                .detail-row {
                    flex-direction: column;
                    gap: 5px;
                }
                
                .detail-value {
                    text-align: left;
                }
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon"></div>
            <h1>🎉 Pembayaran Berjaya!</h1>
            <p class="subtitle">Terima kasih atas pembelian anda. Pesanan anda telah berjaya diproses dan disimpan dalam sistem.</p>
            
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">No. Pesanan:</span>
                    <span class="detail-value"><?= $no_pesanan ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama:</span>
                    <span class="detail-value"><?= htmlspecialchars($nama_pelanggan) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">No. Telefon:</span>
                    <span class="detail-value"><?= htmlspecialchars($no_telefon) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cara Hantar:</span>
                    <span class="detail-value">
                        <?= $cara_hantar == 'delivery' ? '🚚 Hantar ke Rumah' : '📦 Pickup di Dropspot' ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status Pesanan:</span>
                    <span class="detail-value">
                        <span class="status-badge badge-pending">⏳ Pending</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status Bayaran:</span>
                    <span class="detail-value">
                        <span class="status-badge badge-paid">✓ Telah Dibayar</span>
                    </span>
                </div>
                <div class="detail-row total-row">
                    <span class="detail-label">Jumlah Bayaran:</span>
                    <span class="detail-value">RM <?= number_format($jumlah_bayaran, 2) ?></span>
                </div>
            </div>

            <div class="shipping-info">
                <strong>📦 Maklumat Penghantaran:</strong>
                <li><?= htmlspecialchars($nama_pelanggan) ?></li>
                <li><?= htmlspecialchars($no_telefon) ?></li>
                <li><?= htmlspecialchars($alamat) ?></li>
                <?php if (!empty($nota)): ?>
                    <br><strong style="font-size: 1rem;">💬 Nota:</strong> 
                    <span style="color: #555;"><?= htmlspecialchars($nota) ?></span>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="pesanan_detail.php" class="btn btn-primary">📋 Lihat Pesanan Saya</a>
                <a href="index.php" class="btn btn-secondary">🏠 Laman Utama</a>
            </div>
        </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo "<!DOCTYPE html>
    <html lang='ms'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Ralat - Sistem Usahawan Pahang</title>
        <link rel='icon' type='image/png' href='assets/img/jatapahang.png'>
        <style>
            body { 
                font-family: Arial; 
                text-align: center; 
                padding: 50px; 
                background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-box { 
                background: white; 
                padding: 40px; 
                border-radius: 15px; 
                max-width: 500px; 
                margin: auto; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                border: 1px solid rgba(244, 67, 54, 0.3);
            }
            .error-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #f44336, #d32f2f);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 50px;
                color: white;
            }
            h1 { 
                color: #f44336; 
                margin-bottom: 15px;
            }
            p {
                color: #555;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .btn { 
                display: inline-block; 
                padding: 12px 30px; 
                background: linear-gradient(135deg, #003399 0%, #0066FF 100%);
                color: white; 
                text-decoration: none; 
                border-radius: 25px; 
                margin-top: 15px;
                font-weight: 700;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(0, 51, 153, 0.4);
            }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <div class='error-icon'>✕</div>
            <h1>❌ Ralat Berlaku</h1>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <a href='cart.php' class='btn'>🛒 Kembali ke Troli</a>
        </div>
    </body>
    </html>";
    
    error_log("Order processing error: " . $e->getMessage());
}

$conn->close();
?>