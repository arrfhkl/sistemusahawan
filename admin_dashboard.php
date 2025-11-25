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
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistem_usahawan_pahang";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// ====== Statistik ======
$total_usahawan = $conn->query("SELECT COUNT(*) AS total FROM usahawan")->fetch_assoc()['total'];
$total_agro = $conn->query("SELECT COUNT(*) AS total FROM permohonan_agro")->fetch_assoc()['total'];
$total_ipush = $conn->query("SELECT COUNT(*) AS total FROM permohonan_ipush")->fetch_assoc()['total'];
$total_itekad = $conn->query("SELECT COUNT(*) AS total FROM permohonan_itekad")->fetch_assoc()['total'];

// ====== Gabungkan semua permohonan terkini (hanya yang belum ada status) ======
$sql = "
  SELECT nama, ic, kategori, tarikh_permohonan, 'Agro' AS skim 
  FROM permohonan_agro 
  WHERE status IS NULL OR status = ''
  UNION ALL
  SELECT nama, ic, kategori, tarikh_permohonan, 'iPush' AS skim 
  FROM permohonan_ipush 
  WHERE status IS NULL OR status = ''
  UNION ALL
  SELECT nama, ic, kategori, tarikh_permohonan, 'iTekad' AS skim 
  FROM permohonan_itekad 
  WHERE status IS NULL OR status = ''
  ORDER BY tarikh_permohonan DESC
  LIMIT 5
";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Sistem Usahawan Pahang</title>
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
/* ===== Dashboard Cards ===== */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 25px;
  margin-bottom: 40px;
}
.card {
  background: var(--white);
  border-radius: 14px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  text-align: center;
  transition: all 0.3s ease;
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.card h3 {
  font-size: 1rem;
  color: var(--primary);
  margin-bottom: 8px;
}
.card p {
  font-size: 2rem;
  font-weight: 700;
  color: var(--secondary);
  margin: 0;
}
.card.usahawan { border-top: 4px solid #007bff; }
.card.agro { border-top: 4px solid #2831a7ff; }
.card.ipush { border-top: 4px solid #0741ffff; }
.card.itekad { border-top: 4px solid #426ac1ff; }
/* ===== Recent Applications ===== */
.recent-app {
  background: var(--white);
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.recent-app h2 {
  font-size: 1.2rem;
  color: var(--primary);
  margin-bottom: 15px;
}
.table-container {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}
th {
  background: #e9ecef;
  color: #003366;
  font-size: 0.9rem;
}
td {
  font-size: 0.9rem;
  color: #333;
}
tr:hover td {
  background: #f7f9fc;
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
  <a href="admin_dashboard.php" class="active">Dashboard</a>
  <a href="senarai_usahawan.php">Senarai Usahawan</a>
  <a href="admin_agro.php">Permohonan Agro</a>
  <a href="admin_ipush.php">Permohonan iPush</a>
  <a href="admin_itekad.php">Permohonan iTekad</a>
  <a href="tambah_berita.php">Tambah Berita</a>
  <a href="admin_tambah_ruang.php">Tambah Ruang</a>
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="admin_order.php">Pesanan Pengguna</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

<!-- Main Content -->
<div class="main-content">
  <header>
    <h1>Dashboard Admin</h1>
    <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
    <button class="menu-toggle" id="menu-toggle">☰</button>
  </header>

  <!-- Statistik Kad -->
  <section class="stats">
    <div class="card usahawan">
      <h3>Jumlah Usahawan Berdaftar</h3>
      <p><?= $total_usahawan ?></p>
    </div>
    <div class="card agro">
      <h3>Permohonan AGRO</h3>
      <p><?= $total_agro ?></p>
    </div>
    <div class="card ipush">
      <h3>Permohonan iPush</h3>
      <p><?= $total_ipush ?></p>
    </div>
    <div class="card itekad">
      <h3>Permohonan iTekad</h3>
      <p><?= $total_itekad ?></p>
    </div>
  </section>

  <!-- Senarai Permohonan Terkini -->
  <section class="recent-app">
    <h2>Permohonan Terkini (Belum Diproses)</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>No. IC</th>
            <th>Kategori</th>
            <th>Skim</th>
            <th>Tarikh Permohonan</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['ic']) ?></td>
                <td><?= htmlspecialchars($row['kategori']) ?></td>
                <td><?= htmlspecialchars($row['skim']) ?></td>
                <td><?= htmlspecialchars($row['tarikh_permohonan']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
              <tr><td colspan="5" style="text-align:center;">Tiada permohonan baru setakat ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer>© 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi</footer>
</div>

<script>
document.getElementById('menu-toggle').onclick = function() {
  document.getElementById('sidebar').classList.toggle('active');
};
</script>

</body>
</html>
