<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

function getCount($conn, $sql, $id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

/* ===========================
   DATA DASHBOARD
=========================== */

$servisPending = getCount($conn,
    "SELECT COUNT(*) AS total FROM servis_order 
     WHERE seller_id=? AND status='pending'",
    $usahawan_id
);

$pesananPending = getCount($conn,
    "SELECT COUNT(*) AS total FROM pesanan 
     WHERE usahawan_id=? AND status_pesanan='pending'",
    $usahawan_id
);

$pesananProses = getCount($conn,
    "SELECT COUNT(*) AS total FROM pesanan 
     WHERE usahawan_id=? 
     AND status_pesanan IN ('diproses','dihantar')",
    $usahawan_id
);

$jualan = getCount($conn,
    "SELECT IFNULL(SUM(jumlah_bayaran),0) AS total 
     FROM pesanan 
     WHERE usahawan_id=? 
     AND status_bayaran='paid'
     AND MONTH(tarikh_pesanan)=MONTH(CURRENT_DATE())
     AND YEAR(tarikh_pesanan)=YEAR(CURRENT_DATE())",
    $usahawan_id
);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Seller</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
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

.page-header {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    text-align: center;
}

.page-header h2 {
    color: #003399;
    margin: 0;
    font-weight: 700;
}

/* KPI */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

/* ACTION */
.action-card {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-main {
    background:#003399;
    color:#fff;
    padding:10px 22px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

.btn-warning-custom {
    background:#fd7e14;
    color:#fff;
    padding:10px 22px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

/* JUALAN */
.sales-card {
    background: linear-gradient(135deg,#28a745,#1e7e34);
    color:#fff;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.sales-amount {
    font-size: 2.2rem;
    font-weight: 700;
}
</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Dashboard Seller</h2>
    <p class="text-muted mt-2 mb-0">
        Fokus hari ini:
        <strong><?= $servisPending ?></strong> servis &
        <strong><?= $pesananPending ?></strong> pesanan perlu tindakan
    </p>
</div>

<!-- KPI -->
<div class="stats-container">

    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-number"><?= $servisPending ?></div>
        <div class="stat-label">Servis Pending</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-number"><?= $pesananPending ?></div>
        <div class="stat-label">Pesanan Pending</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="fas fa-truck-fast"></i>
        </div>
        <div class="stat-number"><?= $pesananProses ?></div>
        <div class="stat-label">Dalam Proses</div>
    </div>

</div>

<!-- TINDAKAN SEGERA -->
<div class="action-card">
    <h5 class="fw-bold mb-3">⚠️ Tindakan Segera</h5>

    <div class="action-buttons">
        <?php if ($servisPending > 0): ?>
            <a href="servis_order.php?status=pending" class="btn-warning-custom">
                🔧 Selesaikan <?= $servisPending ?> Servis
            </a>
        <?php endif; ?>

        <?php if ($pesananPending > 0): ?>
            <a href="pesanan_masuk.php?status=pending" class="btn-main">
                📦 Proses <?= $pesananPending ?> Pesanan
            </a>
        <?php endif; ?>

        <?php if ($servisPending == 0 && $pesananPending == 0): ?>
            <span class="text-success fw-semibold">
                ✅ Semua urusan selesai hari ini
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- JUALAN -->
<div class="sales-card">
    <h6 class="opacity-75">Jualan Bulan Ini</h6>
    <div class="sales-amount">
        RM <?= number_format($jualan, 2) ?>
    </div>
    <small class="opacity-75">
        Berdasarkan transaksi berjaya
    </small>
</div>

</div>

<?php include "footer.php"; ?>
</body>
</html>
