<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   KPI / RINGKASAN SERVIS
=========================== */

// Jumlah servis
$q_total = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Servis aktif (anggap ada nama & deskripsi)
$q_aktif = $conn->query("
    SELECT COUNT(*) AS aktif 
    FROM servis 
    WHERE usahawan_id = $usahawan_id
    AND nama IS NOT NULL
")->fetch_assoc()['aktif'] ?? 0;

// Jumlah booking
$q_booking = $conn->query("
    SELECT COUNT(*) AS total 
    FROM servis_booking 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Booking pending
$q_pending = $conn->query("
    SELECT COUNT(*) AS pending 
    FROM servis_booking 
    WHERE usahawan_id = $usahawan_id
    AND status = 'pending'
")->fetch_assoc()['pending'] ?? 0;

// Bilangan kategori servis digunakan
$q_kategori = $conn->query("
    SELECT COUNT(DISTINCT kategori_servis_id) AS kategori 
    FROM servis 
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['kategori'] ?? 0;

/* ===========================
   SENARAI SERVIS
=========================== */
$servis = $conn->query("
    SELECT 
        s.*,
        ks.nama AS kategori_nama,
        (
            SELECT COUNT(*) 
            FROM servis_booking sb 
            WHERE sb.service_id = s.id
        ) AS jumlah_booking
    FROM servis s
    LEFT JOIN kategori_servis ks ON ks.id = s.kategori_servis_id
    WHERE s.usahawan_id = $usahawan_id
    ORDER BY s.id DESC
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
.orange { color:#fd7e14; }
.red { color:#dc3545; }

/* Table */
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
    vertical-align: middle;
}

th {
    background: #f8f9fb;
    font-size: 14px;
}

tr:hover {
    background: #f9fbff;
}

.service-img {
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
}

.badge-green { background:#e6f4ea; color:#1e7e34; }
.badge-orange { background:#fff3cd; color:#856404; }
.badge-blue { background:#e7f1ff; color:#004085; }

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

<h1>🛠️ Senarai Servis Perniagaan</h1>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Jumlah Servis</div>
        <div class="kpi-value blue"><?= $q_total ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Servis Aktif</div>
        <div class="kpi-value green"><?= $q_aktif ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Jumlah Booking</div>
        <div class="kpi-value blue"><?= $q_booking ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Booking Pending</div>
        <div class="kpi-value orange"><?= $q_pending ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Kategori Servis</div>
        <div class="kpi-value blue"><?= $q_kategori ?></div>
    </div>
</div>

<!-- TABLE -->
<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>Servis</th>
    <th>Nama & Deskripsi</th>
    <th>Kategori</th>
    <th>Lokasi</th>
    <th>Booking</th>
    <th>Tindakan</th>
</tr>
</thead>
<tbody>

<?php if ($servis->num_rows > 0): ?>
<?php while($s = $servis->fetch_assoc()):

    $img = !empty($s['gambar_servis_url'])
        ? "uploads/" . $s['gambar_servis_url']
        : "assets/img/no-image.png";

    $badge = $s['jumlah_booking'] > 0
        ? "<span class='badge badge-green'>Aktif</span>"
        : "<span class='badge badge-blue'>Belum Dibooking</span>";
?>

<tr>
    <td><img src="<?= $img ?>" class="service-img"></td>
    <td>
        <strong><?= htmlspecialchars($s['nama']) ?></strong><br>
        <small><?= substr(strip_tags($s['deskripsi']),0,80) ?>...</small>
    </td>
    <td><?= htmlspecialchars($s['kategori_nama'] ?? '-') ?></td>
    <td><?= htmlspecialchars($s['lokasi']) ?></td>
    <td><?= $badge ?> (<?= $s['jumlah_booking'] ?>)</td>
    <td class="action">
        <a href="servis_view.php?id=<?= $s['id'] ?>">Lihat</a>
        <a href="servis_edit.php?id=<?= $s['id'] ?>">Edit</a>
        <a href="servis_delete.php?id=<?= $s['id'] ?>"
           onclick="return confirm('Padam servis ini?')">Padam</a>
    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="empty">
        Tiada servis didaftarkan.<br><br>
        <a href="servis_tambah.php">➕ Tambah Servis Pertama</a>
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>

<?php include "footer.php"; ?>
