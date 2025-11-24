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

// ===== Ambil Data Ruang =====
$sql = "SELECT * FROM ruang_fizikal ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Senarai Ruang Fizikal | Sistem Usahawan Pahang</title>
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

    /* ===== Table Section ===== */
    .table-section {
      background: var(--white);
      border-radius: 12px;
      padding: 25px 35px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      overflow-x: auto;
    }
    .table-section h2 {
      color: var(--primary);
      text-align: center;
      margin-bottom: 25px;
      font-size: 1.4rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }
    th, td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: var(--secondary);
      color: white;
    }
    tr:hover {
      background: #f2f6fa;
    }
    img {
      width: 80px;
      height: 60px;
      object-fit: cover;
      border-radius: 5px;
    }

    .btn {
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      color: white;
      font-size: 0.85rem;
      margin: 2px;
      display: inline-block;
    }
    .btn-edit {
      background: #ffc107;
    }
    .btn-delete {
      background: #dc3545;
    }
    .btn-edit:hover {
      background: #e0a800;
    }
    .btn-delete:hover {
      background: #c82333;
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
    <h1>Senarai Ruang Fizikal</h1>
    <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
    <button class="menu-toggle">☰</button>
  </header>

  <section class="table-section">
    <h2>Senarai Ruang yang Telah Ditambah</h2>
    <table>
      <tr>
        <th>#</th>
        <th>Nama Ruang</th>
        <th>Lokasi</th>
        <th>Kadar Sewa (RM)</th>
        <th>Status</th>
        <th>Gambar</th>
        <th>Tindakan</th>
      </tr>
      <?php
      if ($result->num_rows > 0) {
        $no = 1;
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
            <td>{$no}</td>
            <td>{$row['nama_ruang']}</td>
            <td>{$row['lokasi']}</td>
            <td>{$row['kadar_sewa']}</td>
            <td>{$row['status']}</td>
            <td><img src='uploads/{$row['gambar']}' alt='Gambar Ruang'></td>
            <td>
              <a href='edit_ruang.php?id={$row['id']}' class='btn btn-edit'>Edit</a>
              <a href='delete_ruang.php?id={$row['id']}' class='btn btn-delete' onclick=\"return confirm('Adakah anda pasti mahu padam ruang ini?');\">Padam</a>
            </td>
          </tr>";
          $no++;
        }
      } else {
        echo "<tr><td colspan='7' style='text-align:center;'>Tiada ruang ditambah setakat ini.</td></tr>";
      }
      ?>
    </table>
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
