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
    .tab-link { padding: 10px 5px; cursor: pointer; font-weight: bold; color: #888; border-bottom: 3px solid transparent; transition: 0.3s; }
    .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.4s; }

    /* Grid Display */
    .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .item-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
    .item-card img { width: 100%; height: 200px; object-fit: cover; }
    .item-body { padding: 15px; }
    .price { color: var(--primary); font-size: 1.2rem; font-weight: bold; margin: 10px 0; }

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

    <div class="tabs">
        <div class="tab-link active" onclick="openTab(event, 'produkTab')">Produk (<?= $produk->num_rows ?>)</div>
        <div class="tab-link" onclick="openTab(event, 'servisTab')">Servis (<?= $servis->num_rows ?>)</div>
    </div>

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
            <p>Tiada produk disenaraikan.</p>
        <?php endif; ?>
    </div>

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
            <p>Tiada servis disenaraikan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.className += " active";
}

function shareProfile() {
    if (navigator.share) {
        navigator.share({
            title: 'Profil <?= $usahawan["nama"] ?>',
            url: window.location.href
        }).catch(console.error);
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