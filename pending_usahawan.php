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

// ====== Approve/Reject Usahawan (AJAX) ======
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["id"])) {
    $id = intval($_POST["id"]);
    $action = $_POST["action"];
    
    if ($action === "approve") {
        // Get data from pending_usahawan
        $query = $conn->query("SELECT * FROM pending_usahawan WHERE id=$id");
        if ($query && $row = $query->fetch_assoc()) {
            // Insert into usahawan table
            $stmt = $conn->prepare("INSERT INTO usahawan (nama, ic, perniagaan, jenis, alamat, telefon, email, password, avatar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
            $stmt->bind_param("sssssssss", 
                $row['nama'], 
                $row['ic'], 
                $row['perniagaan'], 
                $row['jenis'], 
                $row['alamat'], 
                $row['telefon'], 
                $row['email'], 
                $row['password'],
                $row['avatar']
            );
            
            if ($stmt->execute()) {
                // Delete from pending_usahawan
                $conn->query("DELETE FROM pending_usahawan WHERE id=$id");
                echo "approved";
            } else {
                echo "error";
            }
            $stmt->close();
        }
    } elseif ($action === "reject") {
        // Update status to rejected
        $conn->query("UPDATE pending_usahawan SET status='ditolak' WHERE id=$id");
        echo "rejected";
    }
    exit;
}

// ====== Pagination Setup ======
$limit = 15;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$start = ($page - 1) * $limit;

// ====== Total Rows ======
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM pending_usahawan WHERE status='pending'");
$totalRows = $totalQuery->fetch_assoc()["total"];
$totalPages = ceil($totalRows / $limit);

// ====== Query Data with Limit ======
$sql = "SELECT * FROM pending_usahawan WHERE status='pending' ORDER BY tarikh_daftar DESC LIMIT $start, $limit";
$result = $conn->query($sql);

// ====== FUNCTION TO GET FILE ICON ======
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch($ext) {
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
        case 'xls':
        case 'xlsx':
            return '📊';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '🖼️';
        case 'zip':
        case 'rar':
            return '📦';
        default:
            return '📎';
    }
}

// ====== FUNCTION TO GET FILE TYPE ======
function getFileType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch($ext) {
        case 'pdf':
            return 'PDF';
        case 'doc':
        case 'docx':
            return 'Word';
        case 'xls':
        case 'xlsx':
            return 'Excel';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'Gambar';
        case 'zip':
        case 'rar':
            return 'Arkib';
        default:
            return 'Fail';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengesahan Akaun Usahawan - Sistem Usahawan Pahang</title>
  <style>
    :root {
      --primary: #003366;
      --secondary: #0055a5;
      --light: #f5f7fa;
      --white: #ffffff;
      --success: #28a745;
      --danger: #dc3545;
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
      overflow-y: auto;
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
    /* ===== Stats Cards ===== */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }
    .stat-card {
      background: var(--white);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.05);
      text-align: center;
    }
    .stat-card h3 {
      margin: 0 0 10px 0;
      color: var(--primary);
      font-size: 2rem;
    }
    .stat-card p {
      margin: 0;
      color: #666;
      font-size: 0.9rem;
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
    .action-btn {
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: 0.3s;
      margin: 0 3px;
    }
    .approve-btn {
      background: var(--success);
    }
    .approve-btn:hover {
      background: #218838;
    }
    .reject-btn {
      background: var(--danger);
    }
    .reject-btn:hover {
      background: #c82333;
    }
    .view-btn {
      background: var(--secondary);
    }
    .view-btn:hover {
      background: #00264d;
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
    }
    .status-pending {
      background: #ffc107;
      color: #000;
    }
    .status-approved {
      background: var(--success);
      color: white;
    }
    .status-rejected {
      background: var(--danger);
      color: white;
    }

    /* ===== Modal ===== */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      overflow-y: auto;
    }
    .modal-content {
      background: var(--white);
      margin: 50px auto;
      padding: 30px;
      border-radius: 10px;
      max-width: 600px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 2px solid #e9ecef;
      padding-bottom: 15px;
    }
    .modal-header h2 {
      margin: 0;
      color: var(--primary);
    }
    .close {
      font-size: 2rem;
      cursor: pointer;
      color: #999;
      transition: 0.3s;
    }
    .close:hover {
      color: #333;
    }
    .detail-row {
      display: flex;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .detail-label {
      font-weight: bold;
      width: 150px;
      color: var(--primary);
    }
    .detail-value {
      flex: 1;
      color: #333;
    }

    /* File Link Styles */
    .file-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      background: #e7f3ff;
      color: var(--primary);
      text-decoration: none;
      border-radius: 6px;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      border: 1px solid #b3d9ff;
    }
    .file-link:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }
    .file-icon {
      font-size: 1.2rem;
    }
    .file-actions {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .download-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      background: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-size: 0.8rem;
      transition: 0.3s;
      border: none;
      cursor: pointer;
    }
    .download-btn:hover {
      background: #218838;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.8);
      overflow: auto;
    }
    .modal-content {
      position: relative;
      margin: 2% auto;
      padding: 0;
      width: 90%;
      max-width: 1200px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 30px rgba(0,0,0,0.3);
    }
    .modal-header {
      padding: 15px 20px;
      background: var(--primary);
      color: white;
      border-radius: 10px 10px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-body {
      padding: 20px;
      max-height: 80vh;
      overflow: auto;
    }
    .close {
      color: white;
      font-size: 2rem;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    .close:hover {
      color: #ff6b6b;
    }
    .modal-body iframe {
      width: 100%;
      height: 70vh;
      border: none;
      border-radius: 5px;
    }
    .modal-body img {
      max-width: 100%;
      height: auto;
      border-radius: 5px;
    }

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
      .modal-content {
        margin: 20px;
        max-width: 90%;
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
  <a href="tambah_berita.php">Tambah Berita</a>
  <a href="admin_tambah_ruang.php">Tambah Ruang</a>
  <a href="admin_tempahan_ruang.php">Tempahan Ruang</a>
  <a href="admin_order.php">Pengurusan Pesanan</a>
  <a href="pending_usahawan.php" class="active">Pengesahan Usahawan</a>
  <a href="logout_admin.php">Log Keluar</a>
</div>

  <div class="main-content">
    <header>
      <h1>Pengesahan Akaun Usahawan</h1>
      <p>Pengguna: <b><?= $_SESSION['admin_username'] ?></b></p>
      <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    </header>

    <!-- Stats Cards -->
    <div class="stats-container">
      <div class="stat-card">
        <h3><?= $totalRows ?></h3>
        <p>Permohonan Menunggu</p>
      </div>
      <div class="stat-card">
        <h3><?php 
          $approved = $conn->query("SELECT COUNT(*) as total FROM usahawan WHERE jenis != 'Pengguna'");
          echo $approved->fetch_assoc()['total'];
        ?></h3>
        <p>Usahawan Aktif</p>
      </div>
      <div class="stat-card">
        <h3><?php 
          $rejected = $conn->query("SELECT COUNT(*) as total FROM pending_usahawan WHERE status='ditolak'");
          echo $rejected->fetch_assoc()['total'];
        ?></h3>
        <p>Permohonan Ditolak</p>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>No. IC</th>
            <th>Telefon</th>
            <th>Email</th>
            <th>Perniagaan</th>
            <th>Jenis</th>
            <th>Tarikh Daftar</th>
            <th>SSM</th>
            <th>Status</th>
            <th>Tindakan</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr id="row_<?= $row["id"]; ?>">
                <td><?= $row["id"]; ?></td>
                <td><?= htmlspecialchars($row["nama"]); ?></td>
                <td><?= htmlspecialchars($row["ic"]); ?></td>
                <td><?= htmlspecialchars($row["telefon"]); ?></td>
                <td><?= htmlspecialchars($row["email"]); ?></td>
                <td><?= htmlspecialchars($row["perniagaan"]); ?></td>
                <td><?= htmlspecialchars($row["jenis"]); ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row["tarikh_daftar"])); ?></td>

                <td>
                <?php if (!empty($row['ssm_file'])): ?>
                  <div class="file-actions">

                    <!-- LIHAT SSM -->
                    <a href="#"
                      class="file-link"
                      onclick="viewDocument(
                        'uploads/ssm/<?= htmlspecialchars($row['ssm_file']); ?>',
                        '<?= htmlspecialchars($row['ssm_file']); ?>'
                      ); return false;">
                      
                      <span class="file-icon">
                        <?= getFileIcon($row['ssm_file']); ?>
                      </span>
                      <span>Lihat <?= getFileType($row['ssm_file']); ?></span>
                    </a>

                    <!-- MUAT TURUN -->
                    <a href="uploads/ssm/<?= htmlspecialchars($row['ssm_file']); ?>"
                      class="download-btn"
                      download>
                      ⬇ Muat Turun
                    </a>

                  </div>
                <?php else: ?>
                  <span style="color:#999;">Tiada Dokumen</span>
                <?php endif; ?>
                </td>


                  <span class="status-badge status-pending">Menunggu</span>
                </td>
                <td>
                  <button class="action-btn view-btn" onclick="viewDetails(<?= htmlspecialchars(json_encode($row)); ?>)">Lihat</button>
                  <button class="action-btn approve-btn" onclick="approveUser(<?= $row['id']; ?>)">✓ Lulus</button>
                  <button class="action-btn reject-btn" onclick="rejectUser(<?= $row['id']; ?>)">✗ Tolak</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="10" style="text-align:center;">Tiada permohonan menunggu pengesahan.</td></tr>
                    <?php endif; ?>

                    <div id="documentModal" class="modal">
            <div class="modal-content">
              <div class="modal-header">
                <h2 id="documentTitle">Dokumen</h2>
                <span class="close" onclick="closeModal()">&times;</span>
              </div>
              <div class="modal-body" id="documentBody"></div>
            </div>
          </div>

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

    <footer>
      © 2025 Sistem Usahawan Pahang | Dibangunkan untuk Kegunaan Rasmi
    </footer>
  </div>

  <!-- Modal for User Details -->
  <div id="detailModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Maklumat Usahawan</h2>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <div id="modalBody"></div>
    </div>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    function closeModal() {
      document.getElementById('detailModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('detailModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    function approveUser(id) {
      if (!confirm('Adakah anda pasti untuk meluluskan permohonan ini?')) return;
      
      const formData = new FormData();
      formData.append('id', id);
      formData.append('action', 'approve');
      
      fetch('', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(res => {
          if (res.trim() === 'approved') {
            alert('Permohonan berjaya diluluskan! Usahawan boleh log masuk sekarang.');
            document.getElementById('row_' + id).remove();
            location.reload();
          } else {
            alert('Gagal meluluskan permohonan. Sila cuba lagi.');
          }
        })
        .catch(() => alert('Ralat rangkaian!'));
    }

    function rejectUser(id) {
      if (!confirm('Adakah anda pasti untuk menolak permohonan ini?')) return;
      
      const formData = new FormData();
      formData.append('id', id);
      formData.append('action', 'reject');
      
      fetch('', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(res => {
          if (res.trim() === 'rejected') {
            alert('Permohonan telah ditolak.');
            document.getElementById('row_' + id).remove();
            location.reload();
          } else {
            alert('Gagal menolak permohonan. Sila cuba lagi.');
          }
        })
        .catch(() => alert('Ralat rangkaian!'));
    }

    function viewDocument(filepath, filename) {
      const modal = document.getElementById('documentModal');
      const documentBody = document.getElementById('documentBody');
      const documentTitle = document.getElementById('documentTitle');

      documentTitle.textContent = filename;
      documentBody.innerHTML = '';

      const ext = filename.split('.').pop().toLowerCase();

      if (['jpg','jpeg','png','gif','bmp'].includes(ext)) {
        documentBody.innerHTML = `<img src="${filepath}" alt="${filename}">`;
      } 
      else if (ext === 'pdf') {
        documentBody.innerHTML = `<iframe src="${filepath}"></iframe>`;
      } 
      else if (['doc','docx','xls','xlsx','ppt','pptx'].includes(ext)) {
        documentBody.innerHTML = `
          <iframe src="https://docs.google.com/viewer?url=
          ${encodeURIComponent(window.location.origin + '/' + filepath)}
          &embedded=true"></iframe>`;
      } 
      else {
        documentBody.innerHTML = `
          <div style="text-align:center;padding:40px">
            <p style="font-size:3rem">📄</p>
            <p>Fail ini tidak boleh dipaparkan.</p>
            <a href="${filepath}" download class="download-btn">
              ⬇ Muat Turun
            </a>
          </div>`;
      }

      modal.style.display = 'block';
    }

    function closeModal() {
      document.getElementById('documentModal').style.display = 'none';
    }

    window.onclick = function(e) {
      const modal = document.getElementById('documentModal');
      if (e.target === modal) modal.style.display = 'none';
    }

  </script>

</body>
</html>
<?php $conn->close(); ?>