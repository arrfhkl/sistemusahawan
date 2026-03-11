<?php
session_start();
require 'stripe-php/init.php';
require 'connection.php';

\Stripe\Stripe::setApiKey('sk_test_51SSyVKGbRah2rwXWBgdH4IanXzobmPMY6sEGinsOQQn1p6jenf74YK0L18K82P84OVxFEzECHwqbvbpfuVUSmHO100XSiafaMV');

$session_id = $_GET['session_id'] ?? '';
$booking_id = (int)($_GET['booking_id'] ?? 0);

if (!$session_id || !$booking_id) die("Maklumat tidak lengkap.");

\Stripe\ApiRequestor::setHttpClient(
    new \Stripe\HttpClient\CurlClient([CURLOPT_SSL_VERIFYPEER => false])
);

/* ── Verify payment with Stripe ── */
try {
    $stripe_session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (Exception $e) {
    die("Ralat Stripe: " . $e->getMessage());
}

if ($stripe_session->payment_status === 'paid') {

    /* ── Update booking status to 'paid' ── */
    $upd = $conn->prepare("
        UPDATE servis_booking 
        SET status = 'paid', stripe_session_id = ?
        WHERE id = ? AND status = 'approved'
    ");
    $upd->bind_param("si", $session_id, $booking_id);
    $upd->execute();

    /* Clear session */
    unset($_SESSION['pending_servis_payment']);

    /* Reload booking details for receipt display */
    $stmt = $conn->prepare("
        SELECT sb.*, s.nama AS nama_servis, u.nama AS nama_usahawan
        FROM servis_booking sb
        JOIN servis s ON sb.service_id = s.id
        JOIN usahawan u ON sb.usahawan_id = u.id
        WHERE sb.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    $qi = $conn->prepare("SELECT * FROM quotation_items WHERE booking_id = ? ORDER BY id ASC");
    $qi->bind_param("i", $booking_id);
    $qi->execute();
    $q_items = $qi->get_result()->fetch_all(MYSQLI_ASSOC);

} else {
    die("Pembayaran belum selesai. Sila cuba lagi.");
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran Berjaya</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#111827;--ink-soft:#6B7280;--bg:#F0F2F5;--surface:#fff;--border:#E5E7EB;--green:#059669;--green-lt:#ECFDF5;--blue:#2563EB;--radius:12px;--radius-lg:20px;--shadow:0 4px 24px rgba(0,0,0,.08)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow);max-width:520px;width:100%;padding:40px 36px;text-align:center}
.success-icon{width:72px;height:72px;background:var(--green-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 20px}
h1{font-family:'Sora',sans-serif;font-size:22px;color:var(--ink);margin-bottom:8px}
.sub{font-size:14px;color:var(--ink-soft);margin-bottom:28px}
.receipt{background:#FAFAFA;border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:left;margin-bottom:24px}
.receipt-row{display:flex;justify-content:space-between;padding:7px 0;font-size:13px;border-bottom:1px solid var(--border);color:var(--ink-soft)}
.receipt-row:last-child{border-bottom:none;font-weight:700;font-size:15px;color:var(--ink)}
.receipt-row span:last-child{color:var(--ink)}
.btn{display:inline-block;padding:12px 28px;border-radius:var(--radius);font-weight:600;font-size:14px;text-decoration:none;background:var(--blue);color:#fff;margin-top:4px}
@keyframes pop{0%{transform:scale(.8);opacity:0}70%{transform:scale(1.05)}100%{transform:scale(1);opacity:1}}
.success-icon{animation:pop .4s ease both}
</style>
</head>
<body>
<div class="card">
  <div class="success-icon">✅</div>
  <h1>Pembayaran Berjaya!</h1>
  <p class="sub">Terima kasih. Tempahan servis anda telah disahkan dan pembayaran diterima.</p>

  <div class="receipt">
    <div class="receipt-row"><span>Servis</span><span><?= htmlspecialchars($booking['nama_servis']) ?></span></div>
    <div class="receipt-row"><span>Usahawan</span><span><?= htmlspecialchars($booking['nama_usahawan']) ?></span></div>
    <div class="receipt-row"><span>Pelanggan</span><span><?= htmlspecialchars($booking['nama_pelanggan']) ?></span></div>
    <div class="receipt-row"><span>Tarikh Temujanji</span><span><?= date('d M Y', strtotime($booking['tarikh'])) ?> · <?= $booking['masa'] ?></span></div>
    <div class="receipt-row"><span>ID Tempahan</span><span>#<?= str_pad($booking['id'],5,'0',STR_PAD_LEFT) ?></span></div>
    <?php foreach ($q_items as $item): ?>
    <div class="receipt-row">
      <span><?= htmlspecialchars($item['item_name']) ?> × <?= $item['qty'] ?></span>
      <span>RM <?= number_format($item['total_price'],2) ?></span>
    </div>
    <?php endforeach; ?>
    <div class="receipt-row"><span>Jumlah Dibayar</span><span style="color:var(--green)">RM <?= number_format($booking['harga_sebut'],2) ?></span></div>
  </div>

  <a href="customer_booking.php" class="btn">← Kembali ke Tempahan Saya</a>
</div>
</body>
</html>