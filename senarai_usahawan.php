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
// ====== Sambungan Database ======
include "connection.php";

if ($conn->connect_error) {
  die("Sambungan gagal: " . $conn->connect_error);
}

// ====== Pagination Settings ======
$limit = 15; // jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// ====== Kira jumlah data ======
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM usahawan");
$totalData = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

// ====== Ambil data mengikut halaman ======
$sql = "SELECT * FROM usahawan ORDER BY id DESC LIMIT $start, $limit";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Senarai Usahawan | Admin Sistem Usahawan Pahang</title>
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
      min-height: 100vh;
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
      z-index: 1000;
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
      width: 100%;
      box-sizing: border-box;
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
      flex-wrap: wrap;
    }
    header h1 {
      color: var(--primary);
      font-size: 1.3rem;
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

    /* ===== Table ===== */
    .table-container {
      background: var(--white);
      padding: 25px;
      border-radius: 14px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
      word-wrap: break-word;
    }
    th {
      background: #e9ecef;
      color: #003366;
      text-transform: uppercase;
      font-size: 0.85rem;
    }
    td {
      color: #333;
      font-size: 0.9rem;
    }
    tr:hover td {
      background: #f7f9fc;
    }

    .status {
      padding: 6px 10px;
      border-radius: 6px;
      color: white;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .status.yes { background: #28a745; }
    .status.no { background: #dc3545; }

    /* ===== Pagination ===== */
    .pagination {
      margin-top: 20px;
      text-align: center;
    }
    .pagination a {
      color: var(--primary);
      padding: 8px 14px;
      text-decoration: none;
      margin: 0 3px;
      border: 1px solid var(--secondary);
      border-radius: 6px;
      transition: all 0.3s ease;
      font-size: 0.9rem;
    }
    .pagination a:hover {
      background: var(--secondary);
      color: white;
    }
    .pagination a.active {
      background: var(--secondary);
      color: white;
      font-weight: bold;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      color: #666;
      font-size: 0.9rem;
    }

    /* ===== Responsive ===== */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .menu-toggle {
        display: block;
        margin-left: auto;
      }
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
    }

  </style>
</head>
<body>

  <!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <h2>Admin Panel</h2>
  <a href="admin_dashboard.php">Dashboard</a>
  <a href="senarai_usahawan.php" class="active">Senarai Usahawan</a>
  <a href="admin_agro.php">Permohonan Agro</a>
  <a href="admin_ipush.php">Permohonan iPush</a>
  <a href="admin_itekad.php">Permohonan iTekad</a>
  <a href="tambah_berita.php">Tambah Berita</a>
  <a href="admin_tambah_ruang.php">Tambah Ruang</a>
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

  <!-- ===== Main Content ===== -->
  <div class="main-content">
    <header>
      <h1>Senarai Usahawan Berdaftar</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </header>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Telefon</th>
            <th>Perniagaan</th>
            <th>Jenis</th>
            <th>Alamat</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $statusClass = strtolower($row['status']) === 'yes' ? 'yes' : 'no';
              echo "<tr>
                      <td>{$row['id']}</td>
                      <td>{$row['nama']}</td>
                      <td>{$row['email']}</td>
                      <td>{$row['telefon']}</td>
                      <td>{$row['perniagaan']}</td>
                      <td>{$row['jenis']}</td>
                      <td>{$row['alamat']}</td>
                      <td><span class='status {$statusClass}'>{$row['status']}</span></td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='8' style='text-align:center;'>Tiada data usahawan ditemui.</td></tr>";
          }
          ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <?php
        if ($page > 1) {
          echo "<a href='?page=" . ($page - 1) . "'>Sebelumnya</a>";
        }

        for ($i = 1; $i <= $totalPages; $i++) {
          $active = ($i == $page) ? "active" : "";
          echo "<a class='$active' href='?page=$i'>$i</a>";
        }

        if ($page < $totalPages) {
          echo "<a href='?page=" . ($page + 1) . "'>Seterusnya</a>";
        }
        ?>
      </div>
    </div>

    <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("active");
    }
  </script>

</body>
</html>
<?php $conn->close(); ?>
