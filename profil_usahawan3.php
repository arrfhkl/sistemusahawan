<?php
include "connection.php";
include "header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data usahawan
$stmt = $conn->prepare("SELECT * FROM usahawan WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usahawan = $stmt->get_result()->fetch_assoc();

if (!$usahawan) {
    die("<div class='container'><h2>Usahawan tidak ditemui.</h2></div>");
}

// Avatar logic
$avatar = (!empty($usahawan['avatar']) && file_exists($usahawan['avatar']))
    ? $usahawan['avatar']
    : 'assets/img/default_avatar.jpg';

// Ambil Produk
$stmt2 = $conn->prepare("SELECT * FROM produk WHERE usahawan_id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$produk = $stmt2->get_result();

// Ambil Servis
$stmt3 = $conn->prepare("SELECT * FROM servis WHERE usahawan_id = ?");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$servis = $stmt3->get_result();

// Ambil Reviews
$stmt4 = $conn->prepare("
    SELECT r.*, sb.service_id, s.nama AS nama_servis
    FROM reviews r
    LEFT JOIN servis_booking sb ON r.booking_id = sb.id
    LEFT JOIN servis s ON sb.service_id = s.id
    WHERE r.usahawan_id = ?
    ORDER BY r.created_at DESC
");
$stmt4->bind_param("i", $id);
$stmt4->execute();
$reviews = $stmt4->get_result();
$review_count = $reviews->num_rows;

// Average rating
$avgStmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE usahawan_id = ?");
$avgStmt->bind_param("i", $id);
$avgStmt->execute();
$avg_rating = round($avgStmt->get_result()->fetch_assoc()['avg_rating'], 1);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Profil <?= htmlspecialchars($usahawan['nama']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root { --primary: #003366; --secondary: #25D366; --light: #f4f7f6; }
    body { background-color: var(--light); font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 1000px; margin: 30px auto; padding: 0 15px; }

    /* Profile Header Card */
    .profile-card {
        background: #fff; border-radius: 15px; padding: 30px;
        display: flex; flex-wrap: wrap; gap: 25px; align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;
    }
    .profile-img { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .profile-info { flex: 1; min-width: 250px; }
    .profile-info h2 { margin: 0; color: var(--primary); font-size: 1.8rem; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; background: #e8f5e9; color: #2e7d32; font-size: 0.8rem; font-weight: bold; margin-bottom: 10px; }

    /* Buttons */
    .btn-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
    .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer; transition: 0.3s; }
    .btn-chat { background: var(--primary); color: white; }
    .btn-wa { background: var(--secondary); color: white; }
    .btn-share { background: #eee; color: #555; }
    .btn:hover { opacity: 0.85; transform: translateY(-2px); }

    /* Tabs System */
    .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 25px; gap: 20px; }
    .tab-link { padding: 10px 5px; cursor: pointer; font-weight: bold; color: #888; border-bottom: 3px solid transparent; transition: 0.3s; margin-bottom: -2px; }
    .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s; }

    /* Grid Display */
    .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .item-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
    .item-card img { width: 100%; height: 200px; object-fit: cover; }
    .item-body { padding: 15px; }
    .price { color: var(--primary); font-size: 1.2rem; font-weight: bold; margin: 10px 0; }

    /* ── Reviews Tab ── */
    .review-summary {
        display: flex;
        align-items: center;
        gap: 24px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }
    .review-score {
        text-align: center;
        min-width: 80px;
    }
    .review-score .big { font-size: 2.8rem; font-weight: 700; color: var(--primary); line-height: 1; }
    .review-score .stars-display { color: #F59E0B; font-size: 16px; margin: 4px 0; }
    .review-score .count { font-size: 12px; color: #9CA3AF; }
    .review-bars { flex: 1; }
    .bar-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 5px;
        font-size: 12px;
        color: #6B7280;
    }
    .bar-row .bar-label { width: 40px; text-align: right; white-space: nowrap; }
    .bar-track { flex: 1; background: #F3F4F6; border-radius: 99px; height: 6px; overflow: hidden; }
    .bar-fill { height: 100%; background: #F59E0B; border-radius: 99px; }
    .bar-num { width: 20px; }

    .review-list { display: flex; flex-direction: column; gap: 12px; }
    .review-item {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 18px 20px;
    }
    .review-item-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }
    .reviewer-info { display: flex; align-items: center; gap: 10px; }
    .reviewer-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .reviewer-name { font-size: 14px; font-weight: 600; color: #111827; }
    .reviewer-service { font-size: 12px; color: #9CA3AF; margin-top: 1px; }
    .review-meta { text-align: right; flex-shrink: 0; }
    .review-stars { color: #F59E0B; font-size: 14px; }
    .review-date { font-size: 11px; color: #9CA3AF; margin-top: 3px; }
    .review-komen { font-size: 13.5px; color: #374151; line-height: 1.6; }

    .review-photos {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }
    .review-photos a { display: block; flex-shrink: 0; }
    .review-photos img {
        width: 72px; height: 72px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #E5E7EB;
        transition: opacity .15s;
    }
    .review-photos img:hover { opacity: .85; }

    .empty-state {
        text-align: center;
        padding: 48px 20px;
        color: #9CA3AF;
        font-size: 13.5px;
    }
    .empty-state .empty-icon { font-size: 32px; margin-bottom: 10px; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
</head>
<body>

<div class="container">
    <div class="profile-card">
        <img src="<?= htmlspecialchars($avatar) ?>" class="profile-img" onerror="this.src='assets/img/default_avatar.jpg'">
        <div class="profile-info">
            <span class="status-badge"><i class="fas fa-check-circle"></i> Usahawan Aktif</span>
            <h2><?= htmlspecialchars($usahawan['nama']) ?></h2>
            <p style="color: #666; margin: 5px 0;"><i class="fas fa-store"></i> <?= htmlspecialchars($usahawan['perniagaan']) ?> (<?= htmlspecialchars($usahawan['jenis']) ?>)</p>
            <p style="font-size: 0.9rem; color: #888;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($usahawan['alamat']) ?></p>

            <div class="btn-group">
                <a href="chat_room.php?penerima_id=<?= $usahawan['id'] ?>" class="btn btn-chat">
                    <i class="fas fa-comments"></i> Chat Sekarang
                </a>
                <a href="https://wa.me/6<?= preg_replace('/[^0-9]/', '', $usahawan['telefon']) ?>" target="_blank" class="btn btn-wa">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <button onclick="shareProfile()" class="btn btn-share">
                    <i class="fas fa-share-alt"></i> Kongsi
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tab-link active" onclick="openTab(event, 'produkTab')">Produk (<?= $produk->num_rows ?>)</div>
        <div class="tab-link" onclick="openTab(event, 'servisTab')">Servis (<?= $servis->num_rows ?>)</div>
        <div class="tab-link" onclick="openTab(event, 'reviewTab')">Ulasan (<?= $review_count ?>)</div>
    </div>

    <!-- Produk Tab -->
    <div id="produkTab" class="tab-content active">
        <?php if ($produk->num_rows > 0): ?>
        <div class="item-grid">
            <?php while ($p = $produk->fetch_assoc()):
                $imgP = (!empty($p['gambar_url'])) ? (strpos($p['gambar_url'], 'uploads/') !== false ? $p['gambar_url'] : "uploads/".$p['gambar_url']) : 'assets/img/no_image.jpg';
            ?>
            <div class="item-card">
                <img src="<?= htmlspecialchars($imgP) ?>">
                <div class="item-body">
                    <h3 style="font-size:1.1rem; margin:0;"><?= htmlspecialchars($p['nama']) ?></h3>
                    <div class="price">RM <?= number_format($p['harga'], 2) ?></div>
                    <p style="font-size:0.85rem; color:#777;"><?= substr(htmlspecialchars($p['deskripsi']), 0, 80) ?>...</p>
                    <a href="butiran_produk.php?id=<?= $p['id'] ?>" style="color:var(--primary); text-decoration:none; font-size:0.9rem; font-weight:bold;">Lihat Butiran →</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="empty-state"><div class="empty-icon">📦</div>Tiada produk disenaraikan.</div>
        <?php endif; ?>
    </div>

    <!-- Servis Tab -->
    <div id="servisTab" class="tab-content">
        <?php if ($servis->num_rows > 0): ?>
        <div class="item-grid">
            <?php while ($s = $servis->fetch_assoc()):
                $imgS = (!empty($s['gambar_servis_url'])) ? (strpos($s['gambar_servis_url'], 'uploads/') !== false ? $s['gambar_servis_url'] : "uploads/".$s['gambar_servis_url']) : 'assets/img/no_image.jpg';
            ?>
            <div class="item-card">
                <img src="<?= htmlspecialchars($imgS) ?>">
                <div class="item-body">
                    <h3 style="font-size:1.1rem; margin:0;"><?= htmlspecialchars($s['nama']) ?></h3>
                    <p style="font-size:0.9rem; color:#444;"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($s['lokasi']) ?></p>
                    <a href="butiran_servis.php?id=<?= $s['id'] ?>" class="btn btn-chat" style="margin-top:10px; font-size:0.8rem; justify-content:center;">
                        Tempah Servis
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="empty-state"><div class="empty-icon">🔧</div>Tiada servis disenaraikan.</div>
        <?php endif; ?>
    </div>

    <!-- Reviews Tab -->
    <div id="reviewTab" class="tab-content">
        <?php if ($review_count > 0):

            // Count per star for bar chart
            $barStmt = $conn->prepare("SELECT rating, COUNT(*) AS total FROM reviews WHERE usahawan_id = ? GROUP BY rating");
            $barStmt->bind_param("i", $id);
            $barStmt->execute();
            $barResult = $barStmt->get_result();
            $starCounts = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
            while ($row = $barResult->fetch_assoc()) $starCounts[(int)$row['rating']] = (int)$row['total'];
        ?>

        <!-- Summary -->
        <div class="review-summary">
            <div class="review-score">
                <div class="big"><?= $avg_rating ?></div>
                <div class="stars-display">
                    <?php for ($i = 1; $i <= 5; $i++) echo $i <= round($avg_rating) ? '★' : '☆'; ?>
                </div>
                <div class="count"><?= $review_count ?> ulasan</div>
            </div>
            <div class="review-bars">
                <?php for ($s = 5; $s >= 1; $s--): 
                    $pct = $review_count > 0 ? round($starCounts[$s] / $review_count * 100) : 0;
                ?>
                <div class="bar-row">
                    <div class="bar-label"><?= $s ?>★</div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="bar-num"><?= $starCounts[$s] ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Review list -->
        <div class="review-list">
            <?php $reviews->data_seek(0); while ($r = $reviews->fetch_assoc()): ?>
            <div class="review-item">
                <div class="review-item-top">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar"><?= strtoupper(mb_substr($r['pelanggan_nama'], 0, 1)) ?></div>
                        <div>
                            <div class="reviewer-name"><?= htmlspecialchars($r['pelanggan_nama']) ?></div>
                            <?php if (!empty($r['nama_servis'])): ?>
                            <div class="reviewer-service"><?= htmlspecialchars($r['nama_servis']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="review-meta">
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++) echo $i <= $r['rating'] ? '★' : '☆'; ?>
                        </div>
                        <div class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                    </div>
                </div>
                <div class="review-komen"><?= htmlspecialchars($r['komen']) ?></div>

                <?php
                $photos = !empty($r['gambar']) ? json_decode($r['gambar'], true) : [];
                if (!empty($photos)): ?>
                <div class="review-photos">
                  <?php foreach ($photos as $ph): ?>
                  <a href="uploads/reviews/<?= htmlspecialchars($ph) ?>" target="_blank">
                    <img src="uploads/reviews/<?= htmlspecialchars($ph) ?>" alt="review photo">
                  </a>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endwhile; ?>
        </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                Belum ada ulasan untuk usahawan ini.
            </div>
        <?php endif; ?>
    </div>

</div><!-- .container -->

<script>
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-content').forEach(t => { t.style.display = 'none'; t.classList.remove('active'); });
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    document.getElementById(tabName).style.display = 'block';
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}

function shareProfile() {
    if (navigator.share) {
        navigator.share({ title: 'Profil <?= addslashes($usahawan["nama"]) ?>', url: window.location.href }).catch(console.error);
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert("Pautan profil disalin ke clipboard!");
    }
}
</script>

<?php include "footer.php"; ?>
</body>
</html>
<?php $conn->close(); ?>