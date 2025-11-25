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

// ===== Padam Berita =====
if (isset($_GET['padam'])) {
    $id = intval($_GET['padam']);
    $sql_gambar = "SELECT imej FROM berita WHERE id = $id";
    $result = $conn->query($sql_gambar);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (file_exists($row['imej'])) {
            unlink($row['imej']); // padam imej
        }
    }

    $conn->query("DELETE FROM berita WHERE id = $id");
    echo "<script>alert('Berita berjaya dipadam!'); window.location.href='senarai_berita.php';</script>";
    exit();
}

// ===== Dapatkan Semua Berita =====
$sql = "SELECT * FROM berita ORDER BY tarikh DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Senarai Berita | Sistem Usahawan Pahang</title>
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

    /* ===== Senarai Berita ===== */
    .berita-container {
      background: var(--white);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .berita-card {
      display: flex;
      align-items: center;
      border-bottom: 1px solid #eee;
      padding: 15px 0;
    }

    .berita-card:last-child {
      border-bottom: none;
    }

    .berita-card img {
      width: 90px;
      height: 90px;
      border-radius: 8px;
      object-fit: cover;
      margin-right: 20px;
    }

    .berita-info {
      flex: 1;
    }

    .berita-info h3 {
      margin: 0;
      color: var(--primary);
      font-size: 1.05rem;
    }

    .berita-info p {
      margin: 5px 0;
      color: #555;
      font-size: 0.9rem;
    }

    .delete-btn {
      background: #cc0000;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .delete-btn:hover {
      background: #990000;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      color: #666;
      font-size: 0.9rem;
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
      .berita-card {
        flex-direction: column;
        align-items: flex-start;
      }
      .berita-card img {
        margin-bottom: 10px;
      }
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
    <a href="logout_admin.php">Log Keluar</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <header>
      <h1>Senarai Berita</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle">☰</button>
    </header>

    <div class="berita-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="berita-card">
            <img src="<?= $row['imej'] ?>" alt="Imej Berita">
            <div class="berita-info">
              <h3><?= htmlspecialchars($row['tajuk']) ?></h3>
              <p><b>Tarikh:</b> <?= htmlspecialchars($row['tarikh']) ?></p>
              <p><?= nl2br(substr($row['kandungan'], 0, 120)) ?>...</p>
            </div>
            <form method="GET" onsubmit="return confirm('Padam berita ini?')">
              <input type="hidden" name="padam" value="<?= $row['id'] ?>">
              <button type="submit" class="delete-btn">Padam</button>
            </form>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center; color:#555;">Tiada berita direkodkan buat masa ini.</p>
      <?php endif; ?>
    </div>

    <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
  </div>

  <script>
    // Toggle sidebar untuk mobile
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    toggle.addEventListener('click', () => sidebar.classList.toggle('active'));
  </script>

</body>
</html>
