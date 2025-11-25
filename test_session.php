<?php
session_start();

echo "<h2>🔍 SESSION DIAGNOSTIC</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ NOT ACTIVE") . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\n";

if (isset($_SESSION['usahawan_id'])) {
    echo "✅ User sudah login dengan ID: " . $_SESSION['usahawan_id'];
} else {
    echo "❌ User TIDAK login (session 'usahawan_id' tidak wujud)";
    echo "\n\n";
    echo "🔧 Perlu buat dummy session? Uncomment kod di bawah:\n";
    echo "// \$_SESSION['usahawan_id'] = 1;\n";
    echo "// header('Location: senarai.php');\n";
}

echo "</pre>";

// TEMPORARY FIX: Uncomment untuk create dummy session
// Hanya untuk testing sahaja!
/*
$_SESSION['usahawan_id'] = 1; // Guna ID user yang wujud dalam database
$_SESSION['nama'] = 'Test User';
echo "<script>alert('Session created! Redirecting...'); window.location.href='senarai.php';</script>";
*/
?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <hr>
    <h3>🔧 Quick Actions:</h3>
    <a href="login.php" style="display:inline-block; padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px; margin:5px;">Go to Login</a>
    <a href="senarai.php" style="display:inline-block; padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px; margin:5px;">Go to Senarai</a>
    <a href="?logout=1" style="display:inline-block; padding:10px 20px; background:#dc3545; color:#fff; text-decoration:none; border-radius:5px; margin:5px;">Logout</a>
    
    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        echo "<script>alert('Session destroyed!'); window.location.href='test_session.php';</script>";
    }
    ?>
</body>
</html>