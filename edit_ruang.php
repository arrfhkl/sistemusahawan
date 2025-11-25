<?php
session_start();

// ===== Sambungan Database =====
include "connection.php";

if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// ===== Semak Login =====
if (!isset($_SESSION['admin_id'])) {
    echo "<script>
        alert('Sila log masuk terlebih dahulu untuk akses halaman ini.');
        window.location.href = 'login_admin.php';
    </script>";
    exit();
}

// ===== Dapatkan ID Ruang =====
if (!isset($_GET['id'])) {
    echo "<script>alert('Ruang tidak dijumpai.'); window.location='admin_list_ruang.php';</script>";
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM ruang_fizikal WHERE id='$id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<script>alert('Ruang tidak dijumpai.'); window.location='admin_list_ruang.php';</script>";
    exit();
}

$row = $result->fetch_assoc();

// ===== Proses Kemaskini =====
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_ruang = $_POST['nama_ruang'];
    $lokasi = $_POST['lokasi'];
    $kemudahan = $_POST['kemudahan'];
    $kadar_sewa = $_POST['kadar_sewa'];
    $status = $_POST['status'];
    $gambar_lama = $_POST['gambar_lama'];

    // Jika admin muat naik gambar baru
    if (!empty($_FILES["gambar"]["name"])) {
        $target_dir = "uploads/";
        $gambar_name = basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $gambar_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);

        if ($check !== false) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                // Padam gambar lama
                if (file_exists("uploads/" . $gambar_lama)) {
                    unlink("uploads/" . $gambar_lama);
                }
            } else {
                echo "<script>alert('Ralat semasa memuat naik gambar baru.');</script>";
                $gambar_name = $gambar_lama;
            }
        } else {
            echo "<script>alert('Fail yang dimuat naik bukan gambar.');</script>";
            $gambar_name = $gambar_lama;
        }
    } else {
        $gambar_name = $gambar_lama;
    }

    $update_sql = "UPDATE ruang_fizikal 
                   SET nama_ruang='$nama_ruang', lokasi='$lokasi', kemudahan='$kemudahan', 
                       kadar_sewa='$kadar_sewa', status='$status', gambar='$gambar_name' 
                   WHERE id='$id'";

    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Ruang berjaya dikemaskini!'); window.location='admin_list_ruang.php';</script>";
    } else {
        echo "<script>alert('Ralat: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Ruang Fizikal | Sistem Usahawan Pahang</title>
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
    input[type="number"],
    select,
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

    input[type="file"] {
      margin-top: 6px;
    }

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

    img.preview {
      width: 150px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
      margin-top: 10px;
      border: 1px solid #ccc;
    }

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
  <a href="tambah_berita.php">Tambah Berita</a>
  <a href="admin_tambah_ruang.php" class="active">Tambah Ruang</a> 
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

<!-- Main Content -->
<div class="main-content">
  <header>
    <h1>Edit Ruang Fizikal</h1>
    <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
    <button class="menu-toggle">☰</button>
  </header>

  <section class="form-section">
    <h2>Kemaskini Maklumat Ruang</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Nama Ruang</label>
      <input type="text" name="nama_ruang" value="<?= $row['nama_ruang'] ?>" required>

      <label>Lokasi</label>
      <input type="text" name="lokasi" value="<?= $row['lokasi'] ?>" required>

      <label>Kemudahan</label>
      <textarea name="kemudahan" required><?= $row['kemudahan'] ?></textarea>

      <label>Kadar Sewa (RM)</label>
      <input type="number" step="0.01" name="kadar_sewa" value="<?= $row['kadar_sewa'] ?>" required>

      <label>Status</label>
      <select name="status" required>
        <option value="Tersedia" <?= $row['status'] == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
        <option value="Disewa" <?= $row['status'] == 'Disewa' ? 'selected' : '' ?>>Disewa</option>
      </select>

      <label>Gambar Semasa</label><br>
      <img src="uploads/<?= $row['gambar'] ?>" class="preview" alt="Gambar Ruang">
      <input type="hidden" name="gambar_lama" value="<?= $row['gambar'] ?>">

      <label>Kemas Kini Gambar (pilihan)</label>
      <input type="file" name="gambar" accept="image/*">

      <button type="submit" class="submit-btn">Kemaskini Ruang</button>
    </form>
  </section>

  <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
</div>

<script>
  const toggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  toggle.addEventListener('click', () => sidebar.classList.toggle('active'));
</script>

</body>
</html>
