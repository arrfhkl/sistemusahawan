<?php
include __DIR__ . "/../inc/db.php";
include __DIR__ . "/../inc/helpers.php";
include __DIR__ . "/../inc/auth.php";
require_login();


if ($_POST) {
$tn = gen_tracking_no();
$stmt = $pdo->prepare('INSERT INTO parcels (tracking_no, sender_name, receiver_name, receiver_phone, origin, destination, status) VALUES (?,?,?,?,?,?,?)');
$stmt->execute([$tn, $_POST['sender'], $_POST['receiver'], $_POST['phone'], $_POST['origin'], $_POST['destination'], 'Order Received']);


// initial log
$stmt = $pdo->prepare('INSERT INTO parcel_logs (tracking_no, status, location, remarks) VALUES (?,?,?,?)');
$stmt->execute([$tn, 'Order Received', $_POST['origin'], 'Parcel created by admin']);


$message = 'Parcel created: ' . $tn;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Add Parcel</title></head>
<body>
<h1>Add Parcel</h1>
<?php if (!empty($message)) echo '<p>'.$message.'</p>'; ?>
<form method="POST">
Sender: <input name="sender" required><br>
Receiver: <input name="receiver" required><br>
Phone: <input name="phone"><br>
Origin: <input name="origin"><br>
Destination: <input name="destination"><br>
<button type="submit">Add</button>
</form>
</body>
</html>