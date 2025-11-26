<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Permohonan Geran Agropreneur Muda - Sistem Usahawan Pahang</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
  opacity: 0.15;
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

/* ===== Container & Card ===== */
.container { 
  max-width: 900px; 
  margin: 40px auto; 
  padding: 20px; 
}

.card { 
  background: rgba(255, 255, 255, 0.95);
  padding: 40px; 
  border-radius: 16px; 
  border: 1px solid rgba(76, 175, 80, 0.3);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
}

/* ===== Form Header ===== */
.form-header {
  text-align: center;
  margin-bottom: 35px;
  padding-bottom: 20px;
  border-bottom: 2px solid rgba(76, 175, 80, 0.2);
}

.form-header i {
  font-size: 3rem;
  color: #4caf50;
  margin-bottom: 15px;
  display: block;
}

.form-header h2 { 
  color: #4caf50;
  font-size: 1.8rem;
  margin-bottom: 10px;
  font-weight: 700;
}

.form-header p {
  color: #666;
  font-size: 0.95rem;
  line-height: 1.5;
}

/* ===== Form Sections ===== */
.form-section {
  margin-bottom: 30px;
}

.section-title {
  color: #4caf50;
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(76, 175, 80, 0.15);
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title i {
  font-size: 1.1rem;
}

/* ===== Form Groups ===== */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #333;
  font-weight: 600;
  font-size: 0.95rem;
}

.form-group label i {
  margin-right: 6px;
  color: #4caf50;
  width: 16px;
}

.form-group label .required {
  color: #dc3545;
  margin-left: 3px;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #4caf50;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

/* ===== File Upload Styling ===== */
.file-upload-wrapper {
  position: relative;
  overflow: hidden;
  display: inline-block;
  width: 100%;
}

.file-upload-wrapper input[type="file"] {
  position: absolute;
  left: -9999px;
}

.file-upload-label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  border: 2px dashed #4caf50;
  border-radius: 8px;
  background: rgba(76, 175, 80, 0.05);
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
}

.file-upload-label:hover {
  background: rgba(76, 175, 80, 0.1);
  border-color: #3d8b40;
}

.file-upload-label i {
  font-size: 2rem;
  color: #4caf50;
  margin-right: 15px;
}

.file-upload-text {
  text-align: left;
}

.file-upload-text strong {
  color: #4caf50;
  display: block;
  margin-bottom: 5px;
}

.file-upload-text small {
  color: #666;
  font-size: 0.85rem;
}

/* ===== Info Box ===== */
.info-box {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(76, 175, 80, 0.02) 100%);
  border-left: 4px solid #4caf50;
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 25px;
}

.info-box i {
  color: #4caf50;
  margin-right: 10px;
}

.info-box p {
  margin: 0;
  color: #555;
  font-size: 0.9rem;
  line-height: 1.6;
}

/* ===== Grid Layout for Two Columns ===== */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* ===== Submit Button ===== */
.btn-submit {
  background: linear-gradient(135deg, #28a745 0%, #20a23a 100%);
  color: white;
  border: none;
  padding: 15px 40px;
  border-radius: 8px;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-submit:hover {
  background: linear-gradient(135deg, #20a23a 0%, #1e7e34 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-submit:active {
  transform: translateY(0);
}

.btn-submit i {
  font-size: 1.2rem;
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
  margin-top: 60px;
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

/* ===== Responsive Design ===== */
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
  
  .card {
    padding: 25px;
  }
  
  .form-header h2 {
    font-size: 1.5rem;
  }
  
  .form-header i {
    font-size: 2.5rem;
  }
}

@media (max-width: 480px) {
  .card {
    padding: 20px;
  }
  
  .btn-submit {
    padding: 12px 30px;
    font-size: 1rem;
  }
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
  <div class="card">
    <div class="form-header">
      <i class="fas fa-leaf"></i>
      <h2>Permohonan Geran Agropreneur Muda</h2>
      <p>Sila lengkapkan borang permohonan di bawah dengan maklumat yang tepat dan lengkap. Pastikan semua dokumen sokongan disediakan.</p>
    </div>

    <div class="info-box">
      <i class="fas fa-info-circle"></i>
      <p><strong>Nota Penting:</strong> Geran Agropreneur Muda menyediakan bantuan kewangan antara RM1,000 hingga RM20,000 untuk usahawan muda dalam bidang pertanian, penternakan, perikanan dan agro-teknologi. Semua medan bertanda <span class="required">*</span> adalah wajib diisi.</p>
    </div>

    <form action="simpan-agro.php" method="POST" enctype="multipart/form-data">
      
      <!-- Bahagian 1: Maklumat Peribadi -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-user"></i>
          <span>Maklumat Peribadi</span>
        </div>
        
        <div class="form-group">
          <label for="nama">
            <i class="fas fa-id-card"></i>
            Nama Penuh <span class="required">*</span>
          </label>
          <input type="text" id="nama" name="nama" placeholder="Contoh: Ahmad Bin Abdullah" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="ic">
              <i class="fas fa-id-card-alt"></i>
              No. Kad Pengenalan <span class="required">*</span>
            </label>
            <input type="text" id="ic" name="ic" pattern="[0-9]{12}" placeholder="010203031234" required>
          </div>

          <div class="form-group">
            <label for="telefon">
              <i class="fas fa-phone"></i>
              No. Telefon <span class="required">*</span>
            </label>
            <input type="tel" id="telefon" name="telefon" placeholder="0123456789" required>
          </div>
        </div>

        <div class="form-group">
          <label for="alamat">
            <i class="fas fa-map-marker-alt"></i>
            Alamat Lengkap <span class="required">*</span>
          </label>
          <textarea id="alamat" name="alamat" placeholder="Masukkan alamat lengkap termasuk poskod dan negeri" required></textarea>
        </div>
      </div>

      <!-- Bahagian 2: Maklumat Permohonan -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-seedling"></i>
          <span>Maklumat Permohonan</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="kategori">
              <i class="fas fa-users"></i>
              Kategori Pemohon <span class="required">*</span>
            </label>
            <select id="kategori" name="kategori" required>
              <option value="">-- Sila Pilih --</option>
              <option value="Usahawan">Usahawan Sedia Ada</option>
              <option value="Usahawan Baru">Usahawan Baharu</option>
            </select>
          </div>

          <div class="form-group">
            <label for="projek">
              <i class="fas fa-tractor"></i>
              Jenis Projek <span class="required">*</span>
            </label>
            <select id="projek" name="projek" required>
              <option value="">-- Sila Pilih --</option>
              <option value="Pertanian">Pertanian</option>
              <option value="Penternakan">Penternakan</option>
              <option value="Perikanan">Perikanan</option>
              <option value="Agro-Teknologi">Agro-Teknologi</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="jumlah">
            <i class="fas fa-money-bill-wave"></i>
            Jumlah Geran Dimohon (RM) <span class="required">*</span>
          </label>
          <input type="number" id="jumlah" name="jumlah" min="1000" max="20000" placeholder="1000 - 20000" required>
        </div>

        <div class="form-group">
          <label for="tujuan">
            <i class="fas fa-clipboard-list"></i>
            Tujuan Permohonan <span class="required">*</span>
          </label>
          <textarea id="tujuan" name="tujuan" placeholder="Nyatakan dengan jelas tujuan permohonan geran ini untuk projek agro anda" required></textarea>
        </div>
      </div>

      <!-- Bahagian 3: Dokumen Sokongan -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-file-upload"></i>
          <span>Dokumen Sokongan</span>
        </div>

        <div class="form-group">
          <label>
            <i class="fas fa-paperclip"></i>
            Muat Naik Dokumen <span class="required">*</span>
          </label>
          <div class="file-upload-wrapper">
            <input type="file" id="dokumen" name="dokumen" accept=".pdf,.jpg,.jpeg,.png" required>
            <label for="dokumen" class="file-upload-label">
              <i class="fas fa-cloud-upload-alt"></i>
              <div class="file-upload-text">
                <strong>Klik untuk memuat naik dokumen</strong>
                <small>Format yang diterima: PDF, JPG, PNG (Maks: 5MB)</small>
              </div>
            </label>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <i class="fas fa-paper-plane"></i>
        <span>Hantar Permohonan</span>
      </button>
    </form>
  </div>
</div>

<footer>
  <div class="footer-content">
    <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang">
    <p><strong>Sistem Usahawan Pahang</strong></p>
    <p>Pejabat Setiausaha Kerajaan Negeri Pahang<br>
    Kompleks SUK, 25503 Kuantan, Pahang Darul Makmur</p>
    <p>Telefon: 09-1234567 | Emel: info@pahang.gov.my</p>
    <div class="copyright">
      © 2025 Kerajaan Negeri Pahang. Hak cipta terpelihara.
    </div>
  </div>
</footer>

<script>
  function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('show');
  }

  // File upload display
  document.getElementById('dokumen').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
      const label = document.querySelector('.file-upload-label');
      label.innerHTML = `
        <i class="fas fa-check-circle" style="color: #28a745;"></i>
        <div class="file-upload-text">
          <strong style="color: #28a745;">Fail dipilih: ${fileName}</strong>
          <small>Klik untuk tukar fail</small>
        </div>
      `;
    }
  });
</script>

</body>
</html>