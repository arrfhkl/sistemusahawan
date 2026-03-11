<?php 
include 'connection.php';
include 'header.php';
?>
<br>
<br>
<br>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Caj Servis Baharu</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
body{
  margin:0;
  background:#f4f6f9;
  font-family:'Inter',sans-serif;
}

.container{
  max-width:700px;
  margin:60px auto;
  background:#ffffff;
  padding:30px;
  border-radius:8px;
  box-shadow:0 8px 25px rgba(0,0,0,.08);
}

h2{
  margin-bottom:10px;
}

.subtitle{
  color:#555;
  font-size:14px;
  margin-bottom:25px;
}

.form-group{
  margin-bottom:20px;
}

label{
  display:block;
  font-weight:600;
  margin-bottom:6px;
}

input, textarea, select{
  width:100%;
  padding:10px;
  border:1px solid #ccc;
  border-radius:4px;
  font-size:14px;
}

textarea{
  resize:vertical;
}

.note{
  font-size:13px;
  color:#666;
  margin-top:5px;
}

.summary-box{
  background:#fafafa;
  border:1px solid #e5e5e5;
  padding:15px;
  border-radius:6px;
  margin-top:20px;
  font-size:14px;
}

button{
  padding:12px 18px;
  border:none;
  border-radius:4px;
  font-weight:600;
  cursor:pointer;
}

.btn-primary{
  background:#1f3c88;
  color:#fff;
}

.btn-secondary{
  background:#ccc;
  color:#333;
}
</style>
</head>

<body>

<div class="container">

<h2>Tambah Caj Servis Baharu</h2>
<div class="subtitle">
Sila berikan maklumat yang jelas mengenai caj tambahan sebelum dihantar kepada pelanggan untuk kelulusan.
</div>

<form>

<div class="form-group">
  <label>Kategori Caj</label>
  <select>
    <option>Bayaran Pemeriksaan</option>
    <option>Upah / Kerja Diteruskan</option>
    <option>Peralatan & Alatan</option>
    <option>Bahan Tambahan</option>
    <option>Lain-lain</option>
  </select>
  <div class="note">
    Pilih kategori yang paling sesuai bagi caj ini.
  </div>
</div>

<div class="form-group">
  <label>Tajuk Caj</label>
  <input type="text" placeholder="Contoh: Kerja Penggantian Paip">
  <div class="note">
    Masukkan tajuk ringkas dan jelas untuk caj ini.
  </div>
</div>

<div class="form-group">
  <label>Penerangan Terperinci</label>
  <textarea rows="4" placeholder="Terangkan dengan jelas apa yang termasuk dalam caj ini dan sebab ia diperlukan."></textarea>
  <div class="note">
    Penerangan ini akan dipaparkan kepada pelanggan sebelum kelulusan dibuat.
  </div>
</div>

<div class="form-group">
  <label>Jumlah Caj (RM)</label>
  <input type="number" placeholder="Masukkan jumlah dalam Ringgit Malaysia">
  <div class="note">
    Pelanggan perlu meluluskan jumlah ini sebelum ia dimasukkan ke dalam jumlah akhir yang perlu dibayar.
  </div>
</div>

<div class="summary-box">
  <strong>Penting:</strong><br><br>
  • Selepas dihantar, caj ini tidak boleh diubah.<br>
  • Pelanggan mesti meluluskan caj ini sebelum pembayaran diminta.<br>
  • Caj yang ditolak tidak akan dimasukkan ke dalam jumlah akhir.
</div>

<div style="margin-top:25px; text-align:right;">
  <button type="button" class="btn-secondary">Batal</button>
  <button type="submit" class="btn-primary">Hantar Caj untuk Kelulusan Pelanggan</button>
</div>

</form>

</div>

</body>
</html>