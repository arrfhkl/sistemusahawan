<?php
include __DIR__ . "/../inc/db.php";
include __DIR__ . "/../inc/helpers.php";
include __DIR__ . "/../inc/auth.php";
require_login();


// simple stats
$stmt = $pdo->query('SELECT COUNT(*) AS c FROM parcels');
$total = $stmt->fetchColumn();


$parcels = $pdo->query('SELECT * FROM parcels ORDER BY created_at DESC LIMIT 50')->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
<h1>Admin Dashboard</h1>
<p>Total parcels: <?= esc($total) ?></p>
<p><a href="/admin/add_parcel.php">Add Parcel</a></p>
<table border="1">
<tr><th>ID</th><th>Tracking</th><th>Receiver</th><th>Status</th><th>Action</th></tr>
<?php foreach ($parcels as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><?= esc($p['tracking_no']) ?></td>
<td><?= esc($p['receiver_name']) ?></td>
<td><?= esc($p['status']) ?></td>
<td><a href="/admin/update_parcel.php?tn=<?= urlencode($p['tracking_no']) ?>">Update</a></td>
</tr>
<?php endforeach; ?>
</table>
<p><a href="/">Back to site</a></p>
</body>
</html>