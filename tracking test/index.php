<?php
include "inc/db.php";
include "inc/helpers.php";


$message = '';
$parcel = null;
$logs = [];
if (!empty($_GET['tracking_no'])) {
$tn = $_GET['tracking_no'];
$stmt = $pdo->prepare('SELECT * FROM parcels WHERE tracking_no = ?');
$stmt->execute([$tn]);
$parcel = $stmt->fetch();


$stmt = $pdo->prepare('SELECT * FROM parcel_logs WHERE tracking_no = ? ORDER BY log_time DESC');
$stmt->execute([$tn]);
$logs = $stmt->fetchAll();
if (!$parcel) $message = 'No parcel found with that tracking number.';
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Parcel Tracking</title>
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="container">
<h1>Semak Tracking Parcel</h1>
<form method="GET">
<input type="text" name="tracking_no" placeholder="Enter tracking number" required>
<button type="submit">Track</button>
</form>


<?php if ($message): ?>
<p><?= esc($message) ?></p>
<?php endif; ?>


<?php if ($parcel): ?>
<h2>Tracking: <?= esc($parcel['tracking_no']) ?></h2>
<p>Status: <strong><?= esc($parcel['status']) ?></strong></p>


<h3>Timeline</h3>
<ul>
<?php foreach ($logs as $log): ?>
<li><?= esc($log['log_time']) ?> — <?= esc($log['status']) ?> (<?= esc($log['location']) ?>) — <?= esc($log['remarks']) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>


<p><a href="admin/login.php">Admin Login</a></p>
</div>
</body>
</html>