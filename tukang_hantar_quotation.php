<?php
session_start();
include "connection.php";




?>

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>Hantar Quotation Rasmi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
  font-family: Arial;
  background: #f4f6f9;
}

.container {
  max-width: 600px;
  background: white;
  margin: 60px auto;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
}

label {
  font-weight: bold;
  display: block;
  margin-top: 12px;
}

textarea, input {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

button {
  width: 100%;
  margin-top: 20px;
  padding: 14px;
  background: #007bff;
  border: none;
  color: white;
  font-size: 16px;
  border-radius: 8px;
  cursor: pointer;
}

button:hover {
  background: #0056b3;
}
</style>
</head>
<body>

<div class="container">
  <h2>Hantar Quotation Rasmi</h2>

  <form method="POST">

    <label>Masalah Pelanggan</label>
    <textarea name="masalah" required></textarea>

    <label>Servis Yang Akan Diberi</label>
    <textarea name="servis_akan_diberi" required></textarea>

    <label>Harga Yang Dijanjikan (RM)</label>
    <input type="number" name="harga" step="0.01" required>

    <label>Tarikh Kerja</label>
    <input type="date" name="tarikh_kerja" required>

    <button type="submit" name="submit">
      ✅ Hantar Quotation Kepada Pelanggan
    </button>

  </form>
</div>

</body>
</html>
