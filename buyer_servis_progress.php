<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$buyer_id = (int)$_SESSION['usahawan_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
  die("Order tidak sah");
}

/* Load order (buyer only) */
$stmt = $conn->prepare("
  SELECT
    so.*,
    u.nama AS seller_name,
    s.nama AS servis_name
  FROM servis_order so
  JOIN usahawan u ON u.id = so.seller_id
  JOIN servis s ON s.id = so.servis_id
  WHERE so.id = ?
    AND so.buyer_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
  die("Pesanan tidak dijumpai");
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Status Servis</title>

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
}

.badge.pending{background:#fef3c7;color:#92400e}
.badge.in_progress{background:#e0f2fe;color:#0369a1}
.badge.completed{background:#dcfce7;color:#166534}

.timeline{
  border-left:3px solid #e5e7eb;
  padding-left:15px;
}

.step{
  margin-bottom:15px;
}

.step.done{color:#16a34a}
.step.wait{color:#9ca3af}
</style>
</head>

<body>

<div class="container">

  <!-- ORDER INFO -->
  <div class="card">
    <div class="card-title">📄 Maklumat Servis</div>

    <div class="row">
      <span>Servis</span>
      <strong><?= htmlspecialchars($order['servis_name']) ?></strong>
    </div>

    <div class="row">
      <span>Penjual</span>
      <strong><?= htmlspecialchars($order['seller_name']) ?></strong>
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
    <div class="card-title">⏱️ Progress Servis</div>

    <div class="timeline">
      <div class="step done">
        ✔️ Pesanan diterima
      </div>

      <div class="step <?= $order['started_at'] ? 'done' : 'wait' ?>">
        <?= $order['started_at'] ? '✔️' : '⏳' ?>
        Servis bermula
      </div>

      <div class="step <?= $order['completed_at'] ? 'done' : 'wait' ?>">
        <?= $order['completed_at'] ? '✔️' : '⏳' ?>
        Servis disiapkan
      </div>
    </div>
  </div>

</div>

<?php include "footer.php"; ?>
</body>
</html>
