<?php
session_start();

// ===== Database Connection =====
include "connection.php";

if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// ===== Check Login =====
if (!isset($_SESSION['admin_id'])) {
    echo "<script>
        alert('Sila log masuk terlebih dahulu untuk akses Dashboard.');
        window.location.href = 'login_admin.php';
    </script>";
    exit();
}
?>

<?php
// ====== Sambungan ke Database ======
include "connection.php";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// ====== Proses Tambah Ruang ======
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nama_ruang = $_POST['nama_ruang'];
  $lokasi     = $_POST['lokasi'];
  $kemudahan  = $_POST['kemudahan'];
  $kadar_sewa = $_POST['kadar_sewa'];
  $status     = $_POST['status'];

  // ---- Upload Gambar ----
  $target_dir = "uploads/";
  $gambar_name = basename($_FILES["gambar"]["name"]);
  $target_file = $target_dir . $gambar_name;
  $uploadOk = 1;

  // Cipta folder jika tiada
  if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
  }

  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
  $check = getimagesize($_FILES["gambar"]["tmp_name"]);
  if ($check === false) {
    echo "<script>alert('Fail yang dimuat naik bukan gambar.');</script>";
    $uploadOk = 0;
  }

  if ($uploadOk == 1) {
    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
      $sql = "INSERT INTO ruang_fizikal (nama_ruang, lokasi, kemudahan, kadar_sewa, status, gambar)
              VALUES ('$nama_ruang', '$lokasi', '$kemudahan', '$kadar_sewa', '$status', '$gambar_name')";

      if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Ruang berjaya ditambah!');</script>";
      } else {
        echo "<script>alert('Ralat: " . $conn->error . "');</script>";
      }
    } else {
      echo "<script>alert('Ralat memuat naik gambar.');</script>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Ruang Fizikal | Sistem Usahawan Pahang</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
  --primary: #003366;
  --secondary: #0055a5;
  --light: #f5f7fa;
  --white: #ffffff;
}
body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: var(--light);
  display: flex;
}
/* ===== Sidebar ===== */
.sidebar {
  width: 250px;
  height: 100vh;
  background: linear-gradient(180deg, var(--primary), var(--secondary));
  color: white;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  box-shadow: 3px 0 10px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}
.sidebar h2 {
  text-align: center;
  padding: 20px 0;
  font-size: 1.2rem;
  margin: 0;
  background: rgba(255,255,255,0.08);
}
.sidebar a {
  color: white;
  text-decoration: none;
  padding: 14px 20px;
  display: block;
  transition: all 0.3s ease;
}
.sidebar a:hover, .sidebar a.active {
  background: rgba(255,255,255,0.2);
  padding-left: 25px;
}

/* ===== Main Content ===== */
.main-content {
  margin-left: 250px;
  flex: 1;
  padding: 30px;
  transition: margin-left 0.3s ease;
}
header {
  background: var(--white);
  padding: 15px 25px;
  border-radius: 10px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.05);
  margin-bottom: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
header h1 {
  color: var(--primary);
  font-size: 1.4rem;
  margin: 0;
}
.menu-toggle {
  display: none;
  font-size: 1.8rem;
  background: none;
  border: none;
  color: var(--primary);
  cursor: pointer;
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.active {
    transform: translateX(0);
  }
  .menu-toggle {
    display: block;
  }
  .main-content {
    margin-left: 0;
    padding: 20px;
  }
}

    .form-section {
      background: var(--white);
      border-radius: 12px;
      padding: 30px 40px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      max-width: 800px;
      margin: 0 auto;
    }
    .form-section h2 {
      color: var(--primary);
      text-align: center;
      margin-bottom: 25px;
      font-size: 1.4rem;
    }
    label {
      display: block;
      font-weight: 600;
      margin-top: 15px;
      color: var(--primary);
    }
    input[type="text"],
    input[type="date"],
    input[type="url"],
    input[type="file"],
    textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-top: 6px;
      font-size: 0.95rem;
      box-sizing: border-box;
    }
    textarea { height: 120px; resize: vertical; }

    .submit-btn {
      display: block;
      width: 100%;
      margin-top: 25px;
      background: var(--secondary);
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .submit-btn:hover {
      background: var(--primary);
    }

    .list-btn {
      display: block;
      width: 100%;
      margin-top: 25px;
      background: grey;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .list-btn:hover {
      background: darkslategrey;
    }

    .message {
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }
    .success { color: green; }
    .error { color: red; }

    footer {
      text-align: center;
      margin-top: 40px;
      color: #666;
      font-size: 0.9rem;
    }

    label {
      font-weight: bold;
      color: #333;
      display: block;
      margin-bottom: 5px;
      margin-top: 15px;
    }

    input[type="text"], input[type="number"], select, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
    }

    input[type="file"] {
      border: none;
    }

    textarea {
      resize: vertical;
      min-height: 70px;
    }

    button.submit-btn {
      width: 100%;
      background: var(--secondary);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 16px;
      margin-top: 20px;
      cursor: pointer;
      transition: 0.3s;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <h2>Admin Panel</h2>
  <a href="admin_dashboard.php">Dashboard</a>
  <a href="senarai_usahawan.php">Senarai Usahawan</a>
  <a href="admin_agro.php">Permohonan Agro</a>
  <a href="admin_ipush.php">Permohonan iPush</a>
  <a href="admin_itekad.php">Permohonan iTekad</a>
  <a href="tambah_berita.php">Tambah Berita</a>
  <a href="admin_tambah_ruang.php" class="active">Tambah Ruang</a>
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="admin_order.php">Pengurusan Pesanan</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

  <!-- Main Content -->
  <div class="main-content">
    <header>
      <h1>Tambah Ruang Fizikal</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle">☰</button>
    </header>

    <section class="form-section">
      <form method="POST" enctype="multipart/form-data">
      <label>Nama Ruang</label>
      <input type="text" name="nama_ruang" required>

      <label>Lokasi</label>
      <input type="text" name="lokasi" required>

      <label>Kemudahan</label>
      <textarea name="kemudahan" required></textarea>

      <label>Kadar Sewa (RM)</label>
      <input type="number" step="0.01" name="kadar_sewa" required>

      <label>Status</label>
      <select name="status" required>
        <option value="Tersedia">Tersedia</option>
        <option value="Disewa">Disewa</option>
      </select>

      <label>Muat Naik Gambar</label>
      <input type="file" name="gambar" accept="image/*" required onchange="previewImage(event)">

      <img id="preview" style="display:none; margin-top:15px; max-width:200px; border-radius:10px;">


      <button type="submit" class="submit-btn">Simpan Ruang</button>
      <button type="button" class="list-btn" onclick="window.location.href='admin_list_ruang.php'">Senarai Ruang</button>
    </form>
    </section>

    <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
  </div>

  <script>
    //preview gambar sebelum hantar
    function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
  }
  
    // Toggle sidebar untuk mobile
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    toggle.addEventListener('click', () => sidebar.classList.toggle('active'));
  </script>

</body>
</html>
