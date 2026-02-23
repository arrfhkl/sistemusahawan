<?php
include 'connection.php';
include 'header.php';

$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>
        alert('Sila login dahulu');
        window.location='login.php';
    </script>";
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

if (!isset($_GET['id'])) {
    die("ID tempahan tidak sah.");
}

$booking_id = (int) $_GET['id'];

$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis, s.gambar_servis_url
    FROM servis_booking sb
    JOIN servis s ON sb.service_id = s.id
    WHERE sb.id = ? AND sb.usahawan_id = ?
");

$stmt->bind_param("ii", $booking_id, $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Tempahan tidak dijumpai.");
}

$data = $result->fetch_assoc();

/* ================= UPDATE STATUS ================= */
if (isset($_POST['update_status'])) {

    $status_baru = $_POST['status'];

    $update = $conn->prepare("
        UPDATE servis_booking
        SET status = ?
        WHERE id = ? AND usahawan_id = ?
    ");

    $update->bind_param("sii", $status_baru, $booking_id, $usahawan_id);
    $update->execute();

    echo "<script>
        window.location='booking_detail.php?id=$booking_id';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Butiran Tempahan</title>

<style>
body{
    font-family: 'Segoe UI', sans-serif;
    background:#f5f7fa;
}

.wrapper{
    max-width:1100px;
    margin:40px auto;
}

/* Header Card */
.header-card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header-card h2{
    margin:0;
}

.badge{
    padding:8px 16px;
    border-radius:50px;
    font-size:13px;
    font-weight:600;
}

.pending{ background:#fff3cd; color:#856404; }
.approved{ background:#d4edda; color:#155724; }
.rejected{ background:#f8d7da; color:#721c24; }
.completed{ background:#e2e3e5; color:#383d41; }

/* Main Layout */
.grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:25px;
}

.card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}

.section-title{
    font-weight:600;
    margin-bottom:15px;
    border-bottom:1px solid #eee;
    padding-bottom:10px;
}

.info-row{
    margin-bottom:12px;
}

.label{
    font-size:13px;
    color:#888;
}

.value{
    font-weight:500;
}

.preview-img{
    max-width:100%;
    border-radius:10px;
    margin-top:10px;
}

/* Buttons */
.btn{
    width:100%;
    padding:12px;
    border-radius:8px;
    border:none;
    font-weight:600;
    margin-bottom:10px;
    cursor:pointer;
}

.btn-approve{ background:#28a745; color:white; }
.btn-reject{ background:#dc3545; color:white; }
.btn-complete{ background:#6c757d; color:white; }
.btn-quotation{ background:#007bff; color:white; }
.btn-back{ background:#adb5bd; color:white; }

.btn:disabled{
    opacity:.6;
    cursor:not-allowed;
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- HEADER -->
    <div class="header-card">
      <div style="display:flex; gap:20px; align-items:center;">

    <?php if(!empty($data['gambar_servis_url'])): ?>
        <img src="uploads/<?= htmlspecialchars($data['gambar_servis_url']) ?>"
             style="width:90px;height:90px;object-fit:cover;border-radius:10px;">
    <?php endif; ?>

    <div>
        <h2 style="margin:0;">
            Tempahan Servis <?= htmlspecialchars($data['nama_servis']) ?>
        </h2>
        <small>ID Tempahan: #<?= $data['id'] ?></small>
    </div>
    
        </div>
</div>

    <div class="grid">

        <!-- LEFT CONTENT -->
        <div class="card">
            <div class="section-title">Maklumat Pelanggan</div>

            <div class="info-row">
                <div class="label">Nama</div>
                <div class="value"><?= htmlspecialchars($data['nama_pelanggan']) ?></div>
            </div>

            <div class="info-row">
                <div class="label">Telefon</div>
                <div class="value"><?= htmlspecialchars($data['telefon']) ?></div>
            </div>

            <div class="info-row">
                <div class="label">Alamat</div>
                <div class="value"><?= nl2br(htmlspecialchars($data['alamat'])) ?></div>
            </div>

            <hr style="margin:20px 0;">

            <div class="section-title">Maklumat Servis</div>

            <div class="info-row">
                <div class="label">Tarikh</div>
                <div class="value"><?= $data['tarikh'] ?></div>
            </div>

            <div class="info-row">
                <div class="label">Masa</div>
                <div class="value"><?= $data['masa'] ?></div>
            </div>

            <div class="info-row">
                <div class="label">Masalah</div>
                <div class="value"><?= nl2br(htmlspecialchars($data['masalah'])) ?></div>
            </div>

            <?php if(!empty($data['imej'])): ?>
                <div class="info-row">
                    <div class="label">Imej Lampiran</div>
                    <img src="uploads/<?= htmlspecialchars($data['imej']) ?>" class="preview-img">
                </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT ACTION PANEL -->
        <div class="card">
            <div class="section-title">Tindakan</div>

            <form method="POST">

                <?php if($data['status']=='pending'): ?>
                    <button class="btn btn-approve" 
                            name="status" 
                            value="approved" 
                            type="submit" 
                            name="update_status">
                        Luluskan Tempahan
                    </button>

                    <button class="btn btn-reject" 
                            name="status" 
                            value="rejected" 
                            type="submit" 
                            name="update_status">
                        Tolak Tempahan
                    </button>
                <?php endif; ?>

                <?php if($data['status']=='approved'): ?>
                    <button class="btn btn-complete" 
                            name="status" 
                            value="completed" 
                            type="submit" 
                            name="update_status">
                        Tandakan Selesai
                    </button>
                <?php endif; ?>

                <input type="hidden" name="update_status" value="1">
            </form>

            <a href="generate_quotation.php?booking_id=<?= $data['id'] ?>">
                <button class="btn btn-quotation"
                    <?= ($data['status']!='approved') ? 'disabled' : '' ?>>
                    Jana Sebut Harga
                </button>
            </a>

            <a href="chat.php?chat_id=<?= $chat_id ?>">
                <button class="btn" style="background:#17a2b8;color:white;">
                    Chat Pelanggan
                </button>
            </a>

            <a href="seller_booking.php">
                <button class="btn btn-back">
                    Kembali
                </button>
            </a>

        </div>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>