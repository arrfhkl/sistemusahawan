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
// ===== Sambungan Database =====
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistem_usahawan_pahang";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// ===== Simpan Data =====
$success = $error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $tajuk = $conn->real_escape_string($_POST['tajuk']);
  $tarikh = $conn->real_escape_string($_POST['tarikh']);
  $kandungan = $conn->real_escape_string($_POST['kandungan']);
  $pautan = $conn->real_escape_string($_POST['pautan']);

  // Upload imej
  $target_dir = "uploads/";
  if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

  $file_name = basename($_FILES["imej"]["name"]);
  $target_file = $target_dir . uniqid() . "_" . $file_name;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  $check = getimagesize($_FILES["imej"]["tmp_name"]);
  if ($check !== false) {
    if (move_uploaded_file($_FILES["imej"]["tmp_name"], $target_file)) {
      $sql = "INSERT INTO berita (tajuk, tarikh, kandungan, imej, pautan)
              VALUES ('$tajuk', '$tarikh', '$kandungan', '$target_file', '$pautan')";
      if ($conn->query($sql) === TRUE) {
        $success = "✅ Berita berjaya ditambah!";
      } else {
        $error = "❌ Ralat semasa menyimpan data.";
      }
    } else {
      $error = "❌ Gagal memuat naik imej.";
    }
  } else {
    $error = "❌ Fail bukan imej yang sah.";
  }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Berita | Sistem Usahawan Pahang</title>
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
  <a href="tambah_berita.php" class="active">Tambah Berita</a>
  <a href="admin_tambah_ruang.php">Tambah Ruang</a>
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="admin_order.php">Pengurusan Pesanan</a>
  <a href="pending_usahawan.php">Pengesahan Usahawan</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

  <!-- Main Content -->
  <div class="main-content">
    <header>
      <h1>Tambah Berita Baharu</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle">☰</button>
    </header>

    <section class="form-section">
      <?php if ($success): ?><p class="message success"><?= $success ?></p><?php endif; ?>
      <?php if ($error): ?><p class="message error"><?= $error ?></p><?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <label for="tajuk">Tajuk Berita:</label>
        <input type="text" id="tajuk" name="tajuk" required>

        <label for="tarikh">Tarikh:</label>
        <input type="date" id="tarikh" name="tarikh" required>

        <label for="kandungan">Kandungan:</label>
        <textarea id="kandungan" name="kandungan" required></textarea>

        <label for="imej">Muat Naik Imej:</label>
        <input type="file" id="imej" name="imej" accept="image/*" required onchange="previewImage(event)">

        <img id="preview" style="display:none; margin-top:15px; max-width:200px; border-radius:10px;">

        <label for="pautan">Pautan (jika ada):</label>
        <input type="url" id="pautan" name="pautan" placeholder="https://...">

        <button type="submit" class="submit-btn">Tambah Berita</button>
        <button type="button" class="list-btn" onclick="window.location.href='senarai_berita.php'">Senarai Berita</button>
      </form>
    </section>

    <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
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
  
    // Toggle sidebar untuk mobile
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    toggle.addEventListener('click', () => sidebar.classList.toggle('active'));
  </script>

</body>
</html>
