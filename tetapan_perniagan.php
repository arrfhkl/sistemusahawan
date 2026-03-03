<?php
include "connection.php";
include "header.php";

if (!isset($_SESSION['usahawan_id'])) {
    die("<div style='padding:20px'>Sila log masuk sebagai usahawan.</div>");
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ===========================
   DATA USAHAWAN
=========================== */
$stmt = $conn->prepare("
    SELECT 
        nama,
        perniagaan,
        alamat,
        telefon,
        email,
        avatar,
        status,
        tarikh_daftar
    FROM usahawan
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

/* ===========================
   KPI
=========================== */

// Jumlah servis
$q_servis = $conn->query("
    SELECT COUNT(*) AS total
    FROM servis
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Jumlah produk
$q_produk = $conn->query("
    SELECT COUNT(*) AS total
    FROM produk
    WHERE usahawan_id = $usahawan_id
")->fetch_assoc()['total'] ?? 0;

// Servis aktif (ada nama)
$q_servis_aktif = $conn->query("
    SELECT COUNT(*) AS total
    FROM servis
    WHERE usahawan_id = $usahawan_id
    AND nama IS NOT NULL
")->fetch_assoc()['total'] ?? 0;

/* ===========================
   STATUS & AVATAR
=========================== */
$status_badge = ($u['status'] === 'aktif')
    ? "<span class='badge badge-hijau'>Aktif</span>"
    : "<span class='badge badge-biru'>Tidak Aktif</span>";

$avatar = !empty($u['avatar'])
    ? $u['avatar']
    : "assets/img/no-image.png";
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tetapan Perniagaan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.icon-blue   { color:#007bff; }
.icon-green  { color:#28a745; }
.icon-orange { color:#fd7e14; }

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

/* PROFIL */
.profile-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
    padding: 30px;
}

.profile-header {
    display: flex;
    gap: 20px;
    align-items: center;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 110px;
    height: 110px;
    border-radius: 16px;
    object-fit: cover;
    border: 1px solid #eee;
}

.profile-info h3 {
    margin: 0;
    font-size: 20px;
}

.profile-info p {
    margin: 4px 0;
    font-size: 14px;
    color: #666;
}

.profile-actions {
    margin-top: 25px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-main {
    background: #003399;
    color: #fff;
    padding: 10px 22px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}

.btn-outline {
    border: 2px solid #003399;
    color: #003399;
    padding: 10px 22px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-hijau { background:#e6f4ea; color:#1e7e34; }
.badge-biru { background:#e7f1ff; color:#004085; }
</style>
</head>

<body>

<div class="container">

<div class="page-header">
    <h2><i class="fas fa-gear"></i> Tetapan Perniagaan</h2>
</div>

<!-- KPI -->
<div class="stats-container">

    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-number"><?= $q_servis ?></div>
        <div class="stat-label">Jumlah Servis</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-number"><?= $q_produk ?></div>
        <div class="stat-label">Jumlah Produk</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="stat-number"><?= $q_servis_aktif ?></div>
        <div class="stat-label">Servis Aktif</div>
    </div>

</div>

<!-- PROFIL PERNIAGAAN -->
<div class="profile-card">

    <div class="profile-header">
        <img src="<?= $avatar ?>" class="profile-avatar">

        <div class="profile-info">
            <h3><?= htmlspecialchars($u['perniagaan']) ?></h3>
            <p><strong><?= htmlspecialchars($u['nama']) ?></strong></p>
            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($u['telefon']) ?></p>
            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></p>
            <?= $status_badge ?>
        </div>
    </div>

    <p>
        <strong>Alamat Perniagaan</strong><br>
        <?= nl2br(htmlspecialchars($u['alamat'])) ?>
    </p>

    <p class="text-muted">
        <small>Daftar pada: <?= date("d/m/Y", strtotime($u['tarikh_daftar'])) ?></small>
    </p>

    <div class="profile-actions">
        <a href="tetapan_perniagaan_edit.php" class="btn-main">
            <i class="fas fa-pen"></i> Kemaskini Profil
        </a>

        <a href="tukar_katalaluan.php" class="btn-outline">
            <i class="fas fa-lock"></i> Tukar Kata Laluan
        </a>
    </div>

</div>

</div>

<?php include "footer.php"; ?>
</body>
</html>
