<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

// Dapatkan IC usahawan
$usahawan = $conn->query("SELECT ic, nama FROM usahawan WHERE id = $usahawan_id")->fetch_assoc();
$ic = $usahawan['ic'] ?? '';

/* ===========================
   KPI / RINGKASAN PERMOHONAN
=========================== */

$kpi_agro    = $conn->query("SELECT COUNT(*) AS c FROM permohonan_agro WHERE ic='$ic'")->fetch_assoc()['c'] ?? 0;
$kpi_ipush   = $conn->query("SELECT COUNT(*) AS c FROM permohonan_ipush WHERE ic='$ic'")->fetch_assoc()['c'] ?? 0;
$kpi_itekad  = $conn->query("SELECT COUNT(*) AS c FROM permohonan_itekad WHERE ic='$ic'")->fetch_assoc()['c'] ?? 0;
$kpi_total   = $kpi_agro + $kpi_ipush + $kpi_itekad;

$kpi_lulus   = $conn->query("SELECT COUNT(*) AS c FROM permohonan_agro WHERE ic='$ic' AND status='Lulus'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_ipush WHERE ic='$ic' AND status='Lulus'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_itekad WHERE ic='$ic' AND status='Lulus'")->fetch_assoc()['c'];

$kpi_pending = $conn->query("SELECT COUNT(*) AS c FROM permohonan_agro WHERE ic='$ic' AND status='Menunggu'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_ipush WHERE ic='$ic' AND status='Menunggu'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_itekad WHERE ic='$ic' AND status='Menunggu'")->fetch_assoc()['c'];

$kpi_tolak   = $conn->query("SELECT COUNT(*) AS c FROM permohonan_agro WHERE ic='$ic' AND status='Tolak'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_ipush WHERE ic='$ic' AND status='Tolak'")->fetch_assoc()['c']
             + $conn->query("SELECT COUNT(*) AS c FROM permohonan_itekad WHERE ic='$ic' AND status='Tolak'")->fetch_assoc()['c'];

/* ===========================
   FILTER
=========================== */
$filter_program = $_GET['program'] ?? '';
$filter_status  = $_GET['status'] ?? '';
$search         = $_GET['search'] ?? '';

/* ===========================
   QUERY GABUNGAN
=========================== */
function buildWhere($ic, $filter_status, $search) {
    $where = "WHERE ic='$ic'";
    if ($filter_status !== '') $where .= " AND status='$filter_status'";
    if ($search !== '') $where .= " AND (nama LIKE '%$search%' OR kategori LIKE '%$search%' OR tujuan LIKE '%$search%')";
    return $where;
}

$sql = "";
$parts = [];

if ($filter_program === '' || $filter_program === 'Agro') {
    $w = buildWhere($ic, $filter_status, $search);
    $parts[] = "SELECT 'Agro' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status FROM permohonan_agro $w";
}
if ($filter_program === '' || $filter_program === 'iPush') {
    $w = buildWhere($ic, $filter_status, $search);
    $parts[] = "SELECT 'iPush' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status FROM permohonan_ipush $w";
}
if ($filter_program === '' || $filter_program === 'iTekad') {
    $w = buildWhere($ic, $filter_status, $search);
    $parts[] = "SELECT 'iTekad' AS program, id, nama, ic, telefon, alamat, kategori, jumlah, tujuan, dokumen, tarikh_permohonan, status FROM permohonan_itekad $w";
}

$sql = implode(" UNION ", $parts) . " ORDER BY tarikh_permohonan DESC";
$permohonan = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senarai Permohonan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
    background-attachment: fixed;
    min-height: 100vh;
}

body::after {
    content: "";
    position: fixed;
    inset: 0;
    background-image: url("assets/img/jatapahang.png");
    background-repeat: repeat;
    background-size: 180px 180px;
    opacity: 0.07;
    z-index: -1;
}

.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 30px 40px;
    padding-top: 120px;
    min-height: 100vh;
}

/* ===== PAGE HEADER ===== */
.page-header {
    background: #fff;
    border-radius: 18px;
    padding: 28px 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    border-left: 6px solid #003399;
}

.page-header h2 {
    color: #003399;
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0;
}

.page-header p {
    color: #666;
    font-size: 0.9rem;
    margin: 4px 0 0;
}

.btn-back {
    background: #003399;
    color: #fff;
    padding: 10px 22px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.btn-back:hover {
    background: #001f6e;
    color: #fff;
    transform: translateY(-2px);
}

/* ===== KPI CARDS ===== */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 22px 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    text-align: center;
    transition: 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
}

.stat-card.blue::before   { background: #003399; }
.stat-card.green::before  { background: #28a745; }
.stat-card.orange::before { background: #fd7e14; }
.stat-card.red::before    { background: #dc3545; }

.stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

.stat-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.stat-icon.blue   { color: #003399; }
.stat-icon.green  { color: #28a745; }
.stat-icon.orange { color: #fd7e14; }
.stat-icon.red    { color: #dc3545; }

.stat-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: #777;
    margin-top: 5px;
    font-weight: 500;
}

/* ===== TABLE SECTION ===== */
.table-section {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-section-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.table-section-header h5 {
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
    font-size: 1rem;
}

.record-count {
    background: #e8eeff;
    color: #003399;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
    padding: 0 4px;
}

.permohonan-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

.permohonan-table thead th {
    background: #f8f9ff;
    color: #003399;
    font-weight: 700;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 14px 18px;
    border-bottom: 2px solid #e8eeff;
    white-space: nowrap;
}

.permohonan-table tbody td {
    padding: 14px 18px;
    font-size: 0.9rem;
    color: #444;
    border-bottom: 1px solid #f5f5f5;
    vertical-align: middle;
}

.permohonan-table tbody tr:hover td {
    background: #f8f9ff;
}

.permohonan-table tbody tr:last-child td {
    border-bottom: none;
}

/* ===== BADGE PROGRAM ===== */
.badge-program {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-block;
    white-space: nowrap;
}

.badge-agro   { background: #e6f7ee; color: #1b7a40; }
.badge-ipush  { background: #e7f0ff; color: #0047cc; }
.badge-itekad { background: #fff3e8; color: #c05a00; }

/* ===== BADGE STATUS ===== */
.badge-status {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-block;
    white-space: nowrap;
}

.status-lulus   { background: #e6f7ee; color: #1b7a40; }
.status-tolak   { background: #fde8e8; color: #c0392b; }
.status-menunggu { background: #fff8e1; color: #b77c00; }
.status-default { background: #eee; color: #555; }

/* ===== JUMLAH ===== */
.jumlah-cell {
    font-weight: 700;
    color: #003399;
}

/* ===== DOKUMEN LINK ===== */
.dokumen-link {
    color: #003399;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    background: #e8eeff;
    transition: 0.2s;
}

.dokumen-link:hover {
    background: #003399;
    color: #fff;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #999;
}

.empty-state i {
    font-size: 3.5rem;
    color: #ccc;
    display: block;
    margin-bottom: 15px;
}

.empty-state p {
    font-size: 1rem;
    color: #aaa;
}

/* ===== RESPONSIVE CARD VIEW (mobile) ===== */
@media (max-width: 768px) {
    .container { padding: 15px; padding-top: 100px; }

    .permohonan-table, 
    .permohonan-table thead,
    .permohonan-table tbody,
    .permohonan-table th,
    .permohonan-table td,
    .permohonan-table tr {
        display: block;
        width: 100%;
    }

    .permohonan-table thead { display: none; }

    .permohonan-table tbody tr {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        padding: 14px;
        margin-bottom: 14px;
        border-left: 5px solid #003399;
    }

    .permohonan-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 4px;
        font-size: 0.88rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .permohonan-table tbody td:last-child { border-bottom: none; }

    .permohonan-table tbody td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #003399;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        flex: 0 0 40%;
    }

    .table-wrapper { overflow-x: visible; padding: 16px; }
}

@media (max-width: 480px) {
    .page-header { flex-direction: column; align-items: flex-start; }
    .stats-container { grid-template-columns: repeat(2, 1fr); }
    .filters-row { flex-direction: column; }
    .filter-group { min-width: 100%; }
}
</style>
</head>

<body>

<div class="container">

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <div>
            <h2><i class="fas fa-file-alt me-2"></i> Senarai Permohonan</h2>
            <p>Rekod permohonan program bantuan atas nama: <strong><?= htmlspecialchars($usahawan['nama'] ?? '') ?></strong></p>
        </div>
    </div>

    <!-- ===== KPI CARDS ===== -->
    <div class="stats-container">
        <div class="stat-card blue">
            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div class="stat-number"><?= $kpi_total ?></div>
            <div class="stat-label">Jumlah Permohonan</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
            <div class="stat-number"><?= $kpi_lulus ?></div>
            <div class="stat-label">Diluluskan</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-number"><?= $kpi_pending ?></div>
            <div class="stat-label">Dalam Proses</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?= $kpi_tolak ?></div>
            <div class="stat-label">Ditolak</div>
        </div>
    </div>

    <!-- ===== TABLE SECTION ===== -->
    <div class="table-section">
        <div class="table-section-header">
            <h5><i class="fas fa-list-ul me-2"></i> Rekod Permohonan</h5>
            <span class="record-count"><?= $permohonan->num_rows ?> rekod</span>
        </div>

        <div class="table-wrapper">
            <?php if ($permohonan->num_rows > 0): ?>
            <table class="permohonan-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Program</th>
                        <th>Kategori</th>
                        <th>Jumlah (RM)</th>
                        <th>Tujuan</th>
                        <th>Tarikh Permohonan</th>
                        <th>Dokumen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $bil = 1; while ($row = $permohonan->fetch_assoc()): ?>
                    <?php
                        $program_lower = strtolower(str_replace(' ', '', $row['program']));
                        $badge_class = 'badge-' . $program_lower;

                        $status = strtolower(trim($row['status']));
                        if ($status === 'lulus')    $sc = 'status-lulus';
                        elseif ($status === 'tolak') $sc = 'status-tolak';
                        elseif ($status === 'menunggu') $sc = 'status-menunggu';
                        else $sc = 'status-default';
                    ?>
                    <tr>
                        <td data-label="Bil"><?= $bil++ ?></td>
                        <td data-label="Program">
                            <span class="badge-program <?= $badge_class ?>">
                                <?= htmlspecialchars($row['program']) ?>
                            </span>
                        </td>
                        <td data-label="Kategori"><?= htmlspecialchars($row['kategori']) ?></td>
                        <td data-label="Jumlah (RM)" class="jumlah-cell">
                            RM <?= number_format($row['jumlah'], 2) ?>
                        </td>
                        <td data-label="Tujuan">
                            <?= htmlspecialchars(mb_strimwidth($row['tujuan'], 0, 60, '...')) ?>
                        </td>
                        <td data-label="Tarikh Permohonan">
                            <?= date('d M Y, H:i', strtotime($row['tarikh_permohonan'])) ?>
                        </td>
                        <td data-label="Dokumen">
                            <?php if (!empty($row['dokumen'])): ?>
                                <a href="uploads/<?= htmlspecialchars($row['dokumen']) ?>" 
                                   target="_blank" class="dokumen-link">
                                    <i class="fas fa-file-pdf"></i> Lihat
                                </a>
                            <?php else: ?>
                                <span style="color:#bbb; font-size:0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <span class="badge-status <?= $sc ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>Tiada rekod permohonan dijumpai.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>

</body>
</html>