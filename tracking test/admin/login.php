<?php
include __DIR__ . "/../inc/db.php";
include __DIR__ . "/../inc/helpers.php";
session_start();


$error = '';
if ($_POST) {
$email = $_POST['email'];
$pass = $_POST['password'];


$stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
$stmt->execute([$email]);
$admin = $stmt->fetch();
if ($admin && password_verify($pass, $admin['password'])) {
$_SESSION['admin_id'] = $admin['id'];
header('Location: admin/dashboard.php');
exit;
} else {
$error = 'Login failed';
}
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Login</title></head>
<body>
<h1>Admin Login</h1>
<?php if ($error) echo '<p>'.$error.'</p>'; ?>
<form method="POST">
Email: <input name="email" type="email" required><br>
Password: <input name="password" type="password" required><br>
<button type="submit">Login</button>
</form>
</body>
</html>