<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';
require_login();


$tn = $_GET['tn'] ?? '';
$parcel = null;
if ($tn) {
$stmt = $pdo->prepare('SELECT * FROM parcels WHERE tracking_no = ?');
$stmt->execute([$tn]);
$parcel = $stmt->fetch();
}


if ($_POST) {
$tn = $_POST['tracking_no'];
$status = $_POST['status'];
$location = $_POST['location'];
$remarks = $_POST['remarks'];


$stmt = $pdo->prepare('UPDATE parcels SET status = ? WHERE tracking_no = ?');
$stmt->execute([$status, $tn]);


$stmt = $pdo->prepare('INSERT INTO parcel_logs (tracking_no, status, location, remarks) VALUES (?,?,?,?)');
$stmt->execute([$tn, $status, $location, $remarks]);


$message = 'Updated.';
header('Location: dashboard.php');
exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Update Parcel</title></head>
<body>
<h1>Update Parcel</h1>
<?php if ($parcel): ?>
<form method="POST">
<input type="hidden" name="tracking_no" value="<?= esc($parcel['tracking_no']) ?>">
Status: <input name="status" value="<?= esc($parcel['status']) ?>"><br>
Location: <input name="location"><br>
Remarks: <input name="remarks"><br>
<button type="submit">Update</button>
</form>
<?php else: ?>
<p>Parcel not found.</p>
<?php endif; ?>
</body>
</html>