<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$seller_id = (int)$_SESSION['usahawan_id'];
$order_id  = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
  die("Order tidak sah");
}

/* Load order (seller only) */
$stmt = $conn->prepare("
  SELECT
    so.*,
    u.nama AS buyer_name,
    s.nama AS servis_name
  FROM servis_order so
  JOIN usahawan u ON u.id = so.buyer_id
  JOIN servis s ON s.id = so.servis_id
  WHERE so.id = ?
    AND so.seller_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $order_id, $seller_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
  die("Order tidak dijumpai");
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Progress Servis</title>

<style>
.container{
  max-width:900px;
  margin:120px auto 40px;
  padding:0 20px;
}

.card{
  background:#fff;
  border-radius:16px;
  padding:25px;
  box-shadow:0 14px 35px rgba(0,0,0,.1);
  margin-bottom:25px;
}

.card-title{
  font-size:18px;
  font-weight:600;
  margin-bottom:15px;
}

.row{
  display:flex;
  justify-content:space-between;
  margin-bottom:8px;
  font-size:14px;
}

.badge{
  padding:6px 14px;
  border-radius:20px;
  font-size:13px;
  font-weight:600;
  display:inline-block;
}

.badge.pending{background:#fef3c7;color:#92400e}
.badge.in_progress{background:#e0f2fe;color:#0369a1}
.badge.completed{background:#dcfce7;color:#166534}

.btn{
  padding:12px 22px;
  border-radius:10px;
  border:none;
  font-weight:600;
  cursor:pointer;
}

.btn-start{
  background:#2563eb;
  color:#fff;
}

.btn-complete{
  background:#16a34a;
  color:#fff;
}

.info{
  font-size:14px;
  color:#555;
}
</style>
</head>

<body>

<div class="container">

  <!-- ORDER INFO -->
  <div class="card">
    <div class="card-title">📄 Maklumat Pesanan</div>

    <div class="row">
      <span>Servis</span>
      <strong><?= htmlspecialchars($order['servis_name']) ?></strong>
    </div>

    <div class="row">
      <span>Buyer</span>
      <strong><?= htmlspecialchars($order['buyer_name']) ?></strong>
    </div>

    <div class="row">
      <span>Harga</span>
      <strong>RM <?= number_format($order['price'],2) ?></strong>
    </div>

    <div class="row">
      <span>Status</span>
      <span class="badge <?= $order['status'] ?>">
        <?= strtoupper(str_replace('_',' ',$order['status'])) ?>
      </span>
    </div>
  </div>

  <!-- PROGRESS -->
  <div class="card">
    <div class="card-title">⚙️ Progress Servis</div>

    <?php if ($order['status'] === 'pending'): ?>
      <p class="info">Servis belum dimulakan.</p>
      <form method="post" action="update_servis_status.php">
        <input type="hidden" name="order_id" value="<?= $order_id ?>">
        <input type="hidden" name="action" value="start">
        <button class="btn btn-start">🚀 Mula Servis</button>
      </form>

    <?php elseif ($order['status'] === 'in_progress'): ?>
      <p class="info">Servis sedang dijalankan.</p>
      <form method="post" action="update_servis_status.php">
        <input type="hidden" name="order_id" value="<?= $order_id ?>">
        <input type="hidden" name="action" value="complete">
        <button class="btn btn-complete">✅ Tandakan Siap</button>
      </form>

    <?php else: ?>
      <p class="info">Servis telah siap sepenuhnya.</p>
    <?php endif; ?>
  </div>

</div>

<?php include "footer.php"; ?>
</body>
</html>
