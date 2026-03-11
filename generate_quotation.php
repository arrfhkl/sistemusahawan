<?php
include 'connection.php';
include 'header.php';

$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>
        alert('Sila login dahulu');
        window.location='login.php';
    </script>";
    exit;
}

$usahawan_id = (int)$_SESSION['usahawan_id'];

if (!isset($_GET['booking_id'])) {
    die("Booking ID tidak sah.");
}

$booking_id = (int)$_GET['booking_id'];

/* ================= GET BOOKING ================= */
$stmt = $conn->prepare("
    SELECT sb.*, s.nama AS nama_servis
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

/* ================= FORM SUBMIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = $_POST['amount'];
    $note   = $conn->real_escape_string($_POST['note']);

    if (empty($amount)) {
        echo "<script>alert('Jumlah harga diperlukan');</script>";
    } else {

        $insert = $conn->prepare("
            INSERT INTO quotation 
            (chat_id, seller_id, amount, note, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $chat_id = $_POST['chat_id']; // assume hidden field

        $insert->bind_param("iids", $chat_id, $usahawan_id, $amount, $note);
        $insert->execute();

        echo "<script>
            alert('Sebut harga berjaya dihantar');
            window.location='booking_detail.php?id=$booking_id';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Jana Sebut Harga</title>
<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:#f5f7fa;
}

.container{
    max-width:700px;
    margin:40px auto;
}

.card{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 5px 25px rgba(0,0,0,.08);
}

h2{
    margin-top:0;
}

.label{
    font-size:13px;
    color:#777;
    margin-bottom:5px;
}

input, textarea{
    width:100%;
    padding:12px;
    border-radius:8px;
    border:1px solid #ddd;
    margin-bottom:20px;
    font-size:14px;
}

textarea{
    resize:vertical;
    min-height:100px;
}

.summary-box{
    background:#f8f9fa;
    padding:15px;
    border-radius:8px;
    margin-bottom:25px;
    font-size:14px;
}

.btn{
    padding:12px 20px;
    border:none;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
}

.btn-submit{
    background:#007bff;
    color:white;
}

.btn-back{
    background:#adb5bd;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:8px;
}
</style>
</head>
<body>

<div class="container">

    <div class="card">

        <h2>Jana Sebut Harga</h2>

        <div class="summary-box">
            <strong>Servis:</strong> <?= htmlspecialchars($data['nama_servis']) ?><br>
            <strong>Pelanggan:</strong> <?= htmlspecialchars($data['nama_pelanggan']) ?><br>
            <strong>Tarikh Tempahan:</strong> <?= $data['tarikh'] ?><br>
            <strong>Masalah:</strong><br>
            <?= nl2br(htmlspecialchars($data['masalah'])) ?>
        </div>

        <form method="POST">

            <!-- IMPORTANT: CHAT ID HIDDEN FIELD -->
            <input type="hidden" name="chat_id" value="0">

            <div class="label">Jumlah Harga (RM)</div>
            <input type="number" step="0.01" name="amount" required>

            <div class="label">Butiran / Nota Sebut Harga</div>
            <textarea name="note" placeholder="Contoh: Termasuk kos alat ganti dan upah kerja..." required></textarea>

            <button type="submit" class="btn btn-submit">
                Hantar Sebut Harga
            </button>

            <a href="booking_detail.php?id=<?= $booking_id ?>" class="btn-back">
                Kembali
            </a>

        </form>

    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>