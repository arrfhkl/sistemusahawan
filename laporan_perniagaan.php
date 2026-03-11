<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   FILTER TAHUN
=========================== */
$tahunDipilih = isset($_GET['tahun'])
    ? (int)$_GET['tahun']
    : date('Y');

/* ===========================
   KPI UTAMA
=========================== */

// Jumlah jualan, pesanan & pelanggan unik
$sql = "
SELECT 
    IFNULL(SUM(pi.subtotal),0) AS jumlah_jualan,
    COUNT(DISTINCT p.id) AS jumlah_pesanan,
    COUNT(DISTINCT p.no_telefon) AS pelanggan
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
AND YEAR(p.tarikh_pesanan) = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usahawan_id, $tahunDipilih);
$stmt->execute();
$kpi = $stmt->get_result()->fetch_assoc() ?? [
    'jumlah_jualan' => 0,
    'jumlah_pesanan' => 0,
    'pelanggan' => 0
];
$stmt->close();

/* ===========================
   STATUS PESANAN
=========================== */
$status = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0
];

$status_sql = "
SELECT p.status_pesanan, COUNT(DISTINCT p.id) AS jumlah
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
AND YEAR(p.tarikh_pesanan) = ?
GROUP BY p.status_pesanan
";

$stmt = $conn->prepare($status_sql);
$stmt->bind_param("ii", $usahawan_id, $tahunDipilih);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $status[$row['status_pesanan']] = $row['jumlah'];
}
$stmt->close();

/* ===========================
   JUALAN BULANAN
=========================== */

$bulanLabel = [
    'Januari','Februari','Mac','April','Mei','Jun',
    'Julai','Ogos','September','Oktober','November','Disember'
];

$jualanBulanan = array_fill(0, 12, 0);

$chart_sql = "
SELECT 
    MONTH(p.tarikh_pesanan) AS bulan,
    SUM(pi.subtotal) AS jumlah
FROM pesanan p
JOIN pesanan_item pi ON p.id = pi.pesanan_id
JOIN produk pr ON pi.produk_id = pr.id
WHERE pr.usahawan_id = ?
AND YEAR(p.tarikh_pesanan) = ?
GROUP BY MONTH(p.tarikh_pesanan)
";

$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("ii", $usahawan_id, $tahunDipilih);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $jualanBulanan[(int)$r['bulan'] - 1] = (float)$r['jumlah'];
}
$stmt->close();

$analisis = [];

if ($kpi['jumlah_jualan'] > 0) {
    $analisis[] = "Prestasi jualan menunjukkan aktiviti perniagaan yang berterusan sepanjang tahun ini.";
} else {
    $analisis[] = "Tiada rekod jualan direkodkan bagi tahun ini.";
}

if ($status['pending'] > 0) {
    $analisis[] = "Terdapat tempahan yang masih menunggu dan memerlukan tindakan lanjut.";
}

if ($status['delivered'] > 0) {
    $analisis[] = "Sebahagian tempahan telah berjaya diselesaikan.";
}

// Jumlah tempahan
$q_tempahan = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis_booking 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Perniagaan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ICON COLOR (SAMA MACAM PRODUK) */
.icon-blue   { color:#007bff; }
.icon-green  { color:#28a745; }
.icon-orange { color:#fd7e14; }
.icon-red    { color:#dc3545; }

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 30px 40px;
    padding-top: 120px;
    min-height: 100vh;
}

/* HEADER */
.page-header {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    text-align: center;
}

.page-header h2 {
    margin: 0;
    font-weight: 700;
    color: #003399;
}

/* KPI */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    text-align: center;
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #003399;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

/* FILTER */
.filters-section {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.filters-section select {
    padding: 8px 12px;
    border-radius: 8px;
}

/* CARD */
.card-box {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}
/* ================= CETAK ================= */
@media print {

    body {
        background: #fff;
    }

    .no-print {
        display: none !important;
    }

    header, footer {
        display: none !important;
    }

    .container {
        padding: 0;
        margin: 0;
        max-width: 100%;
    }

    .stat-card,
    .card-box {
        box-shadow: none !important;
        border: 1px solid #ddd;
    }

    canvas {
        max-height: 400px;
    }

    h2 {
        text-align: center;
    }
}

.print-only {
    display: none;
}

@media print {
    .print-only {
        display: block;
        text-align: center;
        margin-bottom: 20px;
    }
}


</style>
</head>

<body>

<div class="container">

<div class="print-header print-only">
    <h2>Laporan Prestasi Perniagaan</h2>
    <p>
        Tahun: <strong><?= $tahunDipilih ?></strong><br>
        Tarikh Cetakan: <?= date('d/m/Y') ?>
    </p>
    <hr>
</div>

<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Laporan Perniagaan</h2>
</div>

<!-- KPI UTAMA -->
<div class="stats-container">

    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-number">RM <?= number_format($kpi['jumlah_jualan'],2) ?></div>
        <div class="stat-label">Jumlah Jualan</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-number"><?= $kpi['jumlah_pesanan'] ?></div>
        <div class="stat-label">Jumlah Pesanan</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-number"><?= $q_tempahan ?></div>
        <div class="stat-label">Jumlah Tempahan</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-number"><?= $kpi['pelanggan'] ?></div>
        <div class="stat-label">Pelanggan Unik</div>
    </div>

</div>

<!-- FILTER TAHUN -->
<div class="filters-section d-flex justify-content-between align-items-center no-print">
    <form method="GET">
        <label><b>Tahun:</b></label>
        <select name="tahun" onchange="this.form.submit()">
            <?php
            $tahunSemasa = date('Y');
            for ($t = $tahunSemasa; $t >= $tahunSemasa - 5; $t--) {
                $selected = ($t == $tahunDipilih) ? 'selected' : '';
                echo "<option value='$t' $selected>$t</option>";
            }
            ?>
        </select>
    </form>

    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Cetak Laporan
    </button>
</div>

<div class="card-box print-only">
    <h5>Ringkasan Analisis</h5>
    <ul style="margin-bottom:0">
        <?php foreach ($analisis as $a): ?>
            <li><?= $a ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- CARTA -->
<div class="card-box">
    <h5>Jualan Bulanan (<?= $tahunDipilih ?>)</h5>
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

<script>
window.onbeforeprint = () => {
    for (let id in Chart.instances) {
        Chart.instances[id].resize();
    }
};
</script>

<?php include "footer.php"; ?>
</body>
</html>
