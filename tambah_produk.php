<?php
include "connection.php";
include "header.php";

/* ==================================================
   AUTH CHECK
================================================== */
if (!isset($_SESSION['usahawan_id'])) {
    header("Location: login.php");
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

/* ==================================================
   DAPATKAN MAKLUMAT USAHAWAN
================================================== */
$stmt = $conn->prepare("SELECT nama FROM usahawan WHERE id = ?");
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Akaun usahawan tidak sah.");
$usahawan = $result->fetch_assoc();
$nama_usahawan = $usahawan['nama'];
$stmt->close();

/* ==================================================
   DAPATKAN SENARAI KATEGORI
================================================== */
$kategori = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");

/* ==================================================
   PROSES TAMBAH PRODUK
================================================== */
$form_error   = null;
$form_success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama        = trim($_POST['nama']);
    $harga       = floatval($_POST['harga']);
    $deskripsi   = trim($_POST['deskripsi']);
    $lokasi      = trim($_POST['lokasi']);
    $stok        = intval($_POST['stok']);
    $kategori_id = intval($_POST['kategori_id']);
    $gambar_url  = null;

    /* ===== Upload Gambar Utama ===== */
    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $allowed)) {
            $form_error = "Format gambar tidak dibenarkan. Gunakan JPG, PNG, GIF atau WEBP.";
        } else {
            $fileName = uniqid("produk_") . "." . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $targetDir . $fileName);
            $gambar_url = $fileName;
        }
    }

    if (!$form_error) {
        $stmt = $conn->prepare("
            INSERT INTO produk (nama, harga, deskripsi, gambar_url, lokasi, stok, kategori_id, usahawan_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsssiii", $nama, $harga, $deskripsi, $gambar_url, $lokasi, $stok, $kategori_id, $usahawan_id);

        if ($stmt->execute()) {
            $new_produk_id = $stmt->insert_id;

            /* ===== Upload Gallery ===== */
            if (!empty($_FILES['gallery']['name'][0])) {
                $targetDir = "uploads/";
                foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp) {
                    $ext = strtolower(pathinfo($_FILES['gallery']['name'][$key], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp'];
                    if (in_array($ext, $allowed)) {
                        $gName = uniqid("gallery_") . "." . $ext;
                        if (move_uploaded_file($tmp, $targetDir . $gName)) {
                            $gs = $conn->prepare("INSERT INTO produk_gallery (produk_id, gambar_url, is_primary) VALUES (?,?,0)");
                            $gs->bind_param("is", $new_produk_id, $gName);
                            $gs->execute();
                        }
                    }
                }
            }

            $form_success = true;
        } else {
            $form_error = "Ralat sistem: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Produk Baharu</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --navy:        #001f3f;
      --blue:        #003399;
      --accent:      #0055cc;
      --accent-soft: #dbeafe;
      --gold:        #b8860b;
      --gold-light:  #f5d98a;
      --success:     #16a34a;
      --success-bg:  #dcfce7;
      --danger:      #dc2626;
      --danger-bg:   #fee2e2;
      --warning:     #d97706;
      --surface:     #ffffff;
      --surface2:    #f8fafd;
      --border:      #e2e8f0;
      --text:        #0f172a;
      --text-mid:    #334155;
      --text-muted:  #64748b;
      --radius-lg:   16px;
      --radius-md:   10px;
      --shadow:      0 4px 24px rgba(0,31,63,0.08);
      --shadow-lg:   0 12px 48px rgba(0,31,63,0.14);
      --transition:  0.25s cubic-bezier(0.4,0,0.2,1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #eef2f7;
      color: var(--text);
      margin-top: 90px;
      min-height: 100vh;
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

    /* ===== PAGE WRAPPER ===== */
    .tp-wrapper {
      max-width: 820px;
      margin: 36px auto 80px;
      padding: 0 18px;
    }

    /* ===== BREADCRUMB ===== */
    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .breadcrumb a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
      transition: color var(--transition);
    }
    .breadcrumb a:hover { color: var(--navy); }
    .breadcrumb .sep { color: #cbd5e1; font-size: 0.7rem; }

    /* ===== GREETING BANNER ===== */
    .greeting-banner {
      background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 55%, #0044bb 100%);
      border-radius: var(--radius-lg);
      padding: 24px 28px;
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-lg);
      position: relative;
      overflow: hidden;
    }
    .greeting-banner::before {
      content: "";
      position: absolute;
      right: -30px; top: -30px;
      width: 160px; height: 160px;
      background: rgba(255,255,255,0.06);
      border-radius: 50%;
    }
    .greeting-banner::after {
      content: "";
      position: absolute;
      right: 60px; bottom: -50px;
      width: 120px; height: 120px;
      background: rgba(255,215,0,0.08);
      border-radius: 50%;
    }
    .banner-icon {
      width: 56px; height: 56px;
      background: rgba(255,255,255,0.12);
      border: 2px solid rgba(255,255,255,0.2);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
      color: var(--gold-light);
      flex-shrink: 0;
      z-index: 1;
    }
    .banner-text { z-index: 1; }
    .banner-text h2 {
      font-family: 'DM Serif Display', serif;
      color: #fff;
      font-size: 1.5rem;
      margin-bottom: 3px;
    }
    .banner-text p {
      color: rgba(255,255,255,0.65);
      font-size: 0.85rem;
    }
    .banner-text .usahawan-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 0.75rem;
      color: var(--gold-light);
      font-weight: 600;
      margin-top: 6px;
    }

    /* ===== MAIN FORM CARD ===== */
    .form-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .form-card-header {
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      padding: 18px 28px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .form-card-header .section-num {
      width: 30px; height: 30px;
      background: linear-gradient(135deg, var(--navy), var(--blue));
      color: #fff;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem;
      font-weight: 800;
      flex-shrink: 0;
    }
    .form-card-header h3 {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--navy);
    }
    .form-card-header p {
      font-size: 0.78rem;
      color: var(--text-muted);
    }

    .form-card-body { padding: 28px; }

    /* ===== FORM GROUPS ===== */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }

    .form-group label {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.82rem;
      font-weight: 800;
      color: var(--navy);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .form-group label i { color: var(--accent); font-size: 0.8rem; }
    .form-group label .req {
      color: var(--danger);
      font-size: 0.9rem;
      line-height: 1;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--border);
      border-radius: var(--radius-md);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.92rem;
      color: var(--text);
      background: var(--surface2);
      transition: all var(--transition);
      appearance: none;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(0,85,204,0.1);
    }
    .form-group textarea { resize: vertical; min-height: 110px; }

    /* Select wrapper */
    .select-wrap { position: relative; }
    .select-wrap::after {
      content: "\f078";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 0.75rem;
      pointer-events: none;
    }

    /* Price & Stok prefix */
    .input-adorn { position: relative; }
    .input-adorn .adorn {
      position: absolute;
      left: 14px; top: 50%;
      transform: translateY(-50%);
      font-weight: 800;
      color: var(--accent);
      font-size: 0.9rem;
      pointer-events: none;
    }
    .input-adorn input { padding-left: 42px !important; }

    /* Helper text */
    .form-hint {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* ===== UPLOAD ZONE ===== */
    .upload-zone {
      border: 2.5px dashed #c0cfe8;
      border-radius: 14px;
      padding: 24px 20px;
      text-align: center;
      cursor: pointer;
      background: linear-gradient(135deg, #f0f6ff, #f8faff);
      transition: all var(--transition);
      position: relative;
    }
    .upload-zone:hover, .upload-zone.drag-over {
      border-color: var(--accent);
      background: #eef4ff;
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0,85,204,0.12);
    }
    .upload-zone.has-file {
      border-color: var(--success);
      background: #f0fdf4;
    }
    .upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .upload-zone .uz-icon {
      font-size: 2.2rem;
      color: #93b4d8;
      margin-bottom: 8px;
      transition: all 0.2s;
    }
    .upload-zone:hover .uz-icon { transform: scale(1.08); color: var(--accent); }
    .upload-zone.has-file .uz-icon { color: var(--success); }
    .upload-zone h4 { font-size: 0.9rem; font-weight: 700; color: var(--navy); margin-bottom: 3px; }
    .upload-zone p  { font-size: 0.78rem; color: var(--text-muted); }
    .uz-types {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-top: 8px;
      flex-wrap: wrap;
    }
    .uz-types span {
      background: #e0ecff;
      color: var(--accent);
      border-radius: 20px;
      padding: 2px 9px;
      font-size: 0.7rem;
      font-weight: 700;
    }
    .upload-zone.has-file .uz-types span {
      background: #dcfce7;
      color: var(--success);
    }

    /* ===== IMAGE PREVIEWS ===== */
    .preview-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 16px 0 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .preview-label::before, .preview-label::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Main image preview */
    .main-preview-wrap {
      display: inline-block;
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      border: 3px solid var(--success);
    }
    .main-preview-wrap img {
      width: 180px;
      height: 180px;
      object-fit: cover;
      display: block;
    }
    .main-preview-wrap .badge-new {
      position: absolute;
      top: 8px; left: 8px;
      background: var(--success);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 800;
      padding: 3px 9px;
      border-radius: 20px;
    }
    .main-preview-wrap .badge-remove {
      position: absolute;
      top: 8px; right: 8px;
      background: rgba(220,38,38,0.85);
      color: #fff;
      width: 26px; height: 26px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      font-size: 0.75rem;
      transition: background var(--transition);
    }
    .main-preview-wrap .badge-remove:hover { background: var(--danger); }

    /* Gallery grid preview */
    .gallery-preview-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
    .gp-item {
      position: relative;
      border-radius: 10px;
      overflow: hidden;
      border: 2.5px solid var(--success);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      flex-shrink: 0;
    }
    .gp-item img {
      width: 88px; height: 88px;
      object-fit: cover;
      display: block;
    }
    .gp-item .gp-num {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      background: rgba(22,163,74,0.8);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      text-align: center;
      padding: 3px;
    }

    /* ===== SUBMIT BUTTON ===== */
    .btn-simpan {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, var(--navy), var(--blue));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all var(--transition);
      box-shadow: 0 4px 16px rgba(0,51,153,0.3);
      letter-spacing: 0.5px;
    }
    .btn-simpan:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,51,153,0.4);
    }
    .btn-simpan:active { transform: translateY(0); }

    /* ===== STEP INDICATOR ===== */
    .step-nav {
      display: flex;
      gap: 0;
      margin-bottom: 24px;
      background: var(--surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .step-item {
      flex: 1;
      padding: 14px 10px;
      text-align: center;
      border-right: 1px solid var(--border);
      cursor: pointer;
      transition: background var(--transition);
      position: relative;
    }
    .step-item:last-child { border-right: none; }
    .step-item.active { background: linear-gradient(135deg, #f0f6ff, #eef3ff); }
    .step-item .step-dot {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: var(--border);
      color: var(--text-muted);
      font-size: 0.75rem;
      font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 6px;
      transition: all var(--transition);
    }
    .step-item.active .step-dot {
      background: linear-gradient(135deg, var(--navy), var(--blue));
      color: #fff;
      box-shadow: 0 3px 10px rgba(0,51,153,0.3);
    }
    .step-item .step-label {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--text-muted);
    }
    .step-item.active .step-label { color: var(--navy); }

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
      max-width: 420px;
      width: 90%;
      box-shadow: 0 24px 80px rgba(0,0,0,0.25);
      transform: scale(0.85) translateY(20px);
      transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
      overflow: hidden;
    }
    .modal-overlay.show .modal { transform: scale(1) translateY(0); }

    .modal-top {
      padding: 32px 28px 22px;
      text-align: center;
    }
    .modal-icon {
      width: 72px; height: 72px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin: 0 auto 16px;
    }
    .modal-icon.confirm { background: #fef3c7; color: var(--warning); border: 3px solid #fde68a; }
    .modal-icon.success { background: var(--success-bg); color: var(--success); border: 3px solid #86efac; }
    .modal-icon.danger  { background: var(--danger-bg);  color: var(--danger);  border: 3px solid #fca5a5; }

    .modal h3 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.35rem;
      color: var(--text);
      margin-bottom: 8px;
    }
    .modal p {
      font-size: 0.88rem;
      color: var(--text-muted);
      line-height: 1.65;
    }
    .modal-footer {
      display: flex;
      gap: 10px;
      padding: 8px 28px 28px;
    }
    .modal-btn {
      flex: 1;
      padding: 13px;
      border: none;
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: all var(--transition);
      display: flex; align-items: center; justify-content: center;
      gap: 6px;
    }
    .btn-cancel  { background: #f1f5f9; color: var(--text-muted); border: 2px solid var(--border); }
    .btn-cancel:hover { background: #e2e8f0; }
    .btn-confirm { background: linear-gradient(135deg, var(--warning), #f59e0b); color: #fff; box-shadow: 0 4px 12px rgba(217,119,6,0.3); }
    .btn-confirm:hover { transform: translateY(-1px); }
    .btn-success-ok { background: linear-gradient(135deg, var(--success), #22c55e); color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,0.3); }
    .btn-success-ok:hover { transform: translateY(-1px); }
    .btn-danger-ok  { background: linear-gradient(135deg, var(--danger), #ef4444); color: #fff; box-shadow: 0 4px 12px rgba(220,38,38,0.3); }
    .btn-danger-ok:hover { transform: translateY(-1px); }

    /* ===== PROGRESS BAR (fake) ===== */
    .form-progress {
      height: 3px;
      background: var(--border);
      border-radius: 10px;
      margin-bottom: 24px;
      overflow: hidden;
    }
    .form-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--navy), var(--accent));
      border-radius: 10px;
      width: 0%;
      transition: width 0.4s ease;
    }

    /* ===== CHAR COUNTER ===== */
    .char-counter {
      font-size: 0.72rem;
      color: var(--text-muted);
      text-align: right;
      margin-top: 4px;
    }
    .char-counter.warn { color: var(--warning); }
    .char-counter.over { color: var(--danger); }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .form-card   { animation: fadeUp 0.45s ease both; }
    .form-card:nth-child(2) { animation-delay: 0.08s; }
    .form-card:nth-child(3) { animation-delay: 0.16s; }
    .form-card:nth-child(4) { animation-delay: 0.24s; }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 640px) {
      .form-row { grid-template-columns: 1fr; }
      .form-card-body { padding: 20px; }
      .step-item .step-label { display: none; }
    }
  </style>
</head>
<body>

<div class="tp-wrapper">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="index.php"><i class="fas fa-home"></i> Laman Utama</a>
    <i class="fas fa-chevron-right sep"></i>
    <a href="profil_usahawan.php"><i class="fas fa-store"></i> Profil Usahawan</a>
    <i class="fas fa-chevron-right sep"></i>
    <span style="color:var(--navy);font-weight:700">Tambah Produk</span>
  </div>

  <!-- Greeting Banner -->
  <div class="greeting-banner">
    <div class="banner-icon"><i class="fas fa-box-open"></i></div>
    <div class="banner-text">
      <h2>Tambah Produk Baharu</h2>
      <p>Lengkapkan maklumat produk anda untuk disiarkan dalam sistem</p>
      <span class="usahawan-chip">
        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($nama_usahawan) ?>
      </span>
    </div>
  </div>

  <!-- Step Nav -->
  <div class="step-nav">
    <div class="step-item active" id="step1">
      <div class="step-dot">1</div>
      <div class="step-label">Maklumat Asas</div>
    </div>
    <div class="step-item" id="step2">
      <div class="step-dot">2</div>
      <div class="step-label">Harga & Stok</div>
    </div>
    <div class="step-item" id="step3">
      <div class="step-dot">3</div>
      <div class="step-label">Gambar</div>
    </div>
    <div class="step-item" id="step4">
      <div class="step-dot">4</div>
      <div class="step-label">Kategori</div>
    </div>
  </div>

  <!-- Progress -->
  <div class="form-progress">
    <div class="form-progress-fill" id="progressFill"></div>
  </div>

  <form method="POST" enctype="multipart/form-data" id="tambahForm">

    <!-- ===== SECTION 1: MAKLUMAT ASAS ===== -->
    <div class="form-card">
      <div class="form-card-header">
        <div class="section-num">1</div>
        <div>
          <h3>Maklumat Asas Produk</h3>
          <p>Nama, deskripsi dan lokasi produk</p>
        </div>
      </div>
      <div class="form-card-body">

        <div class="form-group">
          <label>
            <i class="fas fa-tag"></i> Nama Produk <span class="req">*</span>
          </label>
          <input type="text" name="nama" id="namaProduk"
                 placeholder="Contoh: Kerepek Ubi Pedas Manis..."
                 maxlength="160" required
                 oninput="updateProgress(); updateCharCount('namaProduk', 'namaCount', 160)">
          <div class="char-counter" id="namaCount">0 / 160</div>
        </div>

        <div class="form-group">
          <label><i class="fas fa-align-left"></i> Deskripsi Produk</label>
          <textarea name="deskripsi" id="deskripsiProduk" rows="4"
                    placeholder="Huraikan produk anda secara terperinci — bahan, kelebihan, cara guna, dsb."
                    maxlength="1000"
                    oninput="updateCharCount('deskripsiProduk', 'deskCount', 1000)"></textarea>
          <div class="char-counter" id="deskCount">0 / 1000</div>
        </div>

        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Lokasi Produk</label>
          <input type="text" name="lokasi"
                 placeholder="Contoh: Kuantan, Pahang..."
                 oninput="updateProgress()">
          <div class="form-hint"><i class="fas fa-info-circle"></i> Masukkan bandar atau daerah produk anda</div>
        </div>

      </div>
    </div>

    <!-- ===== SECTION 2: HARGA & STOK ===== -->
    <div class="form-card">
      <div class="form-card-header">
        <div class="section-num">2</div>
        <div>
          <h3>Harga &amp; Stok</h3>
          <p>Tetapkan harga jualan dan kuantiti stok</p>
        </div>
      </div>
      <div class="form-card-body">
        <div class="form-row">
          <div class="form-group">
            <label><i class="fas fa-money-bill-wave"></i> Harga (RM) <span class="req">*</span></label>
            <div class="input-adorn">
              <span class="adorn">RM</span>
              <input type="number" name="harga" step="0.01" min="0"
                     placeholder="0.00" required oninput="updateProgress()">
            </div>
            <div class="form-hint"><i class="fas fa-info-circle"></i> Harga per unit produk</div>
          </div>
          <div class="form-group">
            <label><i class="fas fa-cubes"></i> Kuantiti Stok <span class="req">*</span></label>
            <div class="input-adorn">
              <span class="adorn" style="font-size:0.75rem;">unit</span>
              <input type="number" name="stok" min="0" placeholder="0"
                     required style="padding-left:52px !important;"
                     oninput="updateProgress(); updateStockHint(this.value)">
            </div>
            <div class="form-hint" id="stockHint">
              <i class="fas fa-info-circle"></i> Masukkan bilangan stok semasa
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== SECTION 3: GAMBAR ===== -->
    <div class="form-card">
      <div class="form-card-header">
        <div class="section-num">3</div>
        <div>
          <h3>Gambar Produk</h3>
          <p>Gambar utama dan gallery tambahan</p>
        </div>
      </div>
      <div class="form-card-body">

        <!-- Gambar Utama -->
        <div class="form-group">
          <label><i class="fas fa-image"></i> Gambar Utama</label>
          <div class="upload-zone" id="mainUploadZone">
            <input type="file" name="gambar" id="mainImgInput"
                   accept="image/*" onchange="previewMain(event)">
            <div class="uz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <h4 id="mainUploadTitle">Klik atau seret gambar ke sini</h4>
            <p id="mainUploadSub">Gambar utama produk anda</p>
            <div class="uz-types">
              <span>JPG</span><span>PNG</span><span>GIF</span><span>WEBP</span>
            </div>
          </div>

          <!-- Main preview -->
          <div id="mainPreviewWrap" style="display:none; margin-top:16px; text-align:center;">
            <div class="preview-label">Pratonton Gambar Utama</div>
            <div class="main-preview-wrap">
              <img id="mainPreviewImg" src="" alt="Preview">
              <div class="badge-new">✓ UTAMA</div>
              <div class="badge-remove" onclick="removeMainImage()" title="Buang gambar">
                <i class="fas fa-times"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Gallery -->
        <div class="form-group" style="margin-top:24px;">
          <label><i class="fas fa-images"></i> Gallery Produk <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0">(Pilihan)</span></label>
          <div class="upload-zone" id="galleryUploadZone">
            <input type="file" name="gallery[]" id="galleryInput"
                   accept="image/*" multiple onchange="previewGallery(event)">
            <div class="uz-icon"><i class="fas fa-photo-film"></i></div>
            <h4 id="galleryUploadTitle">Tambah Gambar Gallery</h4>
            <p id="galleryUploadSub">Pilih beberapa gambar sekaligus untuk gallery</p>
            <div class="uz-types">
              <span>JPG</span><span>PNG</span><span>GIF</span><span>WEBP</span>
            </div>
          </div>

          <!-- Gallery preview -->
          <div id="galleryPreviewWrap" style="display:none; margin-top:16px;">
            <div class="preview-label">Pratonton Gallery</div>
            <div class="gallery-preview-grid" id="galleryGrid"></div>
          </div>
        </div>

      </div>
    </div>

    <!-- ===== SECTION 4: KATEGORI ===== -->
    <div class="form-card">
      <div class="form-card-header">
        <div class="section-num">4</div>
        <div>
          <h3>Kategori Produk</h3>
          <p>Klasifikasikan produk untuk carian lebih mudah</p>
        </div>
      </div>
      <div class="form-card-body">
        <div class="form-group">
          <label><i class="fas fa-layer-group"></i> Pilih Kategori <span class="req">*</span></label>
          <div class="select-wrap">
            <select name="kategori_id" required onchange="updateProgress()">
              <option value="">-- Pilih Kategori --</option>
              <?php while($row = $kategori->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <!-- Submit -->
        <button type="button" class="btn-simpan" onclick="openConfirmModal()" style="margin-top:8px;">
          <i class="fas fa-save"></i> Simpan &amp; Siarkan Produk
        </button>

        <a href="profil_usahawan.php" style="
            display:block;
            text-align:center;
            margin-top:14px;
            font-size:0.85rem;
            color:var(--text-muted);
            text-decoration:none;
            font-weight:600;
        ">
          <i class="fas fa-arrow-left"></i> Batal &amp; Kembali ke Profil
        </a>

      </div>
    </div>

  </form>
</div>

<!-- ===== MODAL: CONFIRM ===== -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <div class="modal-top">
      <div class="modal-icon confirm"><i class="fas fa-box-open"></i></div>
      <h3>Siar Produk?</h3>
      <p>Produk ini akan disimpan dan disiarkan dalam sistem Usahawan Pahang.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn btn-cancel" onclick="closeModal('confirmModal')">
        <i class="fas fa-times"></i> Semak Semula
      </button>
      <button class="modal-btn btn-confirm" onclick="submitForm()">
        <i class="fas fa-check"></i> Ya, Siarkan
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAL: SUCCESS ===== -->
<div class="modal-overlay" id="successModal">
  <div class="modal">
    <div class="modal-top">
      <div class="modal-icon success"><i class="fas fa-check-circle"></i></div>
      <h3>Produk Berjaya Ditambah!</h3>
      <p>Produk anda telah berjaya disimpan dan kini tersiar dalam sistem.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn btn-success-ok" onclick="window.location='profil_usahawan.php'">
        <i class="fas fa-store"></i> Lihat Profil
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAL: ERROR ===== -->
<div class="modal-overlay" id="errorModal">
  <div class="modal">
    <div class="modal-top">
      <div class="modal-icon danger"><i class="fas fa-exclamation-circle"></i></div>
      <h3>Ralat Berlaku</h3>
      <p id="errorMsg">Terdapat masalah semasa menyimpan produk. Sila cuba semula.</p>
    </div>
    <div class="modal-footer">
      <button class="modal-btn btn-danger-ok" onclick="closeModal('errorModal')">
        <i class="fas fa-redo"></i> Cuba Semula
      </button>
    </div>
  </div>
</div>

<?php if ($form_success): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('successModal'));</script>
<?php endif; ?>

<?php if ($form_error): ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('errorMsg').textContent = '<?= addslashes($form_error) ?>';
  openModal('errorModal');
});
</script>
<?php endif; ?>

<script>
// ===== MODAL =====
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); });
});

function openConfirmModal() {
  const form = document.getElementById('tambahForm');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  openModal('confirmModal');
}
function submitForm() {
  closeModal('confirmModal');
  document.getElementById('tambahForm').submit();
}

// ===== PROGRESS =====
function updateProgress() {
  const fields = ['namaProduk'];
  const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
  let filled = 0;
  inputs.forEach(el => { if (el.value.trim()) filled++; });
  const pct = Math.min(100, Math.round((filled / inputs.length) * 100));
  document.getElementById('progressFill').style.width = pct + '%';

  // Step highlight
  const nama    = document.querySelector('[name="nama"]').value.trim();
  const harga   = document.querySelector('[name="harga"]').value.trim();
  const stok    = document.querySelector('[name="stok"]').value.trim();
  const kat     = document.querySelector('[name="kategori_id"]').value;

  setStep('step1', nama);
  setStep('step2', harga && stok);
  setStep('step4', kat);
}
function setStep(id, cond) {
  document.getElementById(id).classList.toggle('active', !!cond);
}

// ===== CHAR COUNTER =====
function updateCharCount(inputId, countId, max) {
  const len = document.getElementById(inputId).value.length;
  const el  = document.getElementById(countId);
  el.textContent = len + ' / ' + max;
  el.className = 'char-counter' + (len > max*0.9 ? (len >= max ? ' over' : ' warn') : '');
}

// ===== STOCK HINT =====
function updateStockHint(val) {
  const el = document.getElementById('stockHint');
  const n  = parseInt(val);
  if (isNaN(n) || val === '') {
    el.innerHTML = '<i class="fas fa-info-circle"></i> Masukkan bilangan stok semasa';
    el.style.color = '';
  } else if (n === 0) {
    el.innerHTML = '<i class="fas fa-times-circle"></i> Produk akan ditanda <strong>Habis Stok</strong>';
    el.style.color = 'var(--danger)';
  } else if (n <= 5) {
    el.innerHTML = '<i class="fas fa-exclamation-circle"></i> Stok rendah — produk akan ditanda <strong>Stok Rendah</strong>';
    el.style.color = 'var(--warning)';
  } else {
    el.innerHTML = '<i class="fas fa-check-circle"></i> Stok mencukupi — produk akan ditanda <strong>Tersedia</strong>';
    el.style.color = 'var(--success)';
  }
}

// ===== MAIN IMAGE PREVIEW =====
function previewMain(event) {
  const file = event.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('mainPreviewImg').src = e.target.result;
    document.getElementById('mainPreviewWrap').style.display = 'block';

    const zone = document.getElementById('mainUploadZone');
    zone.classList.add('has-file');
    document.getElementById('mainUploadTitle').textContent = '✓ ' + file.name;
    document.getElementById('mainUploadSub').textContent = (file.size/1024).toFixed(1) + ' KB';

    setStep('step3', true);
  };
  reader.readAsDataURL(file);
}

function removeMainImage() {
  document.getElementById('mainImgInput').value = '';
  document.getElementById('mainPreviewWrap').style.display = 'none';
  const zone = document.getElementById('mainUploadZone');
  zone.classList.remove('has-file');
  document.getElementById('mainUploadTitle').textContent = 'Klik atau seret gambar ke sini';
  document.getElementById('mainUploadSub').textContent = 'Gambar utama produk anda';
}

// ===== GALLERY PREVIEW =====
function previewGallery(event) {
  const files = event.target.files;
  if (!files.length) return;

  const grid = document.getElementById('galleryGrid');
  const wrap = document.getElementById('galleryPreviewWrap');
  grid.innerHTML = '';
  wrap.style.display = 'block';

  Array.from(files).forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const div = document.createElement('div');
      div.className = 'gp-item';
      div.innerHTML = `<img src="${e.target.result}" alt="Gallery ${i+1}">
                       <div class="gp-num">#${i+1}</div>`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });

  const zone = document.getElementById('galleryUploadZone');
  zone.classList.add('has-file');
  document.getElementById('galleryUploadTitle').textContent = `✓ ${files.length} gambar dipilih`;
  document.getElementById('galleryUploadSub').textContent = 'Semak pratonton di bawah';
}

// ===== DRAG OVER EFFECTS =====
['mainUploadZone','galleryUploadZone'].forEach(id => {
  const el = document.getElementById(id);
  el.addEventListener('dragover',  e => { e.preventDefault(); el.classList.add('drag-over'); });
  el.addEventListener('dragleave', ()=> el.classList.remove('drag-over'));
  el.addEventListener('drop',      ()=> el.classList.remove('drag-over'));
});

// ===== INIT =====
updateProgress();
</script>

<?php include "footer.php"; ?>
<?php $conn->close(); ?>
</body>
</html>