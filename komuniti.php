<?php
session_start();
if (!isset($_SESSION['usahawan_id'])) {
    header("Location: komuniti_login.php");
    exit();
}

include "connection.php";

if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

$user_id   = $_SESSION['usahawan_id'];
$user_nama = $_SESSION['usahawan_nama'] ?? '';

// === Upload Post Baru ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_post'])) {
    $caption = trim($_POST['caption']);
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);

        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Only store filename (without uploads/)
            $stmt = $conn->prepare("INSERT INTO posts (usahawan_id, image, caption) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $imageName, $caption);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// === Tambah Komen ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);

    if ($comment !== "") {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, usahawan_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
}

// === Ambil Semua Post dengan Avatar ===
$sql = "SELECT p.*, u.nama, u.avatar 
        FROM posts p 
        JOIN usahawan u ON p.usahawan_id = u.id 
        ORDER BY p.created_at DESC";
$posts = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Komuniti Usahawan Pahang</title>
  <link rel="icon" type="image/png" href="assets/img/jatapahang.png">
  <style>
    * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ===== Background Premium dengan Watermark Jata Pahang ===== */
body {
  margin: 0;
  background: linear-gradient(135deg, #fdfdfd 0%, #f8f8f6 40%, #ede8dc 100%);
  background-attachment: fixed;
  color: #111;
  overflow-x: hidden;
  position: relative;
  margin-top: 90px;
}

/* ✨ Cahaya lembut keemasan & hitam bergerak */
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 25% 30%, rgba(0, 0, 0, 0.05), transparent 70%),
    radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.15), transparent 70%);
  background-repeat: no-repeat;
  animation: royalWave 25s ease-in-out infinite alternate;
  z-index: -3;
  mix-blend-mode: overlay;
}

/* 🏛️ Multiple Watermark Jata Pahang - lebih jelas */
body::after {
  content: "";
  position: fixed;
  inset: 0;
  background-color: transparent;
  background-image: url("assets/img/jatapahang.png");
  background-repeat: repeat;
  background-size: 180px 180px;
  background-position: center;
  opacity: 0.15; /* 🔆 Naikkan dari 0.07 → 0.15 supaya lebih nampak */
  filter: grayscale(5%) brightness(1.3) contrast(1.1);
  animation: watermarkFloat 40s linear infinite;
  z-index: -2;
}

/* 🌫️ Animasi lembut watermark */
@keyframes watermarkFloat {
  0% { background-position: 0 0; opacity: 0.14; }
  50% { background-position: 80px 60px; opacity: 0.18; }
  100% { background-position: 0 0; opacity: 0.14; }
}

/* 🪄 Efek cahaya bergerak lembut */
@keyframes royalWave {
  0% { background-position: 0% 50%, 100% 50%; transform: scale(1); }
  100% { background-position: 100% 50%, 0% 50%; transform: scale(1.05); }
}




/* ===== Header ===== */
header {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  padding: 15px 20px;
  position: fixed;
  top: 0; left: 0; width: 100%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  flex-wrap: wrap;
}

header img.jata { height: 55px; }
.title { color: #fff; font-size: 1.4rem; font-weight: 700; }

.menu-toggle {
  display: none;
  font-size: 1.8rem;
  cursor: pointer;
  background: none;
  border: none;
  color: #fff;
}

/* ===== Navbar ===== */
nav {
  display: flex;
  gap: 15px;
}

nav a {
  color: #fff;
  padding: 8px 12px;
  font-weight: 500;
  text-decoration: none;
  transition: 0.3s;
}
nav a:hover, nav a.active { color: #ffd700; }

/* ===== 3D Metallic Title ===== */
header .title {
  position: relative;
  color: #ffffffff;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  text-align: center;
  text-shadow:
    0 1px 0 #b3b3b3,
    0 2px 0 #999,
    0 3px 0 #777,
    0 4px 0 #555,
    0 5px 8px rgba(0,0,0,0.6);
  background: linear-gradient(90deg, #e6e6e6 0%, #bfbfbf 50%, #f2f2f2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  -webkit-text-fill-color: transparent;
  overflow: hidden;
}

/* Subtle animated shine */
header .title::after {
  content: "";
  position: absolute;
  top: 0; left: -75%;
  width: 50%; height: 100%;
  background: linear-gradient(
    120deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.6) 50%,
    rgba(255,255,255,0) 100%
  );
  animation: textshine 4s linear infinite;
}

@keyframes textshine {
  0% { left: -75%; }
  100% { left: 125%; }
}

/* ===== Metallic Shine Animation ===== */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


    /* ====== GLOBAL LAYOUT ====== */
.container {
  max-width: 800px;
  margin: 40px auto;
  padding: 0 15px;
  font-family: "Poppins", sans-serif;
}

/* ====== CARD BASE ====== */
.card {
  background: #fff;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
  margin-bottom: 25px;
  transition: box-shadow 0.3s ease, transform 0.3s ease;
  border-color: #003366;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 18px rgba(0, 0, 0, 0.12);
}

/* ====== POST CREATION ====== */
.card h2 {
  font-size: 1.4rem;
  color: #333;
  margin-bottom: 15px;
  font-weight: 600;
}

.card form textarea {
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 10px 12px;
  resize: none;
  font-size: 0.95rem;
  margin-bottom: 10px;
  transition: border-color 0.3s;
}
.card form textarea:focus {
  border-color: #003366;
  outline: none;
}

.card form input[type="file"] {
  display: block;
  margin-bottom: 12px;
  font-size: 0.9rem;
}

.card form button {
  background: #003366;
  color: white;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  padding: 10px 18px;
  cursor: pointer;
  transition: background 0.3s ease;
}
.card form button:hover {
  background: #001e3bff;
}

/* ====== POST FEED ====== */
.post {
  padding: 15px 20px;
}
.post-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}
.post-header .avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #003366;
}
.post-user strong {
  font-size: 1rem;
  color: #333;
}
.post-user small {
  color: #888;
  display: block;
  font-size: 0.8rem;
}

.caption {
  margin: 12px 0;
  font-size: 0.95rem;
  color: #444;
  line-height: 1.4;
  white-space: pre-wrap;
}
.post img {
  width: 100%;
  border-radius: 12px;
  margin-top: 8px;
  object-fit: cover;
  max-height: 450px;
  transition: transform 0.3s;
}
.post img:hover {
  transform: scale(1.01);
}

/* ====== COMMENTS SECTION ====== */
.comments {
  margin-top: 12px;
  border-top: 1px solid #eee;
  padding-top: 12px;
}
.comment {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 10px;
}
.comment .avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
}
.comment div {
  background: #f6f6f6;
  padding: 8px 12px;
  border-radius: 12px;
  font-size: 0.9rem;
  line-height: 1.3;
}
.comment strong {
  color: #333;
  font-size: 0.9rem;
}

/* ====== COMMENT FORM ====== */
.post form {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
}
.post form textarea {
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 8px 10px;
  resize: none;
  font-size: 0.9rem;
  margin-bottom: 8px;
}
.post form button {
  align-self: flex-end;
  background: #003366;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 6px 16px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.3s;
}
.post form button:hover {
  background: #001d3aff;
}

/* ====== RESPONSIVE ====== */
@media (max-width: 600px) {
  .card {
    padding: 15px;
  }
  .post-header {
    flex-direction: row;
    align-items: center;
  }
  .post img {
    max-height: 300px;
  }
  .comment div {
    font-size: 0.85rem;
  }
}

    /* ===== Footer ===== */
footer {
  background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
  color: #fff;
  padding: 30px 20px;
  margin-top: 40px;
  text-align: center;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

footer .footer-content {
  max-width: 1100px;
  margin: auto;
  position: relative;
  z-index: 1;
}

footer img {
  height: 60px;
  margin-bottom: 15px;
  filter: drop-shadow(0 3px 5px rgba(0,0,0,0.4));
}

/* ===== 3D Metallic Text ===== */
footer p,
footer .copyright,
footer strong {
  color: #f8f8f8;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-shadow:
    0 1px 0 #ccc,
    0 2px 0 #aaa,
    0 3px 0 #888,
    0 4px 0 #666,
    0 5px 0 #444,
    0 6px 6px rgba(0,0,0,0.6);
  transition: transform 0.3s ease, text-shadow 0.3s ease;
}

/* Glow and depth on hover */
footer p:hover,
footer strong:hover {
  transform: translateY(-2px);
  text-shadow:
    0 1px 0 #fff,
    0 2px 0 #ddd,
    0 3px 0 #bbb,
    0 4px 0 #999,
    0 5px 0 #777,
    0 8px 12px rgba(0, 0, 0, 0.7),
    0 0 10px rgba(255, 255, 255, 0.3);
}

/* Copyright (subtle) */
footer .copyright {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.2);
  font-size: 0.85rem;
  color: #ddd;
  text-shadow:
    0 1px 0 #999,
    0 2px 0 #666,
    0 3px 3px rgba(0,0,0,0.6);
}

/* Metallic Shine Animation */
@keyframes metalshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}


/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .slideshow-container { height: 300px; }
  .function-btn { min-height: 130px; }
  .function-btn i { font-size: 2rem; }
}

@media (max-width: 768px) {
  .menu-toggle { display: block; }
  nav {
    display: none;
    flex-direction: column;
    background: linear-gradient(
      135deg,
      #001F3F 0%,
      #003399 15%,
      #0066FF 40%,
      #99CCFF 60%,
      #003399 80%,
      #001F3F 100%
  );
  animation: metalshine 6s linear infinite;
    padding: 15px;
    border-radius: 10px;
    margin-top: 12px;
    width: 100%;
  }
  nav.show { display: flex; }
  nav a { text-align: center; padding: 10px; font-size: 1rem; }
  .title { font-size: 1.2rem; }
  .slideshow-container { height: 220px; }
  .function-grid { gap: 18px; }
  .function-btn { min-height: 110px; padding: 18px; }
  .function-btn i { font-size: 1.8rem; }
  .function-btn span { font-size: 0.9rem; }
}

@media (max-width: 480px) {
  .slideshow-container { height: 180px; }
  .function-btn { padding: 15px; }
}

  </style>
</head>
<body>

<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php" class="active"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
  </nav>
</header>

<div class="container">
  <!-- Post Form -->
  <div class="card">
    <h2>Kongsi Post Baru</h2>
    <form method="POST" enctype="multipart/form-data">
      <textarea name="caption" placeholder="Tulis kapsyen..." rows="3"></textarea>
      <input type="file" name="image" accept="image/*" required>
      <button type="submit" name="upload_post">Hantar</button>
    </form>
  </div>

  <!-- Posts Feed -->
  <?php while($post = $posts->fetch_assoc()): ?>
    <?php
      // Handle avatar path
      $avatarPath = $post['avatar'] ? $post['avatar'] : 'assets/img/default-avatar.png';
      if ($avatarPath && strpos($avatarPath, 'uploads/') === false && file_exists("uploads/" . $avatarPath)) {
          $avatarPath = "uploads/" . $avatarPath;
      }

      // Handle post image path
      $imagePath = '';
      if (!empty($post['image'])) {
          $imagePath = (strpos($post['image'], 'uploads/') === false)
              ? 'uploads/' . $post['image']
              : $post['image'];
      }
    ?>
    <div class="card post">
      <div class="post-header">
        <img src="<?= htmlspecialchars($avatarPath) ?>" class="avatar" alt="avatar">
        <div class="post-user">
          <strong><?= htmlspecialchars($post['nama']) ?></strong>
          <small><?= date("d M Y, H:i", strtotime($post['created_at'])) ?></small>
        </div>
      </div>
      <div class="caption"><?= nl2br(htmlspecialchars($post['caption'])) ?></div>
      <?php if($imagePath): ?>
        <img src="<?= htmlspecialchars($imagePath) ?>" alt="post image">
      <?php endif; ?>

      <!-- Comments -->
      <div class="comments">
        <?php
        $stmt = $conn->prepare("SELECT c.*, u.nama, u.avatar FROM comments c 
                                JOIN usahawan u ON c.usahawan_id = u.id 
                                WHERE c.post_id = ? ORDER BY c.created_at ASC");
        $stmt->bind_param("i", $post['id']);
        $stmt->execute();
        $comments = $stmt->get_result();
        while($c = $comments->fetch_assoc()):
            $cAvatar = $c['avatar'] ? $c['avatar'] : 'assets/img/default-avatar.png';
            if ($cAvatar && strpos($cAvatar, 'uploads/') === false && file_exists("uploads/" . $cAvatar)) {
                $cAvatar = "uploads/" . $cAvatar;
            }
        ?>
          <div class="comment">
            <img src="<?= htmlspecialchars($cAvatar) ?>" class="avatar" alt="avatar">
            <div>
              <strong><?= htmlspecialchars($c['nama']) ?>:</strong><br>
              <?= nl2br(htmlspecialchars($c['comment'])) ?>
            </div>
          </div>
        <?php endwhile; $stmt->close(); ?>
      </div>

      <!-- Add Comment -->
      <form method="POST">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <textarea name="comment" rows="2" placeholder="Tulis komen..."></textarea>
        <button type="submit" name="add_comment">Komen</button>
      </form>
    </div>
  <?php endwhile; ?>
</div>

<!-- ===== Footer Rasmi ===== -->
<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | Emel: info@pahang.gov.my</p>
    <div class="copyright">
      © <?= date("Y") ?> Kerajaan Negeri Pahang. Hak cipta terpelihara.
    </div>
  </div>
</footer>

<script>
  function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
  }
</script>

</body>
</html>
