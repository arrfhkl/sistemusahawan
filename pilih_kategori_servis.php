<?php
include "connection.php";
include "header.php";

$result = $conn->query("
  SELECT * 
  FROM kategori_servis 
  ORDER BY 
    CASE 
      WHEN nama LIKE '%lain%' THEN 2 
      ELSE 1 
    END,
    nama ASC
");
?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Pilih Kategori Servis</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
* {
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}

body {
  background: #f6f8fb;
}

/* ===== CONTAINER ===== */
.container {
  max-width: 1100px;
  margin: 120px auto 60px;
  padding: 0 16px;
}

/* ===== TAJUK ===== */
.tajuk-kategori {
  text-align: center;
  font-size: 2rem;
  font-weight: 700;
  color: #0f4376;
}

.subteks {
  text-align: center;
  margin-top: 8px;
  color: #555;
  font-size: 15px;
}

/* ===== SEARCH BAR ===== */
.search-box {
  max-width: 420px;
  margin: 30px auto 40px;
}

.search-box input {
  width: 100%;
  padding: 12px 16px;
  border-radius: 30px;
  border: 1px solid #ccc;
  font-size: 14px;
}

/* ===== GRID KAD ===== */
.cards-wrapper {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 22px;
}

/* ===== KAD ===== */
.category-card {
  background: #fff;
  border-radius: 16px;
  padding: 26px 20px;
  text-align: center;
  text-decoration: none;
  color: #333;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  transition: 0.25s ease;
}

.category-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.15);
}

.category-icon {
  font-size: 42px;
  margin-bottom: 14px;
}

.category-title {
  font-size: 17px;
  font-weight: 600;
  margin-bottom: 6px;
}

.category-desc {
  font-size: 14px;
  color: #666;
}
</style>
</head>

<body>

<div class="container">
  <h1 class="tajuk-kategori">Pilih Kategori Servis</h1>
  <p class="subteks">Sila pilih kategori untuk melihat senarai servis yang tersedia</p>

  <div class="search-box">
    <input type="text" id="searchKategori" placeholder="Cari kategori servis...">
  </div>
  <p id="noResult" style="text-align:center; color:#777; display:none;">
  Tiada kategori ditemui
  </p>

  <div class="cards-wrapper">
    <?php while($kat = $result->fetch_assoc()): 

      $icon = "🛠️";
      $nama = strtolower($kat['nama']);

      if (str_contains($nama, "elektrik")) $icon = "⚡";
      elseif (str_contains($nama, "paip")) $icon = "🚰";
      elseif (str_contains($nama, "aircond")) $icon = "❄️";
      elseif (str_contains($nama, "bunga")) $icon = "🌸";
      elseif (str_contains($nama, "jahit")) $icon = "👔";
    ?>
      <a 
        href="senarai_servis.php?kategori_id=<?= $kat['id'] ?>" 
        class="category-card"
        data-name="<?= strtolower($kat['nama']) ?>"
      >
        <div class="category-icon"><?= $icon ?></div>
        <div class="category-title"><?= htmlspecialchars($kat['nama']) ?></div>
        <div class="category-desc">
          Lihat servis berkaitan <?= htmlspecialchars($kat['nama']) ?>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
</div>

<script>
const searchInput = document.getElementById('searchKategori');
const cards = document.querySelectorAll('.category-card');
const noResult = document.getElementById('noResult');

searchInput.addEventListener('input', function () {
  const keyword = this.value.toLowerCase();
  let found = false;

  cards.forEach(card => {
    const nama = card.getAttribute('data-name');

    if (nama.includes(keyword)) {
      card.style.display = "block";
      found = true;
    } else {
      card.style.display = "none";
    }
  });

  noResult.style.display = found ? "none" : "block";
});
</script>

<?php include "footer.php"; ?>
</body>
</html>
