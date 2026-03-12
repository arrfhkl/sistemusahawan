<?php
include "connection.php";
include "header.php";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Sambungan gagal: " . $conn->connect_error); }

// ===== Dapatkan ID produk =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Ralat: Produk tidak dijumpai.");
}

// ===== Dapatkan maklumat produk =====
$sql = "SELECT * FROM produk WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Ralat: Produk tidak dijumpai.");
}
$produk = $result->fetch_assoc();

/* ===============================
   AMBIL GALLERY PRODUK
================================ */
$gallery = $conn->query("
SELECT * 
FROM produk_gallery 
WHERE produk_id = $id
ORDER BY id ASC
");

// ===== Proses kemaskini data =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $conn->real_escape_string($_POST['nama']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $harga = $conn->real_escape_string($_POST['harga']);
    $gambar_baru = $produk['gambar_url'];

    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = "uploads/";
        $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowTypes = ['jpg','jpeg','png','gif','webp'];
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
                $gambar_baru = $fileName;
            }
        }
    }

    $update = $conn->query("
        UPDATE produk 
        SET nama='$nama', deskripsi='$deskripsi', harga='$harga', gambar_url='$gambar_baru'
        WHERE id=$id
    ");

    if ($update) {
<<<<<<< HEAD

    if ($update) {

    /* ===============================
       UPLOAD GALLERY BARU
    ================================ */
    if (!empty($_FILES['gallery']['name'][0])) {

        $targetDir = "uploads/";

        foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {

            $fileName = time().'_'.$key.'_'.basename($_FILES['gallery']['name'][$key]);
            $targetFile = $targetDir . $fileName;

            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowTypes = ['jpg','jpeg','png','gif','webp'];

            if (in_array($ext,$allowTypes)) {

                if (move_uploaded_file($tmp_name,$targetFile)) {

                    $conn->query("
                        INSERT INTO produk_gallery (produk_id,gambar_url,is_primary)
                        VALUES ($id,'$fileName',0)
                    ");

                  }
              }
          }
      }

      // Redirect selepas semua siap
      echo "<script>alert('Produk berjaya dikemaskini!'); 
      window.location.href='profil_usahawan.php?id=" . $produk['usahawan_id'] . "';</script>";
      exit;
  }
=======
        if (!empty($_FILES['gallery']['name'][0])) {
            $targetDir = "uploads/";
            foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {
                $fileName = time().'_'.$key.'_'.basename($_FILES['gallery']['name'][$key]);
                $targetFile = $targetDir . $fileName;
                $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowTypes = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext,$allowTypes)) {
                    if (move_uploaded_file($tmp_name,$targetFile)) {
                        $conn->query("INSERT INTO produk_gallery (produk_id,gambar_url,is_primary) VALUES ($id,'$fileName',0)");
                    }
                }
            }
        }
        echo "<script>showSuccessModal();</script>";
        exit;
>>>>>>> ec6320a04db5bb0add4e3bbc66ec5113f3dcb48d
    } else {
        echo "<script>showErrorModal();</script>";
    }
}
<<<<<<< HEAD

    /* ===============================
    DELETE GAMBAR GALLERY
  ================================ */
  if (isset($_GET['delete_gallery'])) {

      $gid = (int)$_GET['delete_gallery'];

      $g = $conn->query("SELECT gambar_url FROM produk_gallery WHERE id=$gid");
      $row = $g->fetch_assoc();

      if ($row) {

          $file = "uploads/".$row['gambar_url'];

          if (file_exists($file)) {
              unlink($file);
          }

          $conn->query("DELETE FROM produk_gallery WHERE id=$gid");
      }

      header("Location: edit_produk.php?id=$id");
      exit;
  }
  ?>
=======
>>>>>>> ec6320a04db5bb0add4e3bbc66ec5113f3dcb48d

/* ===============================
   DELETE GAMBAR GALLERY
================================ */
if (isset($_GET['delete_gallery'])) {
    $gid = (int)$_GET['delete_gallery'];
    $g = $conn->query("SELECT gambar_url FROM produk_gallery WHERE id=$gid");
    $row = $g->fetch_assoc();
    if ($row) {
        $file = "uploads/".$row['gambar_url'];
        if (file_exists($file)) { unlink($file); }
        $conn->query("DELETE FROM produk_gallery WHERE id=$gid");
    }
    header("Location: edit_produk.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Produk - <?= htmlspecialchars($produk['nama']) ?></title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --navy: #001F3F;
      --blue: #003399;
      --accent: #0055cc;
      --gold: #c9a84c;
      --gold-light: #f5d98a;
      --success: #16a34a;
      --success-bg: #dcfce7;
      --danger: #dc2626;
      --danger-bg: #fee2e2;
      --warning: #d97706;
      --warning-bg: #fef3c7;
      --surface: #ffffff;
      --surface2: #f8fafc;
      --border: #e2e8f0;
      --text: #1e293b;
      --text-muted: #64748b;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
      --shadow-lg: 0 8px 40px rgba(0,0,0,0.14);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f0f4f8;
      color: var(--text);
      min-height: 100vh;
      margin-top: 78px;
      position: relative;
    }

    /* Watermark */
    body::after {
      content: "";
      position: fixed;
      inset: 0;
      background-image: url("assets/img/jatapahang.png");
      background-repeat: repeat;
      background-size: 160px;
      opacity: 0.04;
      z-index: -1;
      pointer-events: none;
    }

    /* ===== PAGE LAYOUT ===== */
    .page-wrapper {
      max-width: 760px;
      margin: 36px auto 60px;
      padding: 0 16px;
    }

    /* ===== BREADCRUMB ===== */
    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-bottom: 20px;
    }
    .breadcrumb a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 0.7rem; color: #94a3b8; }

    /* ===== CARD ===== */
    .card {
      background: var(--surface);
      border-radius: 16px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .card-header {
      background: linear-gradient(135deg, var(--navy), var(--blue));
      padding: 20px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .card-header .icon-wrap {
      width: 44px; height: 44px;
      background: rgba(255,255,255,0.15);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
      color: var(--gold-light);
    }
    .card-header h2 {
      color: #fff;
      font-size: 1.2rem;
      font-weight: 700;
    }
    .card-header p {
      color: rgba(255,255,255,0.65);
      font-size: 0.8rem;
      margin-top: 2px;
    }

    .card-body { padding: 28px; }

    /* ===== FORM ELEMENTS ===== */
    .form-group { margin-bottom: 22px; }
    .form-group label {
      display: flex;
      align-items: center;
      gap: 7px;
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--navy);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .form-group label i { color: var(--accent); font-size: 0.85rem; }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 0.95rem;
      color: var(--text);
      background: var(--surface2);
      transition: all 0.2s;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(0,85,204,0.1);
    }
    .form-group textarea { resize: vertical; min-height: 100px; }

    /* Price input wrapper */
    .price-wrap { position: relative; }
    .price-wrap .prefix {
      position: absolute;
      left: 14px; top: 50%;
      transform: translateY(-50%);
      font-weight: 700;
      color: var(--accent);
      font-size: 0.95rem;
    }
    .price-wrap input { padding-left: 46px !important; }

    /* ===== DIVIDER ===== */
    .divider {
      border: none;
      border-top: 2px dashed var(--border);
      margin: 28px 0;
    }

    /* ===== IMAGE UPLOAD ZONE ===== */
    .upload-zone {
      border: 2.5px dashed #c0cfe8;
      border-radius: 14px;
      padding: 28px 20px;
      text-align: center;
      cursor: pointer;
      background: linear-gradient(135deg, #f0f6ff, #f8faff);
      transition: all 0.25s;
      position: relative;
    }
    .upload-zone:hover, .upload-zone.drag-over {
      border-color: var(--accent);
      background: #eef4ff;
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0,85,204,0.12);
    }
    .upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .upload-zone .upload-icon {
      font-size: 2.5rem;
      color: #93b4d8;
      margin-bottom: 10px;
      transition: transform 0.2s;
    }
    .upload-zone:hover .upload-icon { transform: scale(1.1); color: var(--accent); }
    .upload-zone h4 { font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    .upload-zone p { font-size: 0.8rem; color: var(--text-muted); }
    .upload-zone .badge-types {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-top: 10px;
    }
    .upload-zone .badge-types span {
      background: #e0ecff;
      color: var(--accent);
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 0.72rem;
      font-weight: 700;
    }

    /* ===== IMAGE PREVIEW SECTION ===== */
    .preview-section {
      margin-top: 20px;
    }
    .preview-label {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .preview-label::before, .preview-label::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Current image */
    .current-img-wrap {
      position: relative;
      display: inline-block;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      border: 3px solid var(--border);
      transition: border-color 0.2s;
    }
    .current-img-wrap.replaced {
      border-color: var(--danger);
      opacity: 0.5;
    }
    .current-img-wrap.replaced::after {
      content: "DIGANTI";
      position: absolute;
      inset: 0;
      background: rgba(220,38,38,0.7);
      color: #fff;
      font-weight: 800;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      justify-content: center;
      letter-spacing: 1px;
    }
    .current-img-wrap img,
    .new-preview-wrap img {
      width: 160px;
      height: 160px;
      object-fit: cover;
      display: block;
    }

    .new-preview-wrap {
      position: relative;
      display: inline-block;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      border: 3px solid var(--success);
    }
    .new-preview-wrap .new-badge {
      position: absolute;
      top: 8px; left: 8px;
      background: var(--success);
      color: #fff;
      font-size: 0.68rem;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 20px;
      letter-spacing: 0.5px;
    }

    .preview-arrow {
      font-size: 1.6rem;
      color: var(--accent);
      display: flex;
      align-items: center;
    }

    .img-compare {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-top: 12px;
    }

    /* ===== GALLERY GRID ===== */
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .gallery-item {
      position: relative;
      border-radius: 10px;
      overflow: visible;
    }
    .gallery-item img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      border-radius: 10px;
      border: 2px solid var(--border);
      display: block;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .gallery-item:hover img {
      transform: scale(1.03);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .gallery-item .del-btn {
      position: absolute;
      top: -7px; right: -7px;
      background: var(--danger);
      color: #fff;
      border-radius: 50%;
      width: 24px; height: 24px;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem;
      text-decoration: none;
      font-weight: 800;
      box-shadow: 0 2px 8px rgba(220,38,38,0.4);
      transition: transform 0.15s, box-shadow 0.15s;
      z-index: 2;
    }
    .gallery-item .del-btn:hover {
      transform: scale(1.15);
      box-shadow: 0 4px 12px rgba(220,38,38,0.5);
    }

    /* New gallery preview */
    .gallery-preview-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }
    .gallery-preview-item {
      position: relative;
      border-radius: 10px;
      overflow: hidden;
      border: 2px solid var(--success);
    }
    .gallery-preview-item img {
      width: 90px;
      height: 90px;
      object-fit: cover;
      display: block;
    }
    .gallery-preview-item .new-badge {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      background: rgba(22,163,74,0.85);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      text-align: center;
      padding: 3px;
    }

    /* ===== SUBMIT BUTTON ===== */
    .btn-submit {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, var(--navy), var(--blue));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.25s;
      margin-top: 8px;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 16px rgba(0,51,153,0.3);
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,51,153,0.4);
    }
    .btn-submit:active { transform: translateY(0); }

    /* ===== MODAL ===== */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(6px);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
    }
    .modal-overlay.show {
      opacity: 1;
      pointer-events: all;
    }

    .modal {
      background: #fff;
      border-radius: 20px;
      padding: 0;
      max-width: 440px;
      width: 90%;
      box-shadow: 0 24px 80px rgba(0,0,0,0.25);
      transform: scale(0.85) translateY(20px);
      transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
      overflow: hidden;
    }
    .modal-overlay.show .modal {
      transform: scale(1) translateY(0);
    }

    .modal-header {
      padding: 28px 28px 20px;
      text-align: center;
    }
    .modal-icon {
      width: 72px; height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 16px;
    }
    .modal-icon.confirm { background: #fff8e7; color: var(--warning); border: 3px solid #fde68a; }
    .modal-icon.success { background: var(--success-bg); color: var(--success); border: 3px solid #86efac; }
    .modal-icon.danger  { background: var(--danger-bg); color: var(--danger); border: 3px solid #fca5a5; }
    .modal-icon.delete  { background: #fff1f2; color: var(--danger); border: 3px solid #fca5a5; }

    .modal h3 {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 8px;
    }
    .modal p {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.6;
    }

    .modal-body {
      padding: 0 28px 24px;
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      padding: 0 28px 28px;
    }

    .modal-btn {
      flex: 1;
      padding: 13px;
      border: none;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    .modal-btn.cancel {
      background: #f1f5f9;
      color: var(--text-muted);
      border: 2px solid var(--border);
    }
    .modal-btn.cancel:hover { background: #e2e8f0; }

    .modal-btn.confirm-yes {
      background: linear-gradient(135deg, #d97706, #f59e0b);
      color: #fff;
      box-shadow: 0 4px 12px rgba(217,119,6,0.35);
    }
    .modal-btn.confirm-yes:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(217,119,6,0.45); }

    .modal-btn.success-ok {
      background: linear-gradient(135deg, #16a34a, #22c55e);
      color: #fff;
      box-shadow: 0 4px 12px rgba(22,163,74,0.35);
    }
    .modal-btn.success-ok:hover { transform: translateY(-1px); }

    .modal-btn.danger-ok {
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: #fff;
      box-shadow: 0 4px 12px rgba(220,38,38,0.35);
    }
    .modal-btn.danger-ok:hover { transform: translateY(-1px); }

    .modal-btn.delete-confirm {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: #fff;
      box-shadow: 0 4px 12px rgba(220,38,38,0.35);
    }
    .modal-btn.delete-confirm:hover { transform: translateY(-1px); }

    /* Product name highlight in modal */
    .modal .highlight { color: var(--navy); font-weight: 800; }

    /* ===== STATUS ALERT ===== */
    .status-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .status-chip.changed { background: #fff8e7; color: var(--warning); border: 1px solid #fde68a; }

    /* ===== FOOTER ===== */
    footer {
      background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 60%, #0044bb 100%);
      color: #fff;
      padding: 30px 20px;
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    footer img { height: 52px; margin-bottom: 12px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3)); }
    footer p { font-size: 0.85rem; color: rgba(255,255,255,0.8); line-height: 1.8; }
    footer strong { color: #fff; }
    footer .copyright {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,0.15);
      font-size: 0.78rem;
      color: rgba(255,255,255,0.55);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .menu-toggle { display: block; }
      nav {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 78px; left: 0; right: 0;
        background: var(--navy);
        padding: 12px;
        border-top: 1px solid rgba(255,255,255,0.1);
      }
      nav.show { display: flex; }
      .card-body { padding: 20px; }
    }
  </style>
</head>
<body>


<!-- ===== MAIN CONTENT ===== -->
<div class="page-wrapper">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="profil_usahawan.php?id=<?= $produk['usahawan_id'] ?>"><i class="fas fa-store"></i> Profil Usahawan</a>
    <i class="fas fa-chevron-right"></i>
    <span>Edit Produk</span>
    <i class="fas fa-chevron-right"></i>
    <span style="color:var(--navy);font-weight:700"><?= htmlspecialchars($produk['nama']) ?></span>
  </div>

  <!-- Main Form Card -->
  <div class="card">
    <div class="card-header">
      <div class="icon-wrap"><i class="fas fa-box-open"></i></div>
      <div>
        <h2>Kemaskini Produk</h2>
        <p>Ubah maklumat, gambar atau gallery produk anda</p>
      </div>
    </div>

<<<<<<< HEAD
    <div class="preview" style="margin-top: 20px;">
      <p>Gambar sedia ada:</p>

      <!-- ===============================
     GALLERY PRODUK
================================ -->
<?php if ($gallery->num_rows > 0): ?>

<p style="margin-top:20px;font-weight:bold;">Gallery Produk</p>

<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:10px;">

<?php while($g = $gallery->fetch_assoc()): 

$img = $g['gambar_url'];
if (strpos($img,'uploads/') === false) {
    $img = "uploads/".$img;
}
?>

<div style="position:relative">

<img src="<?= htmlspecialchars($img) ?>" style="
width:90px;
height:90px;
object-fit:cover;
border-radius:8px;
border:2px solid #003366;
">

    <a href="edit_produk.php?id=<?= $id ?>&delete_gallery=<?= $g['id'] ?>"
    onclick="return confirm('Padam gambar ini?')"
    style="
    position:absolute;
    top:-6px;
    right:-6px;
    background:red;
    color:#fff;
    border-radius:50%;
    width:20px;
    height:20px;
    font-size:12px;
    text-align:center;
    line-height:20px;
    text-decoration:none;
    font-weight:bold;
    ">×</a>

    </div>

    <?php endwhile; ?>

    </div>

    <?php endif; ?>

      <?php
      $gambarPath = $produk['gambar_url'];
      if (strpos($gambarPath, 'uploads/') === false) {
          $gambarPath = 'uploads/' . $gambarPath;
      }
      ?>
      <img src="<?= htmlspecialchars($gambarPath) ?>" alt="<?= htmlspecialchars($produk['nama']) ?>">
    </div>

    <label style="margin-top:20px;">Tambah Gambar Gallery</label>
    <input type="file" name="gallery[]" multiple accept="image/*">

    <button type="submit" class="btn-submit">Kemaskini Produk</button>
  </form>
=======
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="editForm">

        <!-- Nama Produk -->
        <div class="form-group">
          <label><i class="fas fa-tag"></i> Nama Produk</label>
          <input type="text" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>" required placeholder="Masukkan nama produk...">
        </div>

        <!-- Deskripsi -->
        <div class="form-group">
          <label><i class="fas fa-align-left"></i> Deskripsi Produk</label>
          <textarea name="deskripsi" rows="4" required placeholder="Huraikan produk anda..."><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
        </div>

        <!-- Harga -->
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Harga Produk</label>
          <div class="price-wrap">
            <span class="prefix">RM</span>
            <input type="number" name="harga" step="0.01" min="0" value="<?= htmlspecialchars($produk['harga']) ?>" required placeholder="0.00">
          </div>
        </div>

        <hr class="divider">

        <!-- ===== GAMBAR UTAMA ===== -->
        <div class="form-group">
          <label><i class="fas fa-image"></i> Gambar Utama Produk</label>

          <!-- Upload Zone -->
          <div class="upload-zone" id="mainUploadZone">
            <input type="file" name="gambar" accept="image/*" id="mainImageInput" onchange="previewMainImage(event)">
            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <h4>Klik atau seret gambar ke sini</h4>
            <p>Hanya untuk menggantikan gambar sedia ada</p>
            <div class="badge-types">
              <span>JPG</span><span>PNG</span><span>GIF</span><span>WEBP</span>
            </div>
          </div>

          <!-- Image Comparison -->
          <div class="preview-section">
            <div class="preview-label">Semakan Gambar</div>
            <div class="img-compare">
              <!-- Current Image -->
              <div>
                <p style="font-size:0.75rem;font-weight:700;color:var(--text-muted);margin-bottom:6px;text-align:center;">
                  <i class="fas fa-clock"></i> SEMASA
                </p>
                <div class="current-img-wrap" id="currentImgWrap">
                  <?php
                  $gambarPath = $produk['gambar_url'];
                  if (strpos($gambarPath, 'uploads/') === false) $gambarPath = 'uploads/' . $gambarPath;
                  ?>
                  <img src="<?= htmlspecialchars($gambarPath) ?>" alt="Gambar Semasa" id="currentImg">
                </div>
              </div>

              <!-- Arrow (only visible when new image selected) -->
              <div class="preview-arrow" id="previewArrow" style="display:none;">
                <i class="fas fa-arrow-right"></i>
              </div>

              <!-- New Preview -->
              <div id="newImgContainer" style="display:none;">
                <p style="font-size:0.75rem;font-weight:700;color:var(--success);margin-bottom:6px;text-align:center;">
                  <i class="fas fa-check-circle"></i> BAHARU
                </p>
                <div class="new-preview-wrap">
                  <img id="newPreviewImg" src="" alt="Gambar Baharu">
                  <div class="new-badge">✓ BAHARU</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="divider">

        <!-- ===== GALLERY ===== -->
        <div class="form-group">
          <label><i class="fas fa-images"></i> Gallery Produk</label>

          <?php
          // Reset gallery result pointer
          $gallery->data_seek(0);
          if ($gallery->num_rows > 0):
          ?>
          <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">
            <i class="fas fa-info-circle"></i> Klik <strong style="color:var(--danger)">×</strong> untuk padam gambar gallery
          </p>
          <div class="gallery-grid">
            <?php while($g = $gallery->fetch_assoc()):
              $img = $g['gambar_url'];
              if (strpos($img,'uploads/') === false) $img = "uploads/".$img;
            ?>
            <div class="gallery-item">
              <img src="<?= htmlspecialchars($img) ?>" alt="Gallery">
              <a href="#" 
                 class="del-btn"
                 onclick="confirmDeleteGallery(<?= $g['id'] ?>, '<?= htmlspecialchars($img) ?>'); return false;">×</a>
            </div>
            <?php endwhile; ?>
          </div>
          <?php else: ?>
          <p style="font-size:0.85rem;color:var(--text-muted);padding:14px;background:#f8fafc;border-radius:8px;text-align:center;">
            <i class="fas fa-folder-open"></i> Tiada gambar gallery lagi
          </p>
          <?php endif; ?>

          <!-- Gallery Upload Zone -->
          <div style="margin-top:16px;">
            <div class="upload-zone" id="galleryUploadZone">
              <input type="file" name="gallery[]" accept="image/*" multiple id="galleryInput" onchange="previewGallery(event)">
              <div class="upload-icon"><i class="fas fa-photo-film"></i></div>
              <h4>Tambah Gambar Gallery</h4>
              <p>Pilih pelbagai gambar sekaligus (multiple)</p>
              <div class="badge-types">
                <span>JPG</span><span>PNG</span><span>GIF</span><span>WEBP</span>
              </div>
            </div>

            <!-- Gallery Preview -->
            <div id="galleryPreviewContainer" style="display:none;margin-top:14px;">
              <div class="preview-label">Gambar Baharu Dipilih</div>
              <div class="gallery-preview-grid" id="galleryPreviewGrid"></div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <button type="button" class="btn-submit" onclick="confirmSubmit()">
          <i class="fas fa-save"></i> Kemaskini Produk
        </button>

      </form>
    </div>
  </div>
>>>>>>> ec6320a04db5bb0add4e3bbc66ec5113f3dcb48d
</div>

<?php include 'footer.php';?>

<!-- =========================================
     MODAL 1: Confirm Submit
========================================== -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon confirm"><i class="fas fa-pencil-alt"></i></div>
      <h3>Sahkan Kemaskini?</h3>
      <p>Anda akan mengemaskini produk <span class="highlight">"<?= htmlspecialchars($produk['nama']) ?>"</span>. Perubahan ini tidak boleh diundur.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn cancel" onclick="closeModal('confirmModal')">
        <i class="fas fa-times"></i> Batal
      </button>
      <button class="modal-btn confirm-yes" onclick="submitForm()">
        <i class="fas fa-check"></i> Ya, Kemaskini
      </button>
    </div>
  </div>
</div>

<!-- =========================================
     MODAL 2: Success
========================================== -->
<div class="modal-overlay" id="successModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon success"><i class="fas fa-check-circle"></i></div>
      <h3>Berjaya Dikemaskini!</h3>
      <p>Produk <span class="highlight">"<?= htmlspecialchars($produk['nama']) ?>"</span> telah berjaya dikemaskini dalam sistem.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn success-ok" onclick="redirectBack()">
        <i class="fas fa-arrow-left"></i> Kembali ke Profil
      </button>
    </div>
  </div>
</div>

<!-- =========================================
     MODAL 3: Error
========================================== -->
<div class="modal-overlay" id="errorModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon danger"><i class="fas fa-exclamation-circle"></i></div>
      <h3>Ralat Berlaku!</h3>
      <p>Maaf, terdapat masalah semasa mengemaskini produk. Sila cuba sekali lagi atau hubungi pentadbir sistem.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn cancel" onclick="closeModal('errorModal')">
        <i class="fas fa-times"></i> Tutup
      </button>
      <button class="modal-btn danger-ok" onclick="closeModal('errorModal')">
        <i class="fas fa-redo"></i> Cuba Lagi
      </button>
    </div>
  </div>
</div>

<!-- =========================================
     MODAL 4: Delete Gallery Confirm
========================================== -->
<div class="modal-overlay" id="deleteGalleryModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon delete"><i class="fas fa-trash-alt"></i></div>
      <h3>Padam Gambar?</h3>
      <p>Gambar ini akan dipadamkan secara kekal dari gallery produk. Tindakan ini <strong>tidak boleh diundur</strong>.</p>
    </div>
    <div class="modal-body" style="text-align:center;">
      <img id="deletePreviewImg" src="" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:10px;border:2px solid #fca5a5;">
    </div>
    <div class="modal-footer">
      <button class="modal-btn cancel" onclick="closeModal('deleteGalleryModal')">
        <i class="fas fa-times"></i> Batal
      </button>
      <button class="modal-btn delete-confirm" id="deleteConfirmBtn">
        <i class="fas fa-trash"></i> Ya, Padam
      </button>
    </div>
  </div>
</div>

<script>
  const usahawanId = <?= $produk['usahawan_id'] ?>;
  const editId = <?= $id ?>;

  // ===== MODAL HELPERS =====
  function openModal(id) {
    document.getElementById(id).classList.add('show');
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('show');
  }

  // Close modal on overlay click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) closeModal(this.id);
    });
  });

  // ===== FORM SUBMIT =====
  function confirmSubmit() {
    openModal('confirmModal');
  }
  function submitForm() {
    closeModal('confirmModal');
    document.getElementById('editForm').submit();
  }
  function redirectBack() {
    window.location.href = 'profil_usahawan.php?id=' + usahawanId;
  }

  // Called from PHP after successful update
  function showSuccessModal() { openModal('successModal'); }
  function showErrorModal()   { openModal('errorModal'); }

  // ===== MAIN IMAGE PREVIEW =====
  function previewMainImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('newPreviewImg').src = e.target.result;
      document.getElementById('newImgContainer').style.display = 'block';
      document.getElementById('previewArrow').style.display = 'flex';
      document.getElementById('currentImgWrap').classList.add('replaced');

      // Update upload zone to show file selected
      const zone = document.getElementById('mainUploadZone');
      zone.querySelector('h4').textContent = '✓ ' + file.name;
      zone.querySelector('p').textContent = (file.size / 1024).toFixed(1) + ' KB';
      zone.style.borderColor = 'var(--success)';
      zone.style.background = '#f0fdf4';
    };
    reader.readAsDataURL(file);
  }

  // ===== GALLERY PREVIEW =====
  function previewGallery(event) {
    const files = event.target.files;
    if (!files.length) return;

    const grid = document.getElementById('galleryPreviewGrid');
    const container = document.getElementById('galleryPreviewContainer');
    grid.innerHTML = '';
    container.style.display = 'block';

    Array.from(files).forEach((file, i) => {
      const reader = new FileReader();
      reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'gallery-preview-item';
        div.innerHTML = `<img src="${e.target.result}" alt="Gallery ${i+1}"><div class="new-badge">BAHARU</div>`;
        grid.appendChild(div);
      };
      reader.readAsDataURL(file);
    });

    // Update gallery zone
    const zone = document.getElementById('galleryUploadZone');
    zone.querySelector('h4').textContent = `✓ ${files.length} gambar dipilih`;
    zone.querySelector('p').textContent = 'Semak preview di bawah';
    zone.style.borderColor = 'var(--success)';
    zone.style.background = '#f0fdf4';
  }

  // ===== DELETE GALLERY MODAL =====
  function confirmDeleteGallery(gid, imgSrc) {
    document.getElementById('deletePreviewImg').src = imgSrc;
    document.getElementById('deleteConfirmBtn').onclick = function() {
      window.location.href = `edit_produk.php?id=${editId}&delete_gallery=${gid}`;
    };
    openModal('deleteGalleryModal');
  }

  // ===== DRAG OVER EFFECT =====
  ['mainUploadZone','galleryUploadZone'].forEach(id => {
    const el = document.getElementById(id);
    el.addEventListener('dragover', () => el.classList.add('drag-over'));
    el.addEventListener('dragleave', () => el.classList.remove('drag-over'));
    el.addEventListener('drop', () => el.classList.remove('drag-over'));
  });
</script>

</body>
</html>