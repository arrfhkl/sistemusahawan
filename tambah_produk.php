<?php
include "connection.php";
include "header.php";

/* ==================================================
   AUTH CHECK – WAJIB LOGIN USAHAWAN
================================================== */
if (!isset($_SESSION['usahawan_id'])) {
    header("Location: login.php");
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ==================================================
   DAPATKAN MAKLUMAT USAHAWAN LOGIN
================================================== */
$stmt = $conn->prepare("SELECT nama FROM usahawan WHERE id = ?");
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Akaun usahawan tidak sah.");
}

$usahawan = $result->fetch_assoc();
$nama_usahawan = $usahawan['nama'];
$stmt->close();

/* ==================================================
   DAPATKAN SENARAI KATEGORI
================================================== */
$kategori = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");

/* ==================================================
   PROSES TAMBAH PRODUK
================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama        = trim($_POST['nama']);
    $harga       = floatval($_POST['harga']);
    $deskripsi   = trim($_POST['deskripsi']);
    $lokasi      = trim($_POST['lokasi']);
    $stok        = intval($_POST['stok']);
    $kategori_id = intval($_POST['kategori_id']);

    /* ===== Upload Gambar ===== */
    $gambar_url = null;

    if (!empty($_FILES['gambar']['name'])) {

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Format gambar tidak dibenarkan');</script>";
        } else {
            $fileName = uniqid("produk_") . "." . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $targetDir . $fileName);
            $gambar_url = $fileName;
        }
    }

    /* ===== INSERT PRODUK (SESSION BASED) ===== */
    $stmt = $conn->prepare("
        INSERT INTO produk
        (nama, harga, deskripsi, gambar_url, lokasi, stok, kategori_id, usahawan_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sdsssiii",
        $nama,
        $harga,
        $deskripsi,
        $gambar_url,
        $lokasi,
        $stok,
        $kategori_id,
        $usahawan_id
    );

    if ($stmt->execute()) {
        echo "<script>
            alert('Produk berjaya ditambah!');
            window.location = 'profil_usahawan.php';
        </script>";
        exit;
    } else {
        echo "Ralat: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin-top: 90px;
            background: #f4f6f8;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 720px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2e86de;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 25px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2e86de;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background: #1f5fa0;
        }
        .back {
            display: block;
            margin-top: 18px;
            text-align: center;
            text-decoration: none;
            color: #003366;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">
    <h2> TAMBAH PRODUK </h2>

    <form method="POST" enctype="multipart/form-data">

        <label>Nama Produk</label>
        <input type="text" name="nama" required>

        <label>Harga (RM)</label>
        <input type="number" step="0.01" name="harga" required>

        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="4"></textarea>

        <label>Gambar Produk Utama</label>
        <input type="file" name="gambar" accept="image/*" onchange="previewImage(event)">
        <img id="preview" style="
          display:none;
          margin-top:12px;
          width:200px;
          border-radius:10px;
          border:1px solid #ccc;
          box-shadow:0 4px 10px rgba(0,0,0,0.15);
        "
        >

        <label>Galeri Produk – Sokongan Muat Naik Berbilang Gambar</label>
        <input type="file" name="gallery[]" multiple accept="image/*" onchange="previewGallery(event)">
        <div id="gallery-preview" style="
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:12px;
        "></div>

        <label>Lokasi</label>
        <input type="text" name="lokasi">

        <label>Stok</label>
        <input type="number" name="stok" required>

        <label>Kategori</label>
        <select name="kategori_id" required>
            <option value="">-- Pilih Kategori --</option>
            <?php while($row = $kategori->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Simpan Produk</button>
    </form>

    <a href="profil_usahawan.php" class="back">⬅ Kembali ke Profil</a>
</div>

<script>
  // preview gambar 
  function previewImage(event) {
    const reader = new FileReader();
      reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
        output.style.display = 'block';
      };
    reader.readAsDataURL(event.target.files[0]);
    }

 // preview multiple gallery images
function previewGallery(event) {

    const container = document.getElementById('gallery-preview');
    container.innerHTML = ""; // reset preview

    const files = event.target.files;

    for (let i = 0; i < files.length; i++) {

        const reader = new FileReader();

        reader.onload = function(e) {

            const img = document.createElement("img");
            img.src = e.target.result;
            img.style.width = "120px";
            img.style.height = "120px";
            img.style.objectFit = "cover";
            img.style.borderRadius = "8px";
            img.style.border = "1px solid #ccc";
            img.style.boxShadow = "0 3px 8px rgba(0,0,0,0.15)";

            container.appendChild(img);
        };

        reader.readAsDataURL(files[i]);
    }
}
  
</script>

<?php include "footer.php"; ?>
<?php $conn->close(); ?>

</body>
</html>
