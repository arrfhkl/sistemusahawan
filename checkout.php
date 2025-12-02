<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    echo "<script>alert('Sila log masuk terlebih dahulu.'); window.location.href='login.php';</script>";
    exit;
}

$usahawan_id = $_SESSION['usahawan_id'];

// Dapatkan maklumat pengguna
$sql_user = "SELECT nama, telefon, alamat FROM usahawan WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $usahawan_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Semak jika tiada item dipilih
if (empty($_POST['selected_items'])) {
    echo "<script>alert('Tiada item dipilih untuk checkout.'); window.location.href='cart.php';</script>";
    exit;
}

// Dapatkan senarai item terpilih
$ids = array_map('intval', $_POST['selected_items']);
$id_list = implode(",", $ids);

// Ambil maklumat produk dari DB dengan validation
$sql = "SELECT c.id as cart_id, c.kuantiti, c.produk_id, p.nama, p.harga, p.gambar_url 
        FROM cart c 
        JOIN produk p ON c.produk_id = p.id 
        WHERE c.id IN ($id_list) AND c.usahawan_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;
if ($result) {
    while($row = $result->fetch_assoc()) {
        $subtotal = $row['harga'] * $row['kuantiti'];
        $row['subtotal'] = $subtotal;
        $total += $subtotal;
        $items[] = $row;
    }
}

// Semak jika tiada item dijumpai
if (empty($items)) {
    echo "<script>alert('Item tidak dijumpai dalam cart.'); window.location.href='cart.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Sistem Usahawan Pahang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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

/* ===== Kad (card) Optional ===== */
.card {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 215, 0, 0.4);
  border-radius: 14px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 25px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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

.container { max-width: 900px; margin: auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
h3 { margin-bottom: 20px; color: #003399; }
.table img { border-radius: 10px; width: 60px; height: 60px; object-fit: cover; }
.form-section { margin-top: 30px; }
.checkout-total { font-size: 1.3rem; font-weight: bold; color: #003399; text-align: right; }
.pay-options { margin-top: 20px; }
.pay-options label { display: block; margin-bottom: 10px; font-weight: 500; }
.checkout-btn { 
  background: #FFD700; 
  border: none; 
  font-weight: 700; 
  width: 100%; 
  padding: 12px; 
  border-radius: 25px; 
  margin-top: 15px;
  color: #003399;
  cursor: pointer;
  transition: all 0.3s ease;
}
.checkout-btn:hover { 
  background: #FFC107;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
}
.checkout-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
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
}


#map {
  width: 100%;
  height: 300px;
  border-radius: 12px;
  border: 1px solid #ccc;
}


</style>
</head>
<body>

<!-- HEADER -->
<header>
  <img src="assets/img/jatapahang.png" alt="Jata Negeri Pahang" class="jata">
  <h1 class="title">Sistem Usahawan Pahang</h1>
  <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="index.php"><strong>Laman Utama</strong></a>
    <a href="daftar.php"><strong>Daftar Usahawan</strong></a>
    <a href="senarai.php"><strong>Senarai Usahawan</strong></a>
    <a href="cart.php" class="active"><strong>Troli Saya</strong></a>
  </nav>
</header>

<div class="container">
    <h3>🛒 Semak Barang & Maklumat Penghantaran</h3>

    <!-- Senarai Barang -->
    <table class="table table-bordered align-middle">
        <thead class="table-primary">
            <tr>
                <th>Gambar</th>
                <th>Nama Produk</th>
                <th>Harga (RM)</th>
                <th>Kuantiti</th>
                <th>Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><img src="uploads/<?= htmlspecialchars($item['gambar_url']) ?>" alt=""></td>
                <td><?= htmlspecialchars($item['nama']) ?></td>
                <td><?= number_format($item['harga'],2) ?></td>
                <td><?= $item['kuantiti'] ?></td>
                <td><?= number_format($item['subtotal'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light">
                <td colspan="4" class="text-end"><strong>Jumlah Keseluruhan:</strong></td>
                <td><strong>RM <?= number_format($total,2) ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Borang Maklumat Pengguna -->
    <form action="process_checkout.php" method="POST" id="checkoutForm" onsubmit="return validateForm()">
        <?php foreach($ids as $id): ?>
            <input type="hidden" name="selected_items[]" value="<?= $id ?>">
        <?php endforeach; ?>

        <div class="form-section">
            <h5>📋 Maklumat Pengguna</h5>
            <div class="mb-3">
                <label class="form-label">Nama Penuh <span style="color: red;">*</span></label>
                <input type="text" name="nama_pelanggan" class="form-control" required value="<?= htmlspecialchars($user['nama']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">No Telefon <span style="color: red;">*</span></label>
                <input type="text" name="no_telefon" class="form-control" required value="<?= htmlspecialchars($user['telefon']) ?>" pattern="[0-9]{10,11}" title="Sila masukkan nombor telefon yang sah (10-11 digit)">
            </div>
            <div class="mb-3">
              <label class="form-label">Alamat Penghantaran <span style="color:red">*</span></label>

              <!-- Input taip alamat -->
              <input type="text" id="alamat" name="alamat" class="form-control mb-2"
                    placeholder="Taip alamat atau tekan butang lokasi..." required>
              <div id="suggestions" class="list-group"></div>
      

              <!-- Butang GPS -->
              <button type="button" class="btn btn-sm btn-primary mb-2" onclick="getLocation()">
                📍 Guna Lokasi Semasa
              </button>

              <!-- Map -->
              <div id="map" style="height: 300px; border-radius: 10px;"></div>
            </div>

            <!-- Hidden untuk simpan koordinat -->
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="mb-3">
                <label class="form-label">Nota / Permintaan Khas</label>
                <textarea name="nota" class="form-control" rows="2" placeholder="Contoh: Hantar pada waktu petang"></textarea>
            </div>
        </div>

        <!-- Pilihan Penghantaran -->
        <div class="form-section">
            <h5>🚚 Pilihan Penghantaran</h5>
            <div class="form-check mb-2">
                <input type="radio" name="cara_hantar" value="delivery" class="form-check-input" id="delivery" checked required>
                <label class="form-check-label" for="delivery">
                    <strong>Hantar ke rumah (Delivery)</strong> - Penghantaran terus ke alamat anda
                </label>
            </div>
            <div class="form-check">
                <input type="radio" name="cara_hantar" value="dropspot" class="form-check-input" id="dropspot" required>
                <label class="form-check-label" for="dropspot">
                    <strong>Pickup di Dropspot</strong> - Ambil sendiri di lokasi yang ditetapkan
                </label>
            </div>
        </div>

        <!-- Pilihan Pembayaran -->
        <div class="form-section pay-options">
            <h5>💳 Pembayaran</h5>
            <div class="form-check mb-2">
                <input type="radio" name="cara_bayar" value="online" class="form-check-input" id="online" checked required>
                <label class="form-check-label" for="online">
                    <strong>Bayar Secara Online (FPX / Kad)</strong> - Pembayaran segera & selamat
                </label>
            </div>
            <div class="form-check">
                <input type="radio" name="cara_bayar" value="cod" class="form-check-input" id="cod" required>
                <label class="form-check-label" for="cod">
                    <strong>Bayar Tunai Semasa Terima (COD)</strong> - Bayar apabila barang sampai
                </label>
            </div>
        </div>

        <div class="form-section">
            <p class="checkout-total">Jumlah Bayaran: RM <?= number_format($total,2) ?></p>
            <button type="button" class="checkout-btn" id="submitBtn" onclick="submitCheckout()">
                💳 Sahkan & Bayar Sekarang
            </button>

        </div>
    </form>
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

function validateForm() {
    const submitBtn = document.getElementById('submitBtn');
    const nama = document.querySelector('input[name="nama_pelanggan"]').value.trim();
    const telefon = document.querySelector('input[name="no_telefon"]').value.trim();
    const alamat = document.querySelector('input[name="alamat"]').value.trim();
    const lat = document.getElementById("latitude").value;
    const lng = document.getElementById("longitude").value;
    const poskodMatch = alamat.match(/\b\d{5}\b/);
    
    if (!nama || !telefon || !alamat) {
        alert('Sila lengkapkan semua maklumat yang diperlukan.');
        return false;
    }
    
    if (!lat || !lng) {
      alert("Sila pin lokasi anda di map untuk ketepatan penghantaran.");
      return false;
    }

        if (!poskodMatch) {
      alert("Poskod tidak sah. Sila pin lokasi pada map.");
      return false;
    }

    // Disable button to prevent double submission
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Memproses...';
    
    return true;
}

</script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
let debounceTimer;

document.getElementById("alamat").addEventListener("input", function () {
  clearTimeout(debounceTimer);
  const query = this.value.trim();

  if (query.length < 3) {
    document.getElementById("suggestions").innerHTML = "";
    return;
  }

  debounceTimer = setTimeout(() => {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=my&limit=5`, {
      headers: {
        "Accept": "application/json"
      }
    })
    .then(res => res.json())
    .then(data => {
      const suggestionBox = document.getElementById("suggestions");
      suggestionBox.innerHTML = "";

      data.forEach(place => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "list-group-item list-group-item-action";
        item.textContent = place.display_name;

        item.onclick = function () {
          document.getElementById("alamat").value = place.display_name;

          const lat = parseFloat(place.lat);
          const lng = parseFloat(place.lon);

          setMarker(lat, lng);
          map.setView([lat, lng], 17);

          suggestionBox.innerHTML = "";
        };

        suggestionBox.appendChild(item);
      });
    })
    .catch(err => console.error("Nominatim error:", err));
  }, 400);
});


var map, marker;

// ✅ INIT MAP TERUS (TAK GUNA DOMContentLoaded)
map = L.map('map').setView([3.8077, 103.3260], 13);

// Load tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);

// Klik atas map untuk pin
map.on('click', function (e) {
  setMarker(e.latlng.lat, e.latlng.lng);
});

function setMarker(lat, lng) {
  if (marker) {
    marker.setLatLng([lat, lng]);
  } else {
    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker.on('dragend', function (e) {
      const pos = e.target.getLatLng();
      updateLatLng(pos.lat, pos.lng);
      autoDetectAddress(pos.lat, pos.lng); // ✅ bila drag, auto dapat alamat
    });
  }

  updateLatLng(lat, lng);
  autoDetectAddress(lat, lng); // ✅ bila klik, auto dapat alamat
}


function updateLatLng(lat, lng) {
  document.getElementById("latitude").value = lat;
  document.getElementById("longitude").value = lng;
}

function autoDetectAddress(lat, lng) {
  fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(res => res.json())
    .then(data => {
      if (data.display_name) {
        document.getElementById("alamat").value = data.display_name;
      }
    })
    .catch(err => console.error("Reverse geocode error:", err));
}


function getLocation() {
  if (!navigator.geolocation) {
    alert("GPS tidak disokong.");
    return;
  }

  navigator.geolocation.getCurrentPosition(
    function (position) {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      map.setView([lat, lng], 18); // zoom lebih dekat
      setMarker(lat, lng);

      autoDetectAddress(lat, lng);
    },
    function (error) {
      alert("Gagal mendapatkan lokasi: " + error.message);
    },
    {
      enableHighAccuracy: true,  // ✅ INI PALING PENTING
      timeout: 10000,
      maximumAge: 0
    }
  );
}

</script>


<script>
function submitCheckout() {
    const method = document.querySelector('input[name="cara_bayar"]:checked').value;

    if (!validateForm()) {
        return;
    }

    if (method === "online") {
        // Online Payment → Stripe
        document.getElementById('checkoutForm').action = "stripe_checkout.php";
    } else {
        // COD → Process seperti biasa
        document.getElementById('checkoutForm').action = "process_checkout.php";
    }

    document.getElementById('checkoutForm').submit();
}

</script>


</body>
</html>