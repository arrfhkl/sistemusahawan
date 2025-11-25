<?php
session_start();
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");

// Jumlah usahawan
$total = $conn->query("SELECT COUNT(*) AS total FROM usahawan")->fetch_assoc()['total'];

// Usahawan mengikut jenis
$jenis = $conn->query("SELECT jenis, COUNT(*) as jumlah FROM usahawan GROUP BY jenis");

// Usahawan daftar setiap bulan (12 bulan terakhir)
$bulan = $conn->query("
    SELECT DATE_FORMAT(tarikh_daftar, '%Y-%m') as bulan, COUNT(*) as jumlah
    FROM usahawan
    GROUP BY DATE_FORMAT(tarikh_daftar, '%Y-%m')
    ORDER BY bulan ASC
");
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Usahawan Pahang</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f9; }
    .card {
        background: white; 
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    h2 { margin-bottom: 10px; }
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
  </style>
</head>
<body>

<h1>📊 Dashboard Sistem Usahawan Pahang</h1>

<div class="grid">
  <!-- Jumlah Usahawan -->
  <div class="card">
    <h2>Jumlah Usahawan</h2>
    <p style="font-size:2rem; font-weight:bold;"><?= $total ?></p>
  </div>

  <!-- Carta Jenis Usahawan -->
  <div class="card">
    <h2>Jenis Usahawan</h2>
    <canvas id="jenisChart"></canvas>
  </div>

  <!-- Carta Pendaftaran Bulanan -->
  <div class="card">
    <h2>Pendaftaran Bulanan</h2>
    <canvas id="bulanChart"></canvas>
  </div>
</div>

<script>
  // Data untuk jenis
  const jenisData = {
    labels: [<?php while($row = $jenis->fetch_assoc()) { echo "'".$row['jenis']."',"; } ?>],
    datasets: [{
      label: 'Jumlah Usahawan',
      data: [<?php
        $jenis->data_seek(0);
        while($row = $jenis->fetch_assoc()) { echo $row['jumlah'].","; } ?>],
      backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF'],
    }]
  };

  new Chart(document.getElementById('jenisChart'), {
    type: 'pie',
    data: jenisData
  });

  // Data untuk bulanan
  const bulanLabels = [<?php while($b = $bulan->fetch_assoc()) { echo "'".$b['bulan']."',"; } ?>];
  <?php $bulan->data_seek(0); ?>
  const bulanData = [<?php while($b = $bulan->fetch_assoc()) { echo $b['jumlah'].","; } ?>];

  new Chart(document.getElementById('bulanChart'), {
    type: 'line',
    data: {
      labels: bulanLabels,
      datasets: [{
        label: 'Jumlah Pendaftaran',
        data: bulanData,
        borderColor: '#36A2EB',
        fill: false,
        tension: 0.1
      }]
    }
  });
</script>

</body>
</html>
