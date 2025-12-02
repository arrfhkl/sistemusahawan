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
    alert('Sila log masuk terlebih dahulu.');
    window.location.href = 'login_admin.php';
  </script>";
  exit();
}

// ===== Proses Maklum Balas =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
  $id = $_POST['id'];
  $maklum_balas = $conn->real_escape_string($_POST['maklum_balas']);
  $update = "UPDATE tempahan_ruang SET maklum_balas = '$maklum_balas' WHERE id = '$id'";
  if ($conn->query($update) === TRUE) {
    echo "<script>alert('Maklum balas berjaya dihantar.'); window.location.href='admin_tempahan_ruang.php';</script>";
    exit();
  } else {
    echo "<script>alert('Ralat: " . $conn->error . "');</script>";
  }
}

// ===== Dapatkan Senarai Tempahan =====
$sql = "SELECT id, nama_ruang, nama_pemohon, no_ic, telefon, tarikh_tempah, maklum_balas FROM tempahan_ruang ORDER BY tarikh_tempah DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Senarai Tempahan Ruang | Sistem Usahawan Pahang</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
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
      flex-direction: row;
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
      box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
      z-index: 100;
    }

    .sidebar h2 {
      text-align: center;
      padding: 20px 0;
      font-size: 1.2rem;
      margin: 0;
      background: rgba(255, 255, 255, 0.08);
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 14px 20px;
      display: block;
      transition: all 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: rgba(255, 255, 255, 0.2);
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
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
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

    /* ===== Responsive Sidebar ===== */
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
      padding: 25px 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }

    th,
    td {
      text-align: left;
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }

    th {
      background: var(--secondary);
      color: white;
      font-weight: 600;
    }

    tr:hover {
      background: #f0f6ff;
    }

    textarea {
      width: 100%;
      min-height: 60px;
      border-radius: 6px;
      border: 1px solid #ccc;
      padding: 8px;
      resize: vertical;
      font-family: inherit;
    }

    button {
      background: var(--secondary);
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      margin-top: 6px;
      transition: background 0.3s;
    }

    button:hover {
      background: var(--primary);
    }

    .no-data {
      text-align: center;
      padding: 20px;
      font-style: italic;
      color: #666;
    }

    /* ===== Kad versi untuk skrin kecil ===== */
    @media (max-width: 700px) {
      table,
      thead,
      tbody,
      th,
      td,
      tr {
        display: block;
      }

      thead {
        display: none;
      }

      tr {
        margin-bottom: 15px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        padding: 12px;
      }

      td {
        border: none;
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
      }

      td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--primary);
      }
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
    <a href="admin_tambah_ruang.php">Tambah Ruang</a>
    <a href="admin_tempahan_ruang.php" class="active">Tempahan Ruang</a>
    <a href="admin_order.php">Pengurusan Pesanan</a>
    <a href="pending_usahawan.php">Pengesahan Usahawan</a>
    <a href="logout_admin.php">Log Keluar</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <header>
      <h1>Tempahan Ruang Fizikal</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle">☰</button>
    </header>

    <section class="table-section">
      <?php if ($result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Ruang</th>
              <th>Nama Pemohon</th>
              <th>No. IC</th>
              <th>Telefon</th>
              <th>Tarikh Tempah</th>
              <th>Maklum Balas</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td data-label="ID"><?= $row['id'] ?></td>
                <td data-label="Nama Ruang"><?= htmlspecialchars($row['nama_ruang']) ?></td>
                <td data-label="Nama Pemohon"><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                <td data-label="No. IC"><?= htmlspecialchars($row['no_ic']) ?></td>
                <td data-label="Telefon"><?= htmlspecialchars($row['telefon']) ?></td>
                <td data-label="Tarikh Tempah"><?= htmlspecialchars($row['tarikh_tempah']) ?></td>
                <td data-label="Maklum Balas">
                  <form method="POST">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <textarea name="maklum_balas" placeholder="Masukkan maklum balas..."><?= htmlspecialchars($row['maklum_balas']) ?></textarea>
                    <button type="submit">Hantar</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="no-data">Tiada tempahan ruang direkodkan buat masa ini.</p>
      <?php endif; ?>
    </section>

    <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
  </div>

  <script>
    const toggle = document.querySelector(".menu-toggle");
    const sidebar = document.querySelector(".sidebar");
    toggle.addEventListener("click", () => sidebar.classList.toggle("active"));
  </script>
</body>
</html>
