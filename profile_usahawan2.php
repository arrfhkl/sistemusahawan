<?php
include "connection.php";
include "usahawan_sidebar.php";

if (!isset($_SESSION['usahawan_id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_SESSION['usahawan_id'];

/* ===============================
   FETCH DATA
================================ */
$stmt = $conn->prepare("
  SELECT nama, ic, perniagaan, jenis, alamat, telefon, email,
         tarikh_daftar, avatar, status, last_profile_update
  FROM usahawan WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

/* ===============================
   30 DAYS EDIT RULE
================================ */
$can_edit = true;
$days_left = 0;

if (!empty($u['last_profile_update'])) {
    $last = new DateTime($u['last_profile_update']);
    $now  = new DateTime();
    $diff = $last->diff($now)->days;
    if ($diff < 30) {
        $can_edit = false;
        $days_left = 30 - $diff;
    }
}

/* ===============================
   MODE CONTROL
================================ */
$editMode   = isset($_GET['edit']) && $can_edit;
$deleteMode = isset($_GET['delete']);

/* ===============================
   UPDATE PROFILE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $can_edit) {

    $stmt = $conn->prepare("
      UPDATE usahawan
      SET nama=?, perniagaan=?, alamat=?, telefon=?, email=?, last_profile_update=NOW()
      WHERE id=?
    ");
    $stmt->bind_param(
        "sssssi",
        $_POST['nama'],
        $_POST['perniagaan'],
        $_POST['alamat'],
        $_POST['telefon'],
        $_POST['email'],
        $id
    );
    $stmt->execute();

    header("Location: profile_usahawan.php?saved=1");
    exit;
}

/* ===============================
   DELETE / DEACTIVATE ACCOUNT
   (BEST PRACTICE: SOFT DELETE)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    $stmt = $conn->prepare("
      UPDATE usahawan SET status='Tidak Aktif' WHERE id=?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    session_destroy();
    header("Location: index.php?account_deactivated=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Profil Usahawan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===============================
   LAYOUT
================================ */
.page{margin-left:260px;padding:30px}
@media(max-width:900px){.page{margin-left:0}}

.card{
  background:#fff;
  border-radius:18px;
  padding:30px;
  max-width:900px;
  box-shadow:0 10px 30px rgba(0,0,0,.12);
  animation:fadeUp .5s ease
}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1}}

.header{display:flex;gap:28px;align-items:center}

/* ===============================
   MODE BASED UI
================================ */
.view-mode .avatar-note,
.view-mode input[type=file]{display:none}

/* ===============================
   AVATAR
================================ */
.avatar-area{text-align:center}

/* VIEW MODE */
.view-mode .avatar-mask{
  width:120px;height:120px;border-radius:50%;
  overflow:hidden;border:2px solid #e0e0e0
}
.view-mode .avatar-img{
  width:120px;height:120px;object-fit:cover
}

/* EDIT MODE */
.edit-mode .avatar-mask{
  width:140px;height:140px;border-radius:50%;
  overflow:hidden;border:3px dashed #003366;
  position:relative;cursor:grab
}
.edit-mode .avatar-mask::after{
  content:"";position:absolute;inset:0;
  border-radius:50%;
  box-shadow:inset 0 0 0 9999px rgba(0,0,0,.15)
}
.edit-mode .avatar-img{
  position:absolute;top:0;left:0;
  width:160px;height:160px;object-fit:cover
}
.avatar-note{font-size:.75rem;color:#555;margin-top:8px}

/* ===============================
   STATUS BADGE
================================ */
.status-badge{
  display:inline-block;
  padding:4px 14px;
  border-radius:20px;
  font-size:.75rem;
  font-weight:600;
  background:#e6f4ea;
  color:#1e7e34;
  margin-top:6px
}

/* ===============================
   SECTIONS
================================ */
.section{margin-top:35px}
.section h3{color:#003366;margin-bottom:16px}
.row{
  display:grid;
  grid-template-columns:220px 1fr;
  margin-bottom:14px
}
.row span{font-weight:600;color:#666}

input,textarea{
  width:100%;padding:10px 12px;
  border-radius:8px;border:1px solid #ccc
}

/* ===============================
   BUTTONS
================================ */
.actions{margin-top:30px;display:flex;gap:12px;flex-wrap:wrap}
.btn{
  padding:10px 24px;border-radius:8px;
  font-weight:600;border:none;cursor:pointer;
  text-decoration:none;display:inline-block
}
.primary{background:#003366;color:#fff}
.secondary{background:#ccc;color:#000}
.danger{background:#b71c1c;color:#fff}

/* ===============================
   NOTICE
================================ */
.notice{
  background:#fff4e5;
  border-left:4px solid #ff9800;
  padding:14px;border-radius:10px;
  margin-bottom:20px;font-size:.9rem
}

/* ===============================
   DELETE BOX
================================ */
.delete-box{
  border:1px solid #e53935;
  background:#fff5f5;
  padding:20px;border-radius:14px;
  margin-top:30px
}

/* ===============================
   TOAST
================================ */
.toast{
  position:fixed;bottom:30px;right:30px;
  background:#003366;color:#fff;
  padding:14px 20px;border-radius:10px;
  box-shadow:0 8px 25px rgba(0,0,0,.25);
  opacity:0;transform:translateY(20px);
  transition:.4s;z-index:2000
}
.toast.show{opacity:1;transform:translateY(0)}
</style>
</head>

<body class="<?= $editMode ? 'edit-mode' : 'view-mode' ?>">

<div class="page">
<div class="card">

<?php if(!$can_edit && isset($_GET['edit'])): ?>
<div class="notice">
  Profil hanya boleh dikemaskini <strong>sekali setiap 30 hari</strong>.
  Baki: <strong><?= $days_left ?> hari</strong>.
</div>
<?php endif; ?>

<div class="header">
  <div class="avatar-area">
    <div class="avatar-mask">
      <img src="<?= $u['avatar'] ?: 'assets/img/no-avatar.png' ?>" class="avatar-img" id="avatarImg">
    </div>

    <?php if($editMode): ?>
      <input type="file" accept="image/*">
      <div class="avatar-note">Seret untuk laraskan posisi gambar</div>
    <?php endif; ?>
  </div>

  <div>
    <h2><?= htmlspecialchars($u['perniagaan']) ?></h2>
    <div style="color:#666"><?= htmlspecialchars($u['nama']) ?></div>
    <div class="status-badge"><?= htmlspecialchars($u['status']) ?></div>
  </div>
</div>

<form method="post" class="section">

<h3>Maklumat Perniagaan</h3>

<?php
function field($label,$name,$value,$edit){
  echo "<div class='row'><span>$label</span>";
  echo $edit
    ? "<input name='$name' value='".htmlspecialchars($value)."'>"
    : "<div>$value</div>";
  echo "</div>";
}
field("Nama Perniagaan","perniagaan",$u['perniagaan'],$editMode);
field("Alamat","alamat",$u['alamat'],$editMode);
field("Telefon","telefon",$u['telefon'],$editMode);
field("Emel","email",$u['email'],$editMode);
?>

<div class="actions">
<?php if($editMode): ?>
  <button class="btn primary" name="update_profile">Simpan</button>
  <a href="profile_usahawan2.php" class="btn secondary">Batal</a>

<?php elseif($deleteMode): ?>
  <a href="profile_usahawan2.php" class="btn secondary">Batal</a>

<?php else: ?>
  <?php if($can_edit): ?>
    <a href="?edit=1" class="btn primary">✏️ Kemaskini Profil</a>
  <?php endif; ?>
  <a href="?delete=1" class="btn danger">🗑 Nyahaktif Akaun</a>
<?php endif; ?>
</div>

</form>

<?php if($deleteMode): ?>
<div class="delete-box">
  <h3 style="color:#b71c1c">Nyahaktif Akaun</h3>
  <p>
    Akaun perniagaan anda akan <strong>dinyahaktifkan</strong>.
    Data tidak akan dipadam tetapi akses akan dihentikan.
  </p>

  <form method="post" style="margin-top:15px">
    <button class="btn danger" name="confirm_delete">
      Sahkan Nyahaktif Akaun
    </button>
  </form>
</div>
<?php endif; ?>

</div>
</div>

<div class="toast" id="toast">Profil berjaya dikemaskini</div>

<?php if(isset($_GET['saved'])): ?>
<script>
window.onload=()=>{
  const t=document.getElementById("toast");
  t.classList.add("show");
  setTimeout(()=>t.classList.remove("show"),3000);
}
</script>
<?php endif; ?>

<?php include "footer.php"; ?>
</body>
</html>
