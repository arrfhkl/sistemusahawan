<?php
// ===== Sambungan ke Pangkkalan Data =====
include "connection.php";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Sambungan gagal: " . $conn->connect_error); 
}

// ===== Dapatkan ID servis =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Ralat: Servis tidak dijumpai.");
}

// ===== Dapatkan maklumat servis =====
$sql = "SELECT * FROM servis WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Ralat: Servis tidak dijumpai.");
}
$servis = $result->fetch_assoc();

// ===== Proses kemaskini data =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama       = $conn->real_escape_string($_POST['nama']);
    $deskripsi  = $conn->real_escape_string($_POST['deskripsi']);
    $lokasi     = $conn->real_escape_string($_POST['lokasi']);
    $harga      = $conn->real_escape_string($_POST['harga']);

    // Kekalkan gambar lama jika tiada upload baru
    $gambar_baru = $servis['gambar_servis_url'];

    // ===== Jika gambar baharu dimuat naik =====
    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Hanya benarkan imej
        $allowTypes = ['jpg','jpeg','png','gif','webp'];
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
                $gambar_baru = $fileName;
            }
        }
    }

    // ===== Kemaskini servis =====
    $update = $conn->query("
        UPDATE servis 
        SET 
            nama='$nama',
            deskripsi='$deskripsi',
            lokasi='$lokasi',
            harga='$harga',
            gambar_servis_url='$gambar_baru'
        WHERE id=$id
    ");

    if ($update) {
        echo "<script>
            alert('✅ Servis berjaya dikemaskini!');
            window.location.href='profil_usahawan.php?id=" . $servis['usahawan_id'] . "';
        </script>";
        exit;
    } else {
        echo "<script>alert('❌ Ralat semasa mengemaskini servis!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Edit Servis - <?= htmlspecialchars($servis['nama']) ?></title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      margin-top: 90px;
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
    }

    .container { 
      max-width: 600px; 
      margin: 50px auto; 
      background: #fff; 
      border-radius: 15px;
      padding: 30px; 
      box-shadow: 0 6px 25px rgba(0,0,0,0.1); 
      border: 2px solid #003366; 
      position: relative;
    }

    h2 { 
      text-align: center; 
      color: #003366; 
      margin-bottom: 20px; 
    }

    label {
      font-weight: 600;
      margin-top: 15px;
      display: block;
    }

    input, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    button {
      background: #003366;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      margin-top: 25px;
      font-weight: bold;
      width: 100%;
      cursor: pointer;
    }

    button:hover {
      background: #001236;
    }

    .preview img {
      width: 200px;
      border-radius: 10px;
      border: 2px solid #003366;
      margin-top: 10px;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      text-decoration: none;
      font-weight: 600;
      color: #003366;
      border: 2px solid #003366;
      padding: 6px 14px;
      border-radius: 20px;
    }
  </style>
</head>
<body>

<div class="container">

  <a href="profil_usahawan.php?id=<?= $servis['usahawan_id'] ?>" class="back-btn">← Kembali</a>

  <h2>Edit Servis</h2>

  <form method="post" enctype="multipart/form-data">

    <label>Nama Servis</label>
    <input type="text" name="nama" value="<?= htmlspecialchars($servis['nama']) ?>" required>

    <label>Deskripsi</label>
    <textarea name="deskripsi" rows="4" required><?= htmlspecialchars($servis['deskripsi']) ?></textarea>

    <label>Lokasi</label>
    <input type="text" name="lokasi" value="<?= htmlspecialchars($servis['lokasi']) ?>" required>

    <label>Harga (RM)</label>
    <input type="number" step="0.01" name="harga" value="<?= htmlspecialchars($servis['harga']) ?>" required>

    <label>Gambar Servis</label>
    <input type="file" name="gambar" accept="image/*" onchange="previewImage(event)">

    <!-- Preview gambar baru -->
    <div class="preview" id="previewWrap" style="display:none;">
      <p>Preview gambar baharu:</p>
      <img id="preview">
    </div>

    <!-- Gambar sedia ada -->
    <div class="preview">
      <p>Gambar sedia ada:</p>
      <?php
        $gambarPath = $servis['gambar_servis_url'];
        if (!empty($gambarPath) && strpos($gambarPath, 'uploads/') === false) {
            $gambarPath = 'uploads/' . $gambarPath;
        }
      ?>
      <img src="<?= htmlspecialchars($gambarPath) ?>">
    </div>

    <button type="submit">Kemaskini Servis</button>
  </form>
</div>

<script>
function previewImage(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('preview').src = e.target.result;
    document.getElementById('previewWrap').style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>

</body>
</html>
