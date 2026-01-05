<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   KEMASKINI STATUS PESANAN
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $pesanan_id = (int) $_POST['pesanan_id'];
    $status_baru = $_POST['status_pesanan'];

    $stmt = $conn->prepare("
        UPDATE pesanan 
        SET status_pesanan = ? 
        WHERE id = ? AND usahawan_id = ?
    ");
    $stmt->bind_param("sii", $status_baru, $pesanan_id, $usahawan_id);
    $stmt->execute();

    // Tarikh automatik ikut status
    if ($status_baru === 'processing') {
        $conn->query("UPDATE pesanan SET tarikh_diproses = NOW() WHERE id = $pesanan_id");
    } elseif ($status_baru === 'shipped') {
        $conn->query("UPDATE pesanan SET tarikh_dihantar = NOW() WHERE id = $pesanan_id");
    } elseif ($status_baru === 'delivered') {
        $conn->query("UPDATE pesanan SET tarikh_selesai = NOW() WHERE id = $pesanan_id");
    }
}

/* ===========================
   KPI PESANAN
=========================== */
$kpi = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status_pesanan='pending') AS pending,
        SUM(status_pesanan='processing') AS processing,
        SUM(status_pesanan='shipped') AS shipped,
        SUM(status_pesanan='delivered') AS delivered
    FROM pesanan
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc();

/* ===========================
   SENARAI PESANAN
=========================== */
$pesanan = $conn->query("
    SELECT 
        p.*,
        COUNT(pi.id) AS jumlah_item,
        SUM(pi.subtotal) AS jumlah_produk
    FROM pesanan p
    LEFT JOIN pesanan_item pi ON p.id = pi.pesanan_id
    WHERE p.usahawan_id = $usahawan_id
    GROUP BY p.id
    ORDER BY p.tarikh_pesanan DESC
");
?>

<style>
.container {
    padding: 30px;
    margin-left: 260px;
    background: #f4f6f9;
    min-height: 100vh;
}

h1 {
    font-size: 26px;
    margin-bottom: 20px;
}

/* KPI */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}

.kpi-title {
    font-size: 13px;
    color: #777;
}

.kpi-value {
    font-size: 26px;
    font-weight: 700;
}

.blue { color:#007bff; }
.orange { color:#fd7e14; }
.green { color:#28a745; }
.red { color:#dc3545; }

/* TABLE */
.table-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
}

th {
    background: #f8f9fb;
    font-size: 14px;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-pending { background:#fff3cd; color:#856404; }
.badge-processing { background:#e7f1ff; color:#004085; }
.badge-shipped { background:#d1ecf1; color:#0c5460; }
.badge-delivered { background:#e6f4ea; color:#1e7e34; }

.action select {
    padding: 6px;
    border-radius: 6px;
}

.action button {
    padding: 6px 12px;
    border-radius: 20px;
    border: none;
    background: #007bff;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}
</style>

<div class="container">

<h1>📥 Pesanan Masuk</h1>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Jumlah Pesanan</div>
        <div class="kpi-value blue"><?= $kpi['total'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Menunggu</div>
        <div class="kpi-value orange"><?= $kpi['pending'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Diproses</div>
        <div class="kpi-value blue"><?= $kpi['processing'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Dihantar</div>
        <div class="kpi-value blue"><?= $kpi['shipped'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Selesai</div>
        <div class="kpi-value green"><?= $kpi['delivered'] ?></div>
    </div>
</div>

<!-- TABLE -->
<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>No Pesanan</th>
    <th>Tarikh</th>
    <th>Pelanggan</th>
    <th>Item</th>
    <th>Jumlah (RM)</th>
    <th>Status</th>
    <th>Tindakan</th>
</tr>
</thead>
<tbody>

<?php if ($pesanan->num_rows > 0): ?>
<?php while($o = $pesanan->fetch_assoc()): 

    $badge = "badge-pending";
    if ($o['status_pesanan'] === 'processing') $badge = "badge-processing";
    elseif ($o['status_pesanan'] === 'shipped') $badge = "badge-shipped";
    elseif ($o['status_pesanan'] === 'delivered') $badge = "badge-delivered";
?>

<tr>
    <td><strong><?= htmlspecialchars($o['no_pesanan']) ?></strong></td>
    <td><?= date('d/m/Y H:i', strtotime($o['tarikh_pesanan'])) ?></td>
    <td><?= htmlspecialchars($o['nama_pelanggan']) ?></td>
    <td><?= $o['jumlah_item'] ?></td>
    <td><?= number_format($o['jumlah_produk'],2) ?></td>
    <td>
        <span class="badge <?= $badge ?>">
            <?= ucfirst($o['status_pesanan']) ?>
        </span>
    </td>
    <td class="action">
        <form method="post">
            <input type="hidden" name="pesanan_id" value="<?= $o['id'] ?>">
            <select name="status_pesanan" required>
                <option value="">Tukar</option>
                <option value="processing">Diproses</option>
                <option value="shipped">Dihantar</option>
                <option value="delivered">Selesai</option>
            </select>
            <button type="submit" name="update_status">✔</button>
        </form>
    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="7" style="padding:40px;text-align:center;color:#777">
        Tiada pesanan diterima buat masa ini.
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
