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

// Jika ada request untuk update status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"], $_POST["status"])) {
    $id = intval($_POST["id"]);
    $status = $conn->real_escape_string($_POST["status"]);
    $conn->query("UPDATE permohonan_itekad SET status='$status' WHERE id=$id");
    echo "success";
    exit;
}

// ===== Pagination Setup =====
$limit = 15; // jumlah data per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Ambil jumlah keseluruhan data
$total_result = $conn->query("SELECT COUNT(*) AS total FROM permohonan_itekad");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Ambil data mengikut halaman
$sql = "SELECT * FROM permohonan_itekad ORDER BY tarikh_permohonan DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Permohonan iTekad - Sistem Usahawan Pahang</title>
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
    .status-select {
      padding: 5px 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 0.9rem;
    }
    .update-btn {
      background: var(--secondary);
      color: white;
      border: none;
      padding: 6px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: 0.3s;
    }
    .update-btn:hover {
      background: #00264d;
    }

    /* ===== Pagination ===== */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 25px;
      flex-wrap: wrap;
    }
    .pagination a {
      color: var(--primary);
      border: 1px solid var(--primary);
      padding: 8px 14px;
      margin: 3px;
      text-decoration: none;
      border-radius: 6px;
      font-size: 0.9rem;
      transition: 0.3s;
    }
    .pagination a.active {
      background: var(--primary);
      color: white;
    }
    .pagination a:hover {
      background: var(--secondary);
      color: white;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      color: #666;
      font-size: 0.9rem;
    }

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
  <div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="senarai_usahawan.php">Senarai Usahawan</a>
    <a href="admin_agro.php">Permohonan Agro</a>
    <a href="admin_ipush.php">Permohonan iPush</a>
    <a href="admin_itekad.php" class="active">Permohonan iTekad</a>
    <a href="tambah_berita.php">Tambah Berita</a>
    <a href="admin_tambah_ruang.php">Tambah Ruang</a>
    <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
    <a href="admin_order.php">Pengurusan Pesanan</a>
    <a href="logout_admin.php">Log Keluar</a>
  </div>

  <div class="main-content">
    <header>
      <h1>Senarai Permohonan i-Tekad</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    </header>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>IC</th>
            <th>Telefon</th>
            <th>Alamat</th>
            <th>Kategori</th>
            <th>Jumlah (RM)</th>
            <th>Tujuan</th>
            <th>Dokumen</th>
            <th>Tarikh Permohonan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $row["id"]; ?></td>
                <td><?= htmlspecialchars($row["nama"]); ?></td>
                <td><?= htmlspecialchars($row["ic"]); ?></td>
                <td><?= htmlspecialchars($row["telefon"]); ?></td>
                <td><?= htmlspecialchars($row["alamat"]); ?></td>
                <td><?= htmlspecialchars($row["kategori"]); ?></td>
                <td><?= number_format($row["jumlah"], 2); ?></td>
                <td><?= htmlspecialchars($row["tujuan"]); ?></td>
                <td>
                  <?php if ($row["dokumen"]): ?>
                    <a href="uploads/<?= htmlspecialchars($row["dokumen"]); ?>" target="_blank">Lihat</a>
                  <?php else: ?>
                    Tiada
                  <?php endif; ?>
                </td>
                <td><?= $row["tarikh_permohonan"]; ?></td>
                <td>
                  <select class="status-select" id="status_<?= $row["id"]; ?>">
                    <?php
                      $status_list = ["Sedang Diproses","Permohonan Diterima","Permohonan Ditolak","Selesai"];
                      foreach ($status_list as $status) {
                        $selected = ($row["status"] == $status) ? "selected" : "";
                        echo "<option value='$status' $selected>$status</option>";
                      }
                    ?>
                  </select>
                  <button class="update-btn" onclick="updateStatus(<?= $row['id']; ?>)">Kemas Kini</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="11" style="text-align:center;">Tiada permohonan direkodkan.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- ===== Pagination Buttons ===== -->
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>">← Sebelumnya</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
          <a href="?page=<?= $page + 1 ?>">Seterusnya →</a>
        <?php endif; ?>
      </div>
    </div>

    <footer>
      © 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi
    </footer>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    function updateStatus(id) {
      const status = document.getElementById('status_' + id).value;
      const formData = new FormData();
      formData.append('id', id);
      formData.append('status', status);
      fetch('', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(res => {
          if (res.trim() === 'success') {
            alert('Status berjaya dikemas kini!');
          } else {
            alert('Gagal mengemas kini status.');
          }
        })
        .catch(() => alert('Ralat rangkaian!'));
    }
  </script>
</body>
</html>
