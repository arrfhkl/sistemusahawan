<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("Sila log masuk sebagai usahawan.");
}

$usahawan_id = (int)$_SESSION['usahawan_id'];

/* ===============================
   KPI: JUALAN, PESANAN, PELANGGAN
================================ */

// Jumlah jualan (hanya produk milik usahawan)
$sql = "
SELECT 
    IFNULL(SUM(pi.subtotal),0) AS jumlah_jualan,
    COUNT(DISTINCT p.id) AS jumlah_pesanan,
    COUNT(DISTINCT p.no_telefon) AS pelanggan
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$kpi = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ===============================
   STATUS PESANAN
================================ */
$status_sql = "
SELECT p.status_pesanan, COUNT(DISTINCT p.id) AS jumlah
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
GROUP BY p.status_pesanan
";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$res = $stmt->get_result();

$status = [
    'pending'=>0,
    'processing'=>0,
    'shipped'=>0,
    'delivered'=>0
];

while ($row = $res->fetch_assoc()) {
    $status[$row['status_pesanan']] = $row['jumlah'];
}
$stmt->close();

/* ===============================
   JUALAN BULANAN (JAN–DEC FIX)
================================ */

// Label 12 bulan (tetap)
$bulanLabel = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// Default semua bulan = 0
$jualanBulanan = array_fill(0, 12, 0);

// SQL ambil jualan ikut bulan
$chart_sql = "
SELECT 
    MONTH(p.tarikh_pesanan) AS bulan,
    SUM(pi.subtotal) AS jumlah
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
GROUP BY MONTH(p.tarikh_pesanan)
";

$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$res = $stmt->get_result();

// Masukkan data ke bulan yang betul
while ($r = $res->fetch_assoc()) {
    $index = (int)$r['bulan'] - 1; // Jan = 0
    $jualanBulanan[$index] = (float)$r['jumlah'];
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Laporan Perniagaan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.container{max-width:1200px;margin:100px auto;padding:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px}
.card{background:#fff;padding:20px;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.08)}
h1{margin-bottom:20px}
</style>
</head>

<body>

<div class="container">
<h1>📊 Laporan Perniagaan</h1>

<div class="grid">
  <div class="card"><b>Jumlah Jualan</b><h2>RM <?= number_format($kpi['jumlah_jualan'],2) ?></h2></div>
  <div class="card"><b>Jumlah Pesanan</b><h2><?= $kpi['jumlah_pesanan'] ?></h2></div>
  <div class="card"><b>Pelanggan Unik</b><h2><?= $kpi['pelanggan'] ?></h2></div>
</div>

<br>

<div class="grid">
  <div class="card">🕒 Pending<br><h2><?= $status['pending'] ?></h2></div>
  <div class="card">⚙️ Processing<br><h2><?= $status['processing'] ?></h2></div>
  <div class="card">🚚 Shipped<br><h2><?= $status['shipped'] ?></h2></div>
  <div class="card">✅ Delivered<br><h2><?= $status['delivered'] ?></h2></div>
</div>

<br>

<div class="card">
<h3>Jualan Bulanan</h3>
<canvas id="salesChart"></canvas>
<p style="font-size:13px;color:#666;margin-top:8px">
Nota: Bulan tanpa jualan akan dipaparkan sebagai RM0.
</p>

</div>
</div>

<script>
new Chart(document.getElementById('salesChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($bulanLabel) ?>,
    datasets: [{
      label: 'Jualan (RM)',
      data: <?= json_encode($jualanBulanan) ?>,
      backgroundColor: '#003399'
    }]
  },
  options: {
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: value => 'RM ' + value
        }
      }
    }
  }
});
</script>


</body>
</html>

<?php include "footer.php"; ?>
