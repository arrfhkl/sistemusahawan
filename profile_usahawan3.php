<?php
include("connection.php");
include "header.php";

// Semak ID usahawan dalam URL
if (!isset($_GET['id'])) {
    die("Profil tidak ditemui.");
}

$profil_id = (int)$_GET['id'];

// Ambil data usahawan — pastikan bukan Pengguna
$stmt = $conn->prepare("
    SELECT * FROM usahawan
    WHERE id = ?
    AND jenis != 'Pengguna'
");
$stmt->bind_param("i", $profil_id);
$stmt->execute();
$usahawan = $stmt->get_result()->fetch_assoc();

if (!$usahawan) {
    die("Profil usahawan tidak ditemui.");
}

// Avatar
$avatar = $usahawan['avatar'];
if (!empty($avatar)) {
    if (strpos($avatar, 'uploads/') === false) {
        $avatar = 'uploads/' . $avatar;
    }
    if (!file_exists($avatar)) {
        $avatar = 'assets/img/default_avatar.jpg';
    }
} else {
    $avatar = 'assets/img/default_avatar.jpg';
}

// Kiraan produk
$stmt_kira = $conn->prepare("SELECT COUNT(*) as total FROM produk WHERE usahawan_id = ?");
$stmt_kira->bind_param("i", $profil_id);
$stmt_kira->execute();
$kira_produk = $stmt_kira->get_result()->fetch_assoc()['total'];

// Kiraan servis
$stmt_kira2 = $conn->prepare("SELECT COUNT(*) as total FROM servis WHERE usahawan_id = ?");
$stmt_kira2->bind_param("i", $profil_id);
$stmt_kira2->execute();
$kira_servis = $stmt_kira2->get_result()->fetch_assoc()['total'];

// Ambil semua produk
$stmt_produk = $conn->prepare("
    SELECT p.*, k.nama AS nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    WHERE p.usahawan_id = ?
    ORDER BY p.id DESC
");
$stmt_produk->bind_param("i", $profil_id);
$stmt_produk->execute();
$senarai_produk = $stmt_produk->get_result();

// Ambil semua servis
$stmt_servis = $conn->prepare("
    SELECT s.*, ks.nama AS nama_kategori_servis
    FROM servis s
    LEFT JOIN kategori_servis ks ON s.kategori_servis_id = ks.id
    WHERE s.usahawan_id = ?
    ORDER BY s.id DESC
");
$stmt_servis->bind_param("i", $profil_id);
$stmt_servis->execute();
$senarai_servis = $stmt_servis->get_result();

// Semak sama ada viewer adalah pemilik profil
$is_owner = isset($_SESSION['usahawan_id']) && $_SESSION['usahawan_id'] == $profil_id;
$can_chat  = isset($_SESSION['usahawan_id']) && !$is_owner;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Profil — <?= htmlspecialchars($usahawan['nama']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ===================== RESET & BASE ===================== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --ink:     #0d1117;
    --ink2:    #374151;
    --muted:   #6b7280;
    --border:  #e5e7eb;
    --surface: #f9fafb;
    --white:   #ffffff;
    --accent:  #0ea5e9;
    --accent2: #0284c7;
    --green:   #10b981;
    --amber:   #f59e0b;
    --radius:  14px;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: #f0f4f8;
    color: var(--ink);
    padding-top: 90px;
    min-height: 100vh;
}

a { text-decoration: none; color: inherit; }

/* ===================== HERO BANNER ===================== */
.hero-banner {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 40%, #1e3a5f 100%);
    position: relative;
    overflow: hidden;
}

.hero-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 40%);
}

/* decorative circles */
.hero-banner::after {
    content: '';
    position: absolute;
    right: -60px;
    bottom: -60px;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    border: 40px solid rgba(255,255,255,0.05);
}

/* ===================== CONTAINER ===================== */
.container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ===================== PROFILE CARD ===================== */
.profile-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    margin-top: -60px;
    padding: 32px 36px 28px;
    position: relative;
    z-index: 2;
    display: flex;
    gap: 28px;
    align-items: flex-start;
    flex-wrap: wrap;
}

.avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.avatar-wrap img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--white);
    box-shadow: 0 4px 18px rgba(0,0,0,0.15);
    margin-top: -52px;
}

.jenis-badge {
    position: absolute;
    bottom: 4px;
    right: 0;
    background: var(--accent);
    color: #fff;
    font-family: 'Sora', sans-serif;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(14,165,233,0.4);
}

.profile-main {
    flex: 1;
    min-width: 200px;
}

.profile-main h1 {
    font-family: 'Sora', sans-serif;
    font-size: 24px;
    font-weight: 800;
    color: var(--ink);
    margin-bottom: 4px;
}

.profile-main .perniagaan {
    font-size: 14px;
    color: var(--accent);
    font-weight: 600;
    margin-bottom: 10px;
}

.meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    font-size: 13px;
    color: var(--muted);
}

.meta-row span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-row .icon {
    font-size: 14px;
}

/* Stats */
.profile-stats {
    display: flex;
    gap: 20px;
    flex-shrink: 0;
    align-items: center;
    flex-wrap: wrap;
}

.stat-box {
    text-align: center;
    padding: 12px 20px;
    background: var(--surface);
    border-radius: 12px;
    border: 1px solid var(--border);
    min-width: 90px;
}

.stat-box .num {
    font-family: 'Sora', sans-serif;
    font-size: 26px;
    font-weight: 800;
    color: var(--accent);
    line-height: 1;
}

.stat-box .lbl {
    font-size: 11px;
    color: var(--muted);
    margin-top: 4px;
    font-weight: 500;
}

/* CTA buttons */
.profile-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-shrink: 0;
    justify-content: center;
}

.btn {
    padding: 11px 22px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 12px rgba(14,165,233,0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14,165,233,0.4);
}

.btn-outline {
    background: var(--white);
    color: var(--ink2);
    border: 1.5px solid var(--border);
}

.btn-outline:hover {
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-2px);
}

/* ===================== TAB BAR ===================== */
.tab-bar {
    display: flex;
    gap: 4px;
    background: var(--white);
    border-radius: var(--radius);
    padding: 6px;
    margin-top: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    width: fit-content;
}

.tab-btn {
    padding: 9px 22px;
    border: none;
    border-radius: 9px;
    font-family: 'Sora', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: var(--muted);
    transition: 0.2s;
}

.tab-btn.active {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(14,165,233,0.3);
}

.tab-btn:hover:not(.active) {
    background: var(--surface);
    color: var(--ink);
}

/* ===================== SECTION CONTENT ===================== */
.tab-content {
    margin-top: 28px;
    display: none;
    animation: fadeUp 0.3s ease;
}

.tab-content.active { display: block; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ===================== GRID PRODUK / SERVIS ===================== */
.item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 20px;
}

.item-card {
    background: var(--white);
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    cursor: pointer;
}

.item-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 32px rgba(0,0,0,0.1);
    border-color: #bfdbfe;
}

.item-img-wrap {
    height: 180px;
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.item-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    transition: transform 0.35s ease;
}

.item-card:hover .item-img-wrap img {
    transform: scale(1.06);
}

.item-body {
    padding: 14px;
}

.item-body .item-name {
    font-family: 'Sora', sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.item-body .item-cat {
    font-size: 11px;
    color: var(--accent);
    font-weight: 600;
    background: #e0f2fe;
    padding: 3px 9px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 8px;
}

.item-body .item-price {
    font-family: 'Sora', sans-serif;
    font-size: 16px;
    font-weight: 800;
    color: var(--ink);
    margin-bottom: 6px;
}

.item-body .item-meta {
    font-size: 12px;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 12px;
}

.stock-ok  { color: var(--green); font-weight: 600; }
.stock-out { color: #ef4444; font-weight: 600; }

.item-body .btn-view {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 700;
    color: var(--accent);
    background: #e0f2fe;
    padding: 7px 13px;
    border-radius: 8px;
    transition: 0.2s;
    width: 100%;
    justify-content: center;
}

.item-body .btn-view:hover {
    background: var(--accent);
    color: #fff;
}

/* ===================== EMPTY STATE ===================== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}

.empty-state .icon {
    font-size: 48px;
    margin-bottom: 14px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 15px;
}

/* ===================== ABOUT SECTION ===================== */
.about-card {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    padding: 28px 32px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 40px;
    margin-top: 20px;
}

.about-item label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--muted);
    display: block;
    margin-bottom: 5px;
}

.about-item p {
    font-size: 14px;
    color: var(--ink);
    font-weight: 500;
}

.about-card h3 {
    font-family: 'Sora', sans-serif;
    font-size: 16px;
    font-weight: 700;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 768px) {
    .profile-card { padding: 22px; gap: 16px; }
    .profile-stats { gap: 12px; }
    .profile-actions { flex-direction: row; flex-wrap: wrap; }
    .about-grid { grid-template-columns: 1fr; }
    .avatar-wrap img { width: 90px; height: 90px; margin-top: -44px; }
    .item-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); }
    .item-img-wrap { height: 145px; }
}

/* ===================== STAGGER ANIMATION ===================== */
.item-card {
    opacity: 0;
    animation: cardIn 0.4s ease forwards;
}

@keyframes cardIn {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.item-card:nth-child(1)  { animation-delay: 0.05s; }
.item-card:nth-child(2)  { animation-delay: 0.10s; }
.item-card:nth-child(3)  { animation-delay: 0.15s; }
.item-card:nth-child(4)  { animation-delay: 0.20s; }
.item-card:nth-child(5)  { animation-delay: 0.25s; }
.item-card:nth-child(6)  { animation-delay: 0.30s; }
.item-card:nth-child(7)  { animation-delay: 0.35s; }
.item-card:nth-child(8)  { animation-delay: 0.40s; }
</style>
</head>

<body>

<!-- HERO BANNER -->
<div class="hero-banner"></div>

<div class="container" style="padding-bottom: 60px;">

    <!-- PROFILE CARD -->
    <div class="profile-card">

        <!-- AVATAR -->
        <div class="avatar-wrap">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
            <span class="jenis-badge"><?= htmlspecialchars($usahawan['jenis']) ?></span>
        </div>

        <!-- NAMA & META -->
        <div class="profile-main">
            <h1><?= htmlspecialchars($usahawan['nama']) ?></h1>
            <div class="perniagaan">🏪 <?= htmlspecialchars($usahawan['perniagaan']) ?></div>
            <div class="meta-row">
                <?php if (!empty($usahawan['alamat'])): ?>
                <span><span class="icon">📍</span><?= htmlspecialchars($usahawan['alamat']) ?></span>
                <?php endif; ?>
                <span><span class="icon">📞</span><?= htmlspecialchars($usahawan['telefon']) ?></span>
                <span><span class="icon">🗓️</span>Ahli sejak <?= date("M Y", strtotime($usahawan['tarikh_daftar'])) ?></span>
            </div>
        </div>

        <!-- STATS -->
        <div class="profile-stats">
            <div class="stat-box">
                <div class="num"><?= $kira_produk ?></div>
                <div class="lbl">Produk</div>
            </div>
            <div class="stat-box">
                <div class="num"><?= $kira_servis ?></div>
                <div class="lbl">Servis</div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="profile-actions">
            <?php if ($is_owner): ?>
                <a href="kemaskini_profil.php" class="btn btn-primary">✏️ Edit Profil</a>
            <?php elseif ($can_chat): ?>
                <a href="chat_room.php?user_id=<?= $profil_id ?>" class="btn btn-primary">💬 Chat Sekarang</a>
                <a href="mailto:<?= htmlspecialchars($usahawan['email']) ?>" class="btn btn-outline">✉️ Emel</a>
            <?php else: ?>
                <a href="daftar.php" class="btn btn-primary">🔐 Log Masuk untuk Chat</a>
            <?php endif; ?>
        </div>

    </div>

    <!-- TAB BAR -->
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('produk', this)">
            📦 Produk <?php if ($kira_produk > 0): ?>(<?= $kira_produk ?>)<?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('servis', this)">
            🛠️ Servis <?php if ($kira_servis > 0): ?>(<?= $kira_servis ?>)<?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('tentang', this)">
            ℹ️ Tentang
        </button>
    </div>

    <!-- ===== TAB: PRODUK ===== -->
    <div id="tab-produk" class="tab-content active">
        <?php if ($senarai_produk->num_rows > 0): ?>
        <div class="item-grid">
            <?php while ($p = $senarai_produk->fetch_assoc()):
                $img_p = $p['gambar_url'] ?: 'default.png';
                if (strpos($img_p, 'uploads/') === false) $img_p = 'uploads/' . $img_p;
            ?>
            <div class="item-card" onclick="window.location.href='butiran_produk.php?id=<?= $p['id'] ?>'">
                <div class="item-img-wrap">
                    <img src="<?= htmlspecialchars($img_p) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
                </div>
                <div class="item-body">
                    <?php if (!empty($p['nama_kategori'])): ?>
                    <span class="item-cat">📦 <?= htmlspecialchars($p['nama_kategori']) ?></span>
                    <?php endif; ?>
                    <div class="item-name"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="item-price">RM <?= number_format($p['harga'], 2) ?></div>
                    <div class="item-meta">
                        <?php if (!empty($p['lokasi'])): ?>
                        📍 <?= htmlspecialchars($p['lokasi']) ?> &nbsp;·&nbsp;
                        <?php endif; ?>
                        <?php if ($p['stok'] > 0): ?>
                            <span class="stock-ok">✅ <?= $p['stok'] ?> unit</span>
                        <?php else: ?>
                            <span class="stock-out">❌ Stok habis</span>
                        <?php endif; ?>
                    </div>
                    <a href="butiran_produk.php?id=<?= $p['id'] ?>" class="btn-view">Lihat Produk →</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">📦</div>
            <p>Tiada produk ditawarkan buat masa ini.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB: SERVIS ===== -->
    <div id="tab-servis" class="tab-content">
        <?php if ($senarai_servis->num_rows > 0): ?>
        <div class="item-grid">
            <?php while ($s = $senarai_servis->fetch_assoc()):
                $img_s = $s['gambar_servis_url'] ?: 'default.png';
                if (strpos($img_s, 'uploads/') === false) $img_s = 'uploads/' . $img_s;
            ?>
            <div class="item-card" onclick="window.location.href='butiran_servis.php?id=<?= $s['id'] ?>'">
                <div class="item-img-wrap">
                    <img src="<?= htmlspecialchars($img_s) ?>" alt="<?= htmlspecialchars($s['nama']) ?>">
                </div>
                <div class="item-body">
                    <?php if (!empty($s['nama_kategori_servis'])): ?>
                    <span class="item-cat">🛠️ <?= htmlspecialchars($s['nama_kategori_servis']) ?></span>
                    <?php endif; ?>
                    <div class="item-name"><?= htmlspecialchars($s['nama']) ?></div>
                    <?php if (!empty($s['lokasi'])): ?>
                    <div class="item-meta">📍 <?= htmlspecialchars($s['lokasi']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['deskripsi'])): ?>
                    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($s['deskripsi']) ?>
                    </div>
                    <?php endif; ?>
                    <a href="butiran_servis.php?id=<?= $s['id'] ?>" class="btn-view">Lihat Servis →</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon">🛠️</div>
            <p>Tiada servis ditawarkan buat masa ini.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== TAB: TENTANG ===== -->
    <div id="tab-tentang" class="tab-content">
        <div class="about-card">
            <h3>📋 Maklumat Usahawan</h3>
            <div class="about-grid">
                <div class="about-item">
                    <label>Nama Penuh</label>
                    <p><?= htmlspecialchars($usahawan['nama']) ?></p>
                </div>
                <div class="about-item">
                    <label>Nama Perniagaan</label>
                    <p><?= htmlspecialchars($usahawan['perniagaan']) ?></p>
                </div>
                <div class="about-item">
                    <label>Jenis Usahawan</label>
                    <p><?= htmlspecialchars($usahawan['jenis']) ?></p>
                </div>
                <div class="about-item">
                    <label>No. Telefon</label>
                    <p><?= htmlspecialchars($usahawan['telefon']) ?></p>
                </div>
                <?php if (!empty($usahawan['email'])): ?>
                <div class="about-item">
                    <label>Emel</label>
                    <p><?= htmlspecialchars($usahawan['email']) ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($usahawan['alamat'])): ?>
                <div class="about-item">
                    <label>Alamat</label>
                    <p><?= htmlspecialchars($usahawan['alamat']) ?></p>
                </div>
                <?php endif; ?>
                <div class="about-item">
                    <label>Ahli Sejak</label>
                    <p><?= date("d F Y", strtotime($usahawan['tarikh_daftar'])) ?></p>
                </div>
                <div class="about-item">
                    <label>Status Akaun</label>
                    <p><?= htmlspecialchars($usahawan['status']) ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function switchTab(name, el) {
    // Hide all
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    // Show target
    document.getElementById('tab-' + name).classList.add('active');
    el.classList.add('active');
}
</script>

<?php include "footer.php"; ?>
</body>
</html>