<?php
session_start();
include "connection.php";

// Get user ID from URL or session
$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

// Check if user is Usahawan
$sql = "SELECT jenis, perniagaan, nama FROM usahawan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Block access if Pengguna
if ($user['jenis'] === 'Pengguna' || $user['perniagaan'] === 'Pengguna') {
    echo "<script>
            alert('⚠️ AKSES DITOLAK!\\n\\nFungsi Tambah Produk hanya untuk Usahawan sahaja.\\n\\nSila daftar sebagai Usahawan untuk mengakses ciri ini.');
            window.location = 'profil_usahawan.php?id=" . $user_id . "';
          </script>";
    exit();
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Produk</title>
</head>
<body>
    <h2>Tambah Produk Baru</h2>
    <!-- Rest of your form -->
</body>
</html>

<?php
// Sambungan ke pangkalan data
include "connection.php";
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Sambungan gagal: " . $conn->connect_error); }

// Dapatkan data usahawan
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$usahawan = $conn->query("SELECT * FROM usahawan WHERE id=$id")->fetch_assoc();

$id = $_GET['id']; // atau boleh datang dari session / parameter lain

$stmt = $conn->prepare("SELECT nama FROM usahawan WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama_usahawan = $row['nama'];
} else {
    echo "Usahawan tidak dijumpai.";
}

// Dapatkan id usahawan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID usahawan tidak sah.");
}
$usahawan_id = intval($_GET['id']);

// Ambil kategori
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama ASC");

// Proses form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama       = $conn->real_escape_string($_POST['nama']);
    $harga      = floatval($_POST['harga']);
    $deskripsi  = $conn->real_escape_string($_POST['deskripsi']);
    $lokasi     = $conn->real_escape_string($_POST['lokasi']);
    $stok       = intval($_POST['stok']);
    $kategori_id = intval($_POST['kategori_id']);

    // Handle upload gambar
    $gambar_url = "";
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // cipta folder kalau tiada
        }

        $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
        $targetFile = $targetDir . $fileName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
                $gambar_url = $fileName; // simpan path dalam DB
            } else {
                echo "<script>alert('Ralat: Gagal muat naik gambar');</script>";
            }
        } else {
            echo "<script>alert('Format gambar tidak sah. Hanya JPG, JPEG, PNG, GIF dibenarkan.');</script>";
        }
    }

    // Masukkan data ke DB
    $sql = "INSERT INTO produk (nama, harga, deskripsi, gambar_url, lokasi, stok, kategori_id, usahawan_id)
            VALUES ('$nama', $harga, '$deskripsi', '$gambar_url', '$lokasi', $stok, $kategori_id, $usahawan_id)";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Produk berjaya ditambah!'); window.location='profil_usahawan.php?id=$usahawan_id';</script>";
        exit;
    } else {
        echo "Ralat: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Sistem Usahawan Pahang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/img/jatapahang.png">

    <style>
        /* ===== Reset ===== */
        * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ===== Background Premium dengan Watermark Jata Pahang ===== */
body {
  margin: 0;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  margin-top: 90px;
}

/* ✨ Cahaya lembut keemasan & hitam bergerak */
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 25% 30%, rgba(0, 0, 0, 0.05), transparent 70%),
    radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.15), transparent 70%);
  background-repeat: no-repeat;
  animation: royalWave 25s ease-in-out infinite alternate;
  z-index: -3;
  mix-blend-mode: overlay;
}

/* 🏛️ Multiple Watermark Jata Pahang - lebih jelas */
body::after {
  content: "";
  position: fixed;
  inset: 0;
  background-color: transparent;
  background-image: url("assets/img/jatapahang.png");
  background-repeat: repeat;
  background-size: 180px 180px;
  background-position: center;
  opacity: 0.15; /* 🔆 Naikkan dari 0.07 → 0.15 supaya lebih nampak */
  filter: grayscale(5%) brightness(1.3) contrast(1.1);
  animation: watermarkFloat 40s linear infinite;
  z-index: -2;
}

/* 🌫️ Animasi lembut watermark */
@keyframes watermarkFloat {
  0% { background-position: 0 0; opacity: 0.14; }
  50% { background-position: 80px 60px; opacity: 0.18; }
  100% { background-position: 0 0; opacity: 0.14; }
}

/* 🪄 Efek cahaya bergerak lembut */
@keyframes royalWave {
  0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
  100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
}

/* ===== Kad (card) Optional ===== */
.card {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 215, 0, 0.4);
  border-radius: 14px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 25px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}


/* ===== Header ===== */
header {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  padding: 15px 20px;
  position: fixed;
  top: 0; left: 0; width: 100%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  flex-wrap: wrap;
}

header img.jata { height: 55px; }
.title { color: #fff; font-size: 1.4rem; font-weight: 700; }

.menu-toggle {
  display: none;
  font-size: 1.8rem;
  cursor: pointer;
  background: none;
  border: none;
  color: #fff;
}

/* ===== Navbar ===== */
nav {
  display: flex;
  gap: 15px;
}

nav a {
  color: #fff;
  padding: 8px 12px;
  font-weight: 500;
  text-decoration: none;
  transition: 0.3s;
}
nav a:hover, nav a.active { color: #ffd700; }

/* ===== 3D Metallic Title ===== */
header .title {
  position: relative;
  color: #ffffffff;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-align: center;
  text-shadow:
    0 1px 0 #b3b3b3,
    0 2px 0 #999,
    0 3px 0 #777,
    0 4px 0 #555,
    0 5px 8px rgba(0,0,0,0.6);
  background: linear-gradient(90deg, #e6e6e6 0%, #bfbfbf 50%, #f2f2f2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
  overflow: hidden;
}

/* Subtle animated shine */
header .title::after {
  content: "";
  position: absolute;
  top: 0; left: -75%;
  width: 50%; height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.6) 50%,
    rgba(255,255,255,0) 100%
  );
  animation: textshine 4s linear infinite;
}

@keyframes textshine {
  0% { left: -75%; }
  100% { left: 125%; }
}

/* ===== Metallic Shine Animation ===== */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


        /* ===== Container ===== */
        .container {
            max-width:700px; margin:auto; background:white;
            padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align:center; margin-bottom:20px; color:#2e86de; }
        label { display:block; margin-top:15px; font-weight:bold; }
        input, textarea, select {
            width:100%; padding:10px; margin-top:5px;
            border:1px solid #ccc; border-radius:6px;
        }
        button {
            margin-top:20px; background:#2e86de; color:white;
            border:none; padding:10px 20px; border-radius:5px;
            font-size:1rem; cursor:pointer; transition:background 0.3s;
        }
        button:hover { background:#1f5fa0; }
        a.kembali { display:inline-block; margin-top:15px; text-decoration:none; color:#003366; }

        /* ===== Footer ===== */
footer {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  color: #fff;
  padding: 30px 20px;
  margin-top: 40px;
  text-align: center;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

footer .footer-content {
  max-width: 1100px;
  margin: auto;
  position: relative;
  z-index: 1;
}

footer img {
  height: 60px;
  margin-bottom: 15px;
  filter: drop-shadow(0 3px 5px rgba(0,0,0,0.4));
}

/* ===== 3D Metallic Text ===== */
footer p,
footer .copyright,
footer strong {
  color: #f8f8f8;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-shadow:
    0 1px 0 #ccc,
    0 2px 0 #aaa,
    0 3px 0 #888,
    0 4px 0 #666,
    0 5px 0 #444,
    0 6px 6px rgba(0,0,0,0.6);
  transition: transform 0.3s ease, text-shadow 0.3s ease;
}

/* Glow and depth on hover */
footer p:hover,
footer strong:hover {
  transform: translateY(-2px);
  text-shadow:
    0 1px 0 #fff,
    0 2px 0 #ddd,
    0 3px 0 #bbb,
    0 4px 0 #999,
    0 5px 0 #777,
    0 8px 12px rgba(0, 0, 0, 0.7),
    0 0 10px rgba(255, 255, 255, 0.3);
}

/* Copyright (subtle) */
footer .copyright {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.2);
  font-size: 0.85rem;
  color: #ddd;
  text-shadow:
    0 1px 0 #999,
    0 2px 0 #666,
    0 3px 3px rgba(0,0,0,0.6);
}

/* Metallic Shine Animation */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .slideshow-container { height: 300px; }
  .function-btn { min-height: 130px; }
  .function-btn i { font-size: 2rem; }
}

@media (max-width: 768px) {
  .menu-toggle { display: block; }
  nav {
    display: none;
    flex-direction: column;
    background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    width: 100%;
  }
  nav.show { display: flex; }
  nav a { text-align: center; padding: 10px; font-size: 1rem; }
  .title { font-size: 1.2rem; }
  .slideshow-container { height: 220px; }
  .function-grid { gap: 18px; }
  .function-btn { min-height: 110px; padding: 18px; }
  .function-btn i { font-size: 1.8rem; }
  .function-btn span { font-size: 0.9rem; }
}

@media (max-width: 480px) {
  .slideshow-container { height: 180px; }
  .function-btn { padding: 15px; }
}
    </style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
  </nav>
</header>

<div class="container">
    <h2>Tambah Produk untuk Usahawan: <?= $nama_usahawan ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Nama Produk:</label>
        <input type="text" name="nama" required>

        <label>Harga (RM):</label>
        <input type="number" step="0.01" name="harga" required>

        <label>Deskripsi:</label>
        <textarea name="deskripsi" rows="4"></textarea>

        <label>Muat Naik Gambar:</label>
        <input type="file" name="gambar" accept="image/*" onchange="previewImage(event)">
        <img id="preview" src="" style="display:none; width:180px; margin-top:12px; border-radius:10px; border:2px solid #ccc;">


        <label>Lokasi:</label>
        <input type="text" name="lokasi">

        <label>Stok:</label>
        <input type="number" name="stok" required>

        <label>Kategori:</label>
        <select name="kategori_id" required>
            <option value="">--Pilih Kategori--</option>
            <?php while($row = $kategori->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Simpan Produk</button>
    </form>
    <a href="profil_usahawan.php?id=<?= $usahawan_id ?>" class="kembali">⬅ Kembali ke Profil Usahawan</a>
</div>

<!-- ===== Footer Rasmi ===== -->
<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | Emel: info@pahang.gov.my</p>
    <div class="copyright">
      © <?= date("Y") ?> Kerajaan Negeri Pahang. Hak cipta terpelihara.
    </div>
  </div>
</footer>

<script>
  function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
  }

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
  
</script>

</body>
</html>
<?php $conn->close(); ?>
