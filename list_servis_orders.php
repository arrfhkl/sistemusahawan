<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$seller_id = (int)$_SESSION['usahawan_id'];

$stmt = $conn->prepare("
  SELECT
    so.id,
    so.title,
    so.price,
    so.status,
    so.created_at,
    u.nama AS buyer_name,
    s.nama AS servis_name
  FROM servis_order so
  JOIN usahawan u ON u.id = so.buyer_id
  JOIN servis s ON s.id = so.servis_id
  WHERE so.seller_id = ?
  ORDER BY so.created_at DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Pesanan Servis Saya</title>

<style>
.container{
  max-width:1100px;
  margin:120px auto 40px;
  padding:0 20px;
}

.page-title{
  font-size:22px;
  font-weight:600;
  margin-bottom:25px;
}

.order-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
  gap:20px;
}

.order-card{
  background:#fff;
  border-radius:14px;
  padding:20px;
  box-shadow:0 10px 25px rgba(0,0,0,.08);
  transition:.25s;
}

.order-card:hover{
  transform:translateY(-4px);
  box-shadow:0 16px 35px rgba(0,0,0,.12);
}

.order-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}

.order-title{
  font-weight:600;
  font-size:16px;
}

.badge{
  padding:5px 10px;
  border-radius:20px;
  font-size:12px;
  font-weight:600;
}

.badge.pending{background:#fef3c7;color:#92400e}
.badge.paid{background:#dbeafe;color:#1e40af}
.badge.in_progress{background:#e0f2fe;color:#0369a1}
.badge.completed{background:#dcfce7;color:#166534}

.order-body{
  font-size:14px;
  color:#444;
  line-height:1.6;
}

.order-body strong{color:#111}

.order-footer{
  margin-top:15px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.price{
  font-weight:600;
  color:#111;
}

.btn{
  padding:8px 14px;
  border-radius:8px;
  font-size:13px;
  text-decoration:none;
  background:linear-gradient(135deg,#d4b26a,#b89544);
  color:#fff;
}
.btn:hover{opacity:.9}

.empty{
  background:#f9fafb;
  padding:30px;
  border-radius:12px;
  text-align:center;
  color:#666;
}
</style>
</head>

<body>

<div class="container">
  <div class="page-title">📋 Pesanan Servis Saya</div>

  <?php if ($orders->num_rows === 0): ?>
    <div class="empty">
      Belum ada pesanan servis buat masa ini.
    </div>
  <?php else: ?>

  <div class="order-grid">
    <?php while ($o = $orders->fetch_assoc()): ?>
      <div class="order-card">

        <div class="order-header">
          <div class="order-title">
            <?= htmlspecialchars($o['servis_name']) ?>
          </div>
          <span class="badge <?= $o['status'] ?>">
            <?= strtoupper(str_replace('_',' ',$o['status'])) ?>
          </span>
        </div>

        <div class="order-body">
          <div><strong>Buyer:</strong> <?= htmlspecialchars($o['buyer_name']) ?></div>
          <div><strong>Order:</strong> <?= htmlspecialchars($o['title']) ?></div>
          <div><strong>Tarikh:</strong> <?= date("d M Y", strtotime($o['created_at'])) ?></div>
        </div>

        <div class="order-footer">
          <div class="price">RM <?= number_format($o['price'],2) ?></div>
          <a href="seller_order_detail.php?order_id=<?= $o['id'] ?>" class="btn">
            Lihat Progress
          </a>
        </div>

      </div>
    <?php endwhile; ?>
  </div>

  <?php endif; ?>
</div>

<?php include "footer.php"; ?>
</body>
</html>
