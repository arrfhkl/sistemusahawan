<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   KPI / RINGKASAN PRODUK
=========================== */

// Jumlah produk
$q_total = $conn->query("
    SELECT COUNT(*) AS total 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Produk aktif
$q_aktif = $conn->query("
    SELECT COUNT(*) AS aktif 
    FROM produk 
    WHERE usahawan_id = $usahawan_id 
    AND stok > 0
")->fetch_assoc()['aktif'] ?? 0;

// Produk habis stok
$q_habis = $conn->query("
    SELECT COUNT(*) AS habis 
    FROM produk 
    WHERE usahawan_id = $usahawan_id 
    AND stok = 0
")->fetch_assoc()['habis'] ?? 0;

// Nilai inventori (harga × stok)
$q_nilai = $conn->query("
    SELECT IFNULL(SUM(harga * stok),0) AS nilai 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['nilai'] ?? 0;

// Bilangan kategori digunakan
$q_kategori = $conn->query("
    SELECT COUNT(DISTINCT kategori_id) AS kategori 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['kategori'] ?? 0;

/* ===========================
   SENARAI PRODUK
=========================== */
$produk = $conn->query("
    SELECT * 
    FROM produk 
    WHERE usahawan_id = $usahawan_id
    ORDER BY id DESC
");
?>

<style>
/* ===========================
   LAYOUT ASAS
=========================== */
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

/* ===========================
   KPI CARDS
=========================== */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    transition: 0.3s;
}

.kpi-card:hover {
    transform: translateY(-4px);
}

.kpi-title {
    font-size: 13px;
    color: #777;
}

.kpi-value {
    font-size: 26px;
    font-weight: 700;
    margin-top: 5px;
}

.blue { color:#007bff; }
.green { color:#28a745; }
.red { color:#dc3545; }
.orange { color:#fd7e14; }

/* ===========================
   TABLE PRODUK
=========================== */
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
    text-align: left;
    vertical-align: middle;
}

th {
    background: #f8f9fb;
    font-size: 14px;
}

tr:hover {
    background: #f9fbff;
}

.product-img {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-green { background:#e6f4ea; color:#1e7e34; }
.badge-red { background:#fdecea; color:#b02a37; }
.badge-orange { background:#fff3cd; color:#856404; }

.action a {
    margin-right: 8px;
    text-decoration: none;
    font-weight: 600;
    color: #007bff;
}

.empty {
    padding: 40px;
    text-align: center;
    color: #777;
}
</style>

<div class="container">

<h1>📦 Senarai Produk Perniagaan</h1>

<!-- ===========================
     KPI SECTION
=========================== -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Jumlah Produk</div>
        <div class="kpi-value blue"><?= $q_total ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Produk Aktif</div>
        <div class="kpi-value green"><?= $q_aktif ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Produk Habis Stok</div>
        <div class="kpi-value red"><?= $q_habis ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Nilai Inventori (RM)</div>
        <div class="kpi-value blue"><?= number_format($q_nilai,2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Kategori Digunakan</div>
        <div class="kpi-value orange"><?= $q_kategori ?></div>
    </div>
</div>

<!-- ===========================
     TABLE PRODUK
=========================== -->
<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>Produk</th>
    <th>Nama & Deskripsi</th>
    <th>Harga (RM)</th>
    <th>Stok</th>
    <th>Lokasi</th>
    <th>Tindakan</th>
</tr>
</thead>
<tbody>

<?php if ($produk->num_rows > 0): ?>
<?php while($p = $produk->fetch_assoc()): 

    if ($p['stok'] == 0) {
        $status = "<span class='badge badge-red'>Habis Stok</span>";
    } elseif ($p['stok'] <= 5) {
        $status = "<span class='badge badge-orange'>Stok Rendah</span>";
    } else {
        $status = "<span class='badge badge-green'>Aktif</span>";
    }

    $img = !empty($p['gambar_url'])
        ? "uploads/" . $p['gambar_url']
        : "assets/img/no-image.png";
?>

<tr>
    <td><img src="<?= $img ?>" class="product-img"></td>
    <td>
        <strong><?= htmlspecialchars($p['nama']) ?></strong><br>
        <small><?= substr(strip_tags($p['deskripsi']),0,80) ?>...</small>
    </td>
    <td><?= number_format($p['harga'],2) ?></td>
    <td><?= $status ?> (<?= $p['stok'] ?>)</td>
    <td><?= htmlspecialchars($p['lokasi']) ?></td>
    <td class="action">
        <a href="produk_view.php?id=<?= $p['id'] ?>">Lihat</a>
        <a href="produk_edit.php?id=<?= $p['id'] ?>">Edit</a>
        <a href="produk_delete.php?id=<?= $p['id'] ?>" 
           onclick="return confirm('Padam produk ini?')">Padam</a>
    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="empty">
        Tiada produk didaftarkan.  
        <br><br>
        <a href="produk_tambah.php">➕ Tambah Produk Pertama</a>
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>

<?php include "footer.php"; ?>
