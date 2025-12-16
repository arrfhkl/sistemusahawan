<?php
session_start();
$conn = new mysqli("localhost", "root", "", "sistem_usahawan_pahang");
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

/* ✅ PASTIKAN PELANGGAN LOGIN */
if (!isset($_SESSION['usahawan_id'])) {
  echo "<script>
    alert('Sila login terlebih dahulu');
    window.location='login.php';
  </script>";
  exit;
}

$user_id = $_SESSION['usahawan_id'];

/* ✅ PROSES CANCEL TEMPAHAN */
if (isset($_GET['cancel_id'])) {
  $cancel_id = (int)$_GET['cancel_id'];

  $check = $conn->query("
    SELECT id FROM servis_booking 
    WHERE id = $cancel_id AND usahawan_id = $user_id AND status = 'pending'
  ");

  if ($check->num_rows > 0) {
    $conn->query("
      UPDATE servis_booking 
      SET status = 'cancelled' 
      WHERE id = $cancel_id
    ");

    echo "<script>
      alert('✅ Tempahan berjaya dibatalkan');
      window.location='pelanggan_status_tempahan.php';
    </script>";
    exit;
  }
}

/* ✅ PAPAR SEMUA TEMPAHAN USER */
$result = $conn->query("
  SELECT 
    b.*,
    s.nama AS nama_servis,
    s.lokasi,
    s.harga,
    s.gambar_servis_url
  FROM servis_booking b
  JOIN servis s ON b.service_id = s.id
  WHERE b.usahawan_id = $user_id
  ORDER BY b.id DESC
");
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Status Tempahan Saya</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
  font-family: Arial;
  background: #f2f2f2;
  margin: 0;
}

.container {
  max-width: 1100px;
  margin: 30px auto;
  padding: 20px;
}

h2 {
  text-align: center;
  color: #003366;
  margin-bottom: 25px;
}

/* ✅ CARD SEBELAH-SEBELAH */
.tempahan-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0,0,0,.15);
  overflow: hidden;
  margin-bottom: 25px;
  display: grid;
  grid-template-columns: 280px 1fr;
}

.tempahan-card img {
  width: 100%;
  height: 100%;
  min-height: 220px;
  object-fit: cover;
}

/* ✅ BAHAGIAN DETAIL */
.tempahan-content {
  padding: 18px 22px;
}

.tempahan-content h3 {
  margin: 0 0 10px;
  color: #222;
}

.tempahan-content p {
  margin: 6px 0;
  font-size: 15px;
  color: #555;
}

.status {
  font-weight: bold;
  margin-top: 10px;
  padding: 6px 14px;
  display: inline-block;
  border-radius: 20px;
  font-size: 13px;
}

.status.pending { background: orange; color: #fff; }
.status.accepted { background: blue; color: #fff; }
.status.on-going { background: purple; color: #fff; }
.status.completed { background: green; color: #fff; }
.status.cancelled { background: red; color: #fff; }

.action-row {
  margin-top: 15px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-cancel {
  background: red;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}

.btn-again {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}

.btn-again:hover { background:#0056b3; }
.btn-cancel:hover { background: darkred; }

/* ✅ RESPONSIVE */
@media (max-width: 768px) {
  .tempahan-card {
    grid-template-columns: 1fr;
  }

  .tempahan-card img {
    height: 220px;
  }
}
</style>
</head>
<body>

<div class="container">
  <h2>📋 Status Tempahan Saya</h2>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>

      <?php
        // ✅ Format masa ke AM / PM
        $masaAMPM = date("g:i A", strtotime($row['masa']));
      ?>

      <div class="tempahan-card">

        <?php if (!empty($row['gambar_servis_url'])): ?>
          <img src="uploads/<?= htmlspecialchars($row['gambar_servis_url']) ?>">
        <?php else: ?>
          <img src="https://via.placeholder.com/400x250?text=Tiada+Gambar">
        <?php endif; ?>

        <div class="tempahan-content">
          <h3><?= htmlspecialchars($row['nama_servis']) ?></h3>

          <p><strong>📍 Lokasi:</strong> <?= htmlspecialchars($row['alamat']) ?></p>
          <p><strong>🗓️ Tarikh:</strong> <?= $row['tarikh'] ?></p>
          <p><strong>⏰ Masa:</strong> <?= $masaAMPM ?></p>
          <p><strong>🛠️ Masalah:</strong> <?= htmlspecialchars($row['masalah']) ?></p>

          <span class="status <?= $row['status'] ?>">
            <?= strtoupper($row['status']) ?>
          </span>

          <div class="action-row">

            <?php if ($row['status'] == 'pending'): ?>
              <button class="btn-cancel"
                onclick="confirmCancel(<?= $row['id'] ?>)">
                ❌ Batalkan Tempahan
              </button>
            <?php endif; ?>

            <button class="btn-again"
              onclick="window.location.href='tempah_servis.php?id=<?= $row['service_id'] ?>'">
              🔁 Buat Tempahan Semula
            </button>

          </div>
        </div>

      </div>

    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center;">Tiada tempahan dibuat.</p>
  <?php endif; ?>
</div>

<script>
function confirmCancel(id){
  if(confirm("Adakah anda pasti ingin membatalkan tempahan ini?")){
    window.location.href = "pelanggan_status_tempahan.php?cancel_id=" + id;
  }
}
</script>

</body>
</html>

<?php $conn->close(); ?>
