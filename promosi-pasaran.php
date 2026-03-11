<?php
include 'connection.php';
include 'header.php';

$sql_lokasi = "SELECT DISTINCT lokasi FROM produk ORDER BY lokasi DESC";
$result_lokasi = $conn->query($sql_lokasi);

// 🔹 Semak sama ada user sudah login
$is_logged_in = isset($_SESSION['usahawan_id']);
$user_id = $is_logged_in ? $_SESSION['usahawan_id'] : null;

// 🔹 Dapatkan jumlah cart jika sudah login
$cart_count = 0;
if ($is_logged_in) {
  $result_cart = $conn->query("SELECT COUNT(*) AS total FROM cart WHERE usahawan_id = '$user_id'");
  $cart_count = $result_cart ? $result_cart->fetch_assoc()['total'] : 0;
}

$search = isset($_GET['search']) 
  ? mysqli_real_escape_string($conn, $_GET['search']) 
  : '';

$lokasi_filter = isset($_GET['lokasi']) 
  ? mysqli_real_escape_string($conn, $_GET['lokasi']) 
  : '';

$sql = "SELECT * FROM produk WHERE 1";

if (!empty($search)) {
  $sql .= " AND nama LIKE '$search%'";
}

if (!empty($lokasi_filter)) {
  $sql .= " AND lokasi = '$lokasi_filter'";
}

$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
?>




<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senarai Produk - Usahawan Pahang</title>
<link rel="icon" type="image/png" href="assets/img/jatapahang.png">
<style>

/* ===== SEARCH BAR ===== */
.search-wrapper {
  max-width: 1200px;
  margin: 20px auto 10px auto;
  padding: 0 20px;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-box input {
  width: 100%;
  padding: 12px 15px 12px 45px;
  border-radius: 30px;
  border: 1px solid #ccc;
  font-size: 15px;
  outline: none;
  transition: 0.3s;
}

.search-box input:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0,123,255,0.3);
}

.search-icon {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #777;
}


/* ===== PRODUK GRID ===== */
.produk-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr); /* Sentiasa 4 kolum */
  gap:20px;
  padding:20px;
  max-width:1200px;
  margin:auto;
  align-items: stretch; 
}

.produk-card {
  background:#fff;
  border-radius:10px;
  overflow:hidden;
  box-shadow:0 2px 10px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
  cursor: pointer;

  display: flex;
  flex-direction: column;

  height: auto;          /* 🔥 remove fixed height */
  min-height: 300px;     /* optional */
}
.produk-info {
  padding:15px;
  flex: 1;                 
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.produk-card:hover { transform:translateY(-5px); }
.produk-card img { width:100%; height:200px; object-fit:cover; }
.produk-info h3 { font-size:1.1em; color:#222; }
.harga { font-weight:bold; color:#e67e22; margin:6px 0; }
.lokasi { color:#666; font-size:13px; margin-bottom:8px; }

/* ===== BUTTONS ===== */
.btn-group { display:flex; gap:8px; }
.btn {
  flex:1;
  text-align:center;
  padding:8px 10px;
  border:none;
  border-radius:5px;
  font-size:14px;
  cursor:pointer;
  transition:0.3s;
}
.btn-chat { background:#25D366; color:#fff; }
.btn-chat:hover { background:#1eb255; }

/* ===== MODAL (POPUP) ===== */
.modal {
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.6);
  align-items:center;
  justify-content:center;
  z-index:2000;
}
.modal-content {
  background:#fff;
  border-radius:10px;
  max-width:600px;
  width:90%;
  padding:20px;
  box-shadow:0 5px 25px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
  position: relative;
}
.modal-content img {
  width:100%;
  border-radius:10px;
  height:300px;
  object-fit:cover;
}
.modal-details {
  margin-top:15px;
}
.modal-details h2 {
  font-size:1.4rem;
  margin-bottom:5px;
}
.modal-details .harga {
  color:#e67e22;
  font-weight:bold;
  margin-bottom:10px;
}
.modal-buttons {
  display:flex;
  gap:10px;
  margin-top:15px;
}
.modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #dc3545; /* Merah */
  border: none;
  color: #fff;
  font-size: 22px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  transition: 0.25s ease;
  z-index: 10;
}

.modal-close:hover {
  background: #b02a37;
  transform: scale(1.1);
}

/*supaya responsive*/
@media (max-width: 992px) {
  .produk-container {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .produk-container {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .produk-container {
    grid-template-columns: repeat(1, 1fr);
  }
}

/* ===== DARK MODERN CENTER TOAST ===== */
.toast {
  position: fixed;
  bottom: 30px;              /* ⬅ pindah dari tengah ke bawah */
  left: 50%;
  transform: translateX(-50%) scale(0.95);

  min-width: 320px;
  max-width: 420px;
  padding: 18px 22px;
  border-radius: 14px;

  background: #1e3a8a;       /* 🔵 solid color (tak lut sinar) */
  color: #ffffff;

  font-size: 15px;
  font-weight: 600;
  text-align: center;

  box-shadow: 0 15px 35px rgba(0,0,0,0.6);

  opacity: 0;
  pointer-events: none;
  transition: all 0.3s ease;

  z-index: 99999;            /* ⬅ confirm atas semua */
}

.toast.show {
  opacity: 1;
  transform: translateX(-50%) scale(1);
}

/* SUCCESS */
.toast.success {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
}

/* ERROR */
.toast.error {
  background: linear-gradient(135deg, #7f1d1d, #dc2626);
}


</style>
</head>
<body>

<!-- ===== PRODUK LIST ===== -->
<main>
  <!-- ===== SEARCH BAR ===== -->
<div class="search-wrapper">
  <div style="display:flex; gap:10px; align-items:center;">

    <!-- SEARCH -->
    <div class="search-box" style="flex:2;">
      <span class="search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari produk..." autocomplete="off">
    </div>

    <!-- FILTER LOKASI -->
    <select id="lokasiFilter" style="
      flex:1;
      padding:12px;
      border-radius:25px;
      border:1px solid #ccc;
      font-size:14px;
      outline:none;
      height:45px;
    ">
      <option value="">📍 Semua Lokasi</option>

      <?php if ($result_lokasi && $result_lokasi->num_rows > 0): ?>
        <?php while ($lok = $result_lokasi->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($lok['lokasi']) ?>">
            <?= htmlspecialchars($lok['lokasi']) ?>
          </option>
        <?php endwhile; ?>
      <?php endif; ?>
    </select>

  </div>
</div>



  <div class="produk-container" id="produkContainer">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="produk-card"
     onclick="window.location.href='butiran_produk.php?id=<?= $row['id'] ?>'">

          <img src="<?= htmlspecialchars('uploads/'.$row['gambar_url']) ?>" alt="<?= htmlspecialchars($row['nama']) ?>">
          <div class="produk-info">
            <h3><?= htmlspecialchars($row['nama']) ?></h3>
            <p class="harga">RM <?= number_format($row['harga'], 2) ?></p>
            <p class="lokasi">📍 <?= htmlspecialchars($row['lokasi']) ?></p>
            <div class="btn-group">
              <button class="btn btn-cart"
                onclick="event.stopPropagation(); tambahKeCart(
                  <?= (int)$row['id'] ?>,
                  '<?= htmlspecialchars(addslashes($row['nama'])) ?>',
                  <?= (float)$row['harga'] ?>,
                  '<?= htmlspecialchars(addslashes($row['gambar_url'])) ?>'
                )">🛒 Add to Cart</button>

              <button class="btn btn-chat"
                onclick="event.stopPropagation(); bukaChat('<?= urlencode($row['nama']) ?>')">💬 Chat</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center; margin-top:50px;">Tiada produk ditemui.</p>
    <?php endif; ?>
  </div>
</main>

<!-- ===== MODAL (Popup Detail) ===== -->
<div class="modal" id="produkModal">
  <div class="modal-content">
    <button class="modal-close" onclick="tutupPopup()"><strong>×</strong></button>
    <img id="modalGambar" src="" alt="">
    <div class="modal-details">
      <h2 id="modalNama"></h2>
      <p class="harga" id="modalHarga"></p>
      <p id="modalDeskripsi"></p>
      <p id="modalLokasi"></p>
      <div class="modal-buttons">
        <button class="btn btn-cart" onclick="
          tambahKeCart(
            currentProduk.id,
            currentProduk.nama,
            currentProduk.harga,
            currentProduk.gambar_url
          )
        ">🛒 Add to Cart</button>

        <button class="btn btn-chat" onclick="bukaChat(document.getElementById('modalNama').innerText)">💬 Chat</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== TOAST NOTIFICATION ===== -->
<div id="toast" class="toast"></div>

<script>
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}

// ========== REAL TIME SEARCH  ========== //
const searchInput = document.getElementById("searchInput");
const lokasiFilter = document.getElementById("lokasiFilter");
const produkContainer = document.getElementById("produkContainer");

function loadProduk() {
  const searchValue = searchInput.value;
  const lokasiValue = lokasiFilter.value;

  fetch(`?search=${searchValue}&lokasi=${lokasiValue}`)
    .then(res => res.text())
    .then(data => {
      const parser = new DOMParser();
      const html = parser.parseFromString(data, "text/html");
      const newProduk = html.querySelector("#produkContainer").innerHTML;

      produkContainer.innerHTML = newProduk;
    });
}

// ✅ Auto reload bila taip
let searchTimer;
searchInput.addEventListener("keyup", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadProduk, 400);
});

// ✅ Auto reload bila tukar lokasi
lokasiFilter.addEventListener("change", loadProduk);

// ========== FUNGSI TAMBAH KE CART ========== //
async function tambahKeCart(produk_id, nama, harga, gambar_url) {
  console.log('🟢 START - Data yang dihantar:', {
    produk_id: produk_id,
    nama: nama,
    harga: harga,
    gambar_url: gambar_url
  });

  try {
    const formData = new URLSearchParams({
      produk_id: produk_id,
      nama: nama,
      harga: harga,
      gambar_url: gambar_url,
      kuantiti: 1
    });

    console.log('🟡 FormData:', formData.toString());

    const response = await fetch('add_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });

    console.log('🟠 Response status:', response.status);

    const text = await response.text();
    console.log('🔵 Response text:', text);

    const data = JSON.parse(text);
    console.log('🟣 Parsed JSON:', data);

    if (data.success) {
      showToast("🛒 Produk berjaya dimasukkan ke troli.", "success");

      // reload selepas toast 3 saat
      setTimeout(() => {
        document.querySelector(".cart-icon").innerText = "🛒";
      }, 3000);


    } else {
      showToast("⚠️ Gagal menambah produk ke troli.", "error");
    }

  } catch (error) {
    console.error('🔴 ERROR:', error);
    alert('❌ Error: ' + error.message);
  }
}

function bukaChat(nama){
  const url = "https://wa.me/60123456789?text=Hai,%20saya%20berminat%20dengan%20produk%20" + encodeURIComponent(nama);
  window.open(url, "_blank");
}

// ===== Popup Produk =====
function bukaPopup(data){
  currentProduk = data;

  document.getElementById("modalGambar").src = "uploads/" + data.gambar_url;
  document.getElementById("modalNama").innerText = data.nama;
  document.getElementById("modalHarga").innerText = "RM " + parseFloat(data.harga).toFixed(2);
  document.getElementById("modalDeskripsi").innerText = data.deskripsi;
  document.getElementById("modalLokasi").innerText = "📍 " + data.lokasi;

  document.getElementById("produkModal").style.display = "flex";
}

function tutupPopup(){
  document.getElementById("produkModal").style.display = "none";
}

document.getElementById("produkModal").addEventListener("click", function(e){
  if (e.target === this) {
    tutupPopup();
  }
});

function bukaCart(){
  window.location.href = "cart.php";
}

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");

  toast.className = `toast ${type}`;
  toast.innerHTML = message;

  // force reflow (important)
  toast.offsetHeight;

  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

</script>

</body>
</html>

<?php $conn->close(); ?>