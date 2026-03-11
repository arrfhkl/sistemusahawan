<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   UPDATE STATUS PESANAN
=========================== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {

    $pesanan_id = (int) $_POST['pesanan_id'];
    $status_baru = $_POST['status_pesanan'];
    $sebab_batal = $_POST['sebab_batal'] ?? null;

    $stmt = $conn->prepare("
        UPDATE pesanan 
        SET status_pesanan = ?,
            sebab_batal = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi",$status_baru,$sebab_batal,$pesanan_id);
    $stmt->execute();

    if ($status_baru==='processing')
        $conn->query("UPDATE pesanan SET tarikh_diproses=NOW() WHERE id=$pesanan_id");
    elseif ($status_baru==='shipped')
        $conn->query("UPDATE pesanan SET tarikh_dihantar=NOW() WHERE id=$pesanan_id");
    elseif ($status_baru==='delivered')
        $conn->query("UPDATE pesanan SET tarikh_selesai=NOW() WHERE id=$pesanan_id");
    elseif ($status_baru==='cancelled')
        $conn->query("UPDATE pesanan SET tarikh_dibatalkan=NOW() WHERE id=$pesanan_id");
}

/* ===========================
   KPI PESANAN (IKUT PRODUK)
=========================== */
$kpi = $conn->query("
SELECT
    COUNT(DISTINCT p.id) AS total,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='pending' THEN p.id END) AS pending,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='processing' THEN p.id END) AS processing,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='shipped' THEN p.id END) AS shipped,
    COUNT(DISTINCT CASE WHEN p.status_pesanan='delivered' THEN p.id END) AS delivered,
    SUM(pi.subtotal) AS nilai_jualan
FROM pesanan p
JOIN pesanan_item pi ON p.id=pi.pesanan_id
JOIN produk pr ON pi.produk_id=pr.id
WHERE pr.usahawan_id=$usahawan_id
")->fetch_assoc();

/* ===========================
   SENARAI PESANAN
=========================== */
$orders = $conn->query("
SELECT p.*
FROM pesanan p
JOIN pesanan_item pi ON p.id=pi.pesanan_id
JOIN produk pr ON pi.produk_id=pr.id
WHERE pr.usahawan_id=$usahawan_id
GROUP BY p.id
ORDER BY p.tarikh_pesanan DESC
");

function badge($status){
    return match($status){
        'pending'=>'warning',
        'processing'=>'info',
        'shipped'=>'primary',
        'delivered'=>'success',
        'cancelled'=>'danger',
        default=>'secondary'
    };
}
?>

<style>
.container{padding:30px;margin-left:260px;background:#f4f6f9}
.kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px}
.card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 8px 18px rgba(0,0,0,.08)}
.order{margin-top:25px}
.badge{padding:6px 14px;border-radius:20px;font-size:12px}
.timeline span{display:block;font-size:13px;color:#555}
.products{background:#f8f9fa;border-radius:10px;padding:15px;margin-top:15px}
.products div{display:flex;justify-content:space-between;padding:6px 0}
.actions select, .actions button{padding:8px;border-radius:8px}
.actions button{background:#007bff;color:#fff;border:none}
.alert{padding:12px;border-radius:10px;margin-bottom:15px}
.alert-warning{background:#fff3cd}
</style>

<div class="container">

<h1>📥 Pesanan Masuk</h1>

<!-- KPI -->
<div class="kpi">
  <div class="card">Jumlah Pesanan<br><strong><?= $kpi['total'] ?></strong></div>
  <div class="card">Menunggu<br><strong><?= $kpi['pending'] ?></strong></div>
  <div class="card">Diproses<br><strong><?= $kpi['processing'] ?></strong></div>
  <div class="card">Dihantar<br><strong><?= $kpi['shipped'] ?></strong></div>
  <div class="card">Nilai Jualan<br><strong>RM <?= number_format($kpi['nilai_jualan'],2) ?></strong></div>
</div>

<?php while($o=$orders->fetch_assoc()): ?>

<?php
$items = $conn->query("
SELECT * FROM pesanan_item pi
JOIN produk pr ON pi.produk_id=pr.id
WHERE pi.pesanan_id={$o['id']} AND pr.usahawan_id=$usahawan_id
");
?>

<div class="card order">

<?php if($o['status_pesanan']=='pending' && strtotime($o['tarikh_pesanan']) < strtotime('-24 hours')): ?>
<div class="alert alert-warning">⚠️ Pesanan ini belum diproses lebih 24 jam</div>
<?php endif; ?>

<h3>#<?= $o['no_pesanan'] ?></h3>
<span class="badge bg-<?= badge($o['status_pesanan']) ?>">
<?= strtoupper($o['status_pesanan']) ?>
</span>

<p><strong><?= $o['nama_pelanggan'] ?></strong> | <?= $o['no_telefon'] ?></p>
<p>📍 <?= $o['alamat'] ?></p>

<p>
🚚 <?= strtoupper($o['cara_hantar']) ?> |
💳 <?= strtoupper($o['cara_bayar']) ?> |
💰 <?= strtoupper($o['status_bayaran']) ?>
</p>

<?php if($o['nota']): ?>
<p><strong>Nota Pelanggan:</strong> <?= nl2br(htmlspecialchars($o['nota'])) ?></p>
<?php endif; ?>

<div class="products">
<strong>Produk Anda:</strong>
<?php while($i=$items->fetch_assoc()): ?>
<div>
<span><?= $i['nama_produk'] ?> (x<?= $i['kuantiti'] ?>)</span>
<span>RM <?= number_format($i['subtotal'],2) ?></span>
</div>
<?php endwhile; ?>
</div>

<div class="timeline">
<span>🕒 Pesanan: <?= $o['tarikh_pesanan'] ?></span>
<?php if($o['tarikh_diproses']): ?><span>⚙️ Diproses: <?= $o['tarikh_diproses'] ?></span><?php endif; ?>
<?php if($o['tarikh_dihantar']): ?><span>🚚 Dihantar: <?= $o['tarikh_dihantar'] ?></span><?php endif; ?>
<?php if($o['tarikh_selesai']): ?><span>✅ Selesai: <?= $o['tarikh_selesai'] ?></span><?php endif; ?>
</div>

<div class="actions">
<form method="post">
<input type="hidden" name="pesanan_id" value="<?= $o['id'] ?>">
<select name="status_pesanan" required>
<option value="">Tukar Status</option>
<option value="processing">Diproses</option>
<option value="shipped">Dihantar</option>
<option value="delivered">Selesai</option>
<option value="cancelled">Batal</option>
</select>
<button name="update_status">Kemaskini</button>
</form>
</div>

</div>
<?php endwhile; ?>

</div>
