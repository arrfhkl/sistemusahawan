<?php
include 'connection.php';
include 'header.php';

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$susun = $_GET['susun'] ?? 'terbaru';

$sql = "
SELECT sb.*, s.nama AS nama_servis
FROM servis_booking sb
JOIN servis s ON sb.service_id = s.id
WHERE sb.usahawan_id = ?
";

$params = [$usahawan_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND (sb.nama_pelanggan LIKE ? OR s.nama LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($status !== '') {
    $sql .= " AND sb.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sqlCount = $sql;

$order = $susun === 'terlama' ? 'ASC' : 'DESC';
$sql .= " ORDER BY sb.tarikh $order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* COUNT */
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param(substr($types,0,-2), ...array_slice($params,0,-2));
$stmtCount->execute();
$totalRows = $stmtCount->get_result()->num_rows;
$totalPages = ceil($totalRows / $limit);

/* KPI */
$total = $conn->query("SELECT COUNT(*) t FROM servis_booking WHERE usahawan_id=$usahawan_id")->fetch_assoc()['t'];
$pending = $conn->query("SELECT COUNT(*) t FROM servis_booking WHERE usahawan_id=$usahawan_id AND status='pending'")->fetch_assoc()['t'];
$approved = $conn->query("SELECT COUNT(*) t FROM servis_booking WHERE usahawan_id=$usahawan_id AND status='approved'")->fetch_assoc()['t'];
$completed = $conn->query("SELECT COUNT(*) t FROM servis_booking WHERE usahawan_id=$usahawan_id AND status='completed'")->fetch_assoc()['t'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Tempahan Servis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary-dark:#0f2a44;
}

.container {
    max-width:1280px;
    margin:0 auto;
    padding:30px 40px;
    padding-top:120px;
}

.page-header {
    background:#fff;
    border-radius:15px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    margin-bottom:30px;
    text-align:center;
}

.page-header h2 {
    color:#003399;
    font-weight:700;
}

.stats-container {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.stat-card {
    background:#fff;
    border-radius:15px;
    padding:25px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.stat-number {
    font-size:2rem;
    font-weight:700;
    color:#003399;
}

.stat-label {
    font-size:.9rem;
    color:#666;
}

.filters-section {
    background:#fff;
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    margin-bottom:30px;
}

.filters-row {
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.filter-group {
    flex:1;
    min-width:220px;
}

.filter-group label {
    font-weight:600;
    color:#003399;
}

.filter-group input,
.filter-group select {
    width:100%;
    padding:10px;
    border:2px solid #e9ecef;
    border-radius:8px;
}

.filter-btn {
    background:#003399;
    color:#fff;
    padding:10px 25px;
    border-radius:8px;
    font-weight:600;
    border:none;
}

.table-card {
    background:#fff;
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.status {
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.pending { background:#fff3cd; color:#856404; }
.approved { background:#e6f4ea; color:#1e7e34; }
.completed { background:#e2e3e5; color:#383d41; }
.rejected { background:#fdecea; color:#b02a37; }

.btn-action {
    padding:6px 12px;
    border-radius:6px;
    font-size:13px;
    text-decoration:none;
    font-weight:600;
}

.btn-view { background:#007bff; color:white; }

</style>
</head>

<body>

<div class="container">

    <div class="page-header">
        <h2><i class="fas fa-calendar-check"></i> Senarai Tempahan Servis</h2>
    </div>

    <!-- KPI -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Jumlah Tempahan</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $pending ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $approved ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $completed ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <div class="filter-group">
        <label><i class="fas fa-sort"></i> Susunan Tarikh</label>
        <select name="susun">
            <option value="terbaru" <?= $susun=='terbaru'?'selected':'' ?>>Terbaru</option>
            <option value="terlama" <?= $susun=='terlama'?'selected':'' ?>>Terlama</option>
        </select>
    </div>

<!-- FILTER -->
<div class="filters-section">
    <form method="GET">
        <div class="filters-row">

            <div class="filter-group">
                <label><i class="fas fa-search"></i> Cari Tempahan</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama pelanggan / servis">
            </div>

            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Status</label>
                <select name="status">
                    <option value="">Semua</option>
                    <option value="pending"   <?= $status=='pending'   ?'selected':'' ?>>Pending</option>
                    <option value="approved"  <?= $status=='approved'  ?'selected':'' ?>>Approved</option>
                    <option value="completed" <?= $status=='completed' ?'selected':'' ?>>Completed</option>
                    <option value="rejected"  <?= $status=='rejected'  ?'selected':'' ?>>Rejected</option>
                </select>
            </div>

            <!-- ✅ MOVED INSIDE FORM -->
            <div class="filter-group">
                <label><i class="fas fa-sort"></i> Susunan Tarikh</label>
                <select name="susun">
                    <option value="terbaru" <?= $susun=='terbaru'?'selected':'' ?>>Terbaru</option>
                    <option value="terlama" <?= $susun=='terlama'?'selected':'' ?>>Terlama</option>
                </select>
            </div>

            <button class="filter-btn">
                <i class="fas fa-search"></i> Tapis
            </button>

        </div>
    </form>
</div>

    <div class="table-card">
        <table class="table align-middle table-hover">
        <thead>
        <tr>
        <th>Servis</th>
        <th>Pelanggan</th>
        <th>Tarikh</th>
        <th>Status</th>
        <th>Tindakan</th>
        </tr>
        </thead>
        <tbody id="bookingTable">

        <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
        <td>
        <i class="fas fa-screwdriver-wrench text-primary me-2"></i>
        <?= htmlspecialchars($row['nama_servis']) ?>
        </td>
        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
        <td><?= $row['tarikh'] ?></td>
        <td>
        <span class="badge rounded-pill bg-<?=
        $row['status']=='pending'?'warning':
        ($row['status']=='approved'?'success':
        ($row['status']=='completed'?'secondary':'danger'))
        ?>">
        <?= strtoupper($row['status']) ?>
        </span>
        </td>
        <td>
        <a href="booking_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Lihat</a>
        </tr>
        <?php endwhile; ?>
        <?php else: ?>
        <tr><td colspan="5" class="text-center">Tiada tempahan.</td></tr>
        <?php endif; ?>

        </tbody>
        </table>

        <!-- Pagination -->
        <nav>
        <ul class="pagination justify-content-center">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
        <li class="page-item <?= $i==$page?'active':'' ?>">
        <a class="page-link"
        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&susun=<?= urlencode($susun) ?>">
        <?= $i ?>
        </a>
        </li>
        <?php endfor; ?>
        </ul>
        </nav>

        </div>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput  = document.querySelector("input[name='search']");
const statusSelect = document.querySelector("select[name='status']");  // ✅ was missing
const susunSelect  = document.querySelector("select[name='susun']");

function loadBooking() {
    const keyword = searchInput.value;
    const statusVal = statusSelect.value;   // ✅ use statusVal, not status
    const susun   = susunSelect.value;

    fetch(`?search=${encodeURIComponent(keyword)}&status=${encodeURIComponent(statusVal)}&susun=${encodeURIComponent(susun)}`)
    .then(res => res.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        document.getElementById('bookingTable').innerHTML =
            doc.querySelector('#bookingTable').innerHTML;
    });
}

searchInput.addEventListener('keyup', loadBooking);
statusSelect.addEventListener('change', loadBooking);
susunSelect.addEventListener('change', loadBooking);
</script>

<?php include 'footer.php'; ?>

</body>
</html>


<?php
$stmt->close();
$conn->close();
?>