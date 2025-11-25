<?php
include "connection.php";
$id = $_GET['id'];
$sql = "SELECT * FROM produk WHERE id = $id";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
  echo "
  <div class='row'>
    <div class='col-md-6'>
      <img src='uploads/{$row['gambar_url']}' class='img-fluid rounded'>
    </div>
    <div class='col-md-6'>
      <h4>{$row['nama']}</h4>
      <p>{$row['deskripsi']}</p>
      <p><b>Harga:</b> RM " . number_format($row['harga'], 2) . "</p>
      <p><b>Stok:</b> {$row['stok']}</p>
      <p><b>Lokasi:</b> {$row['lokasi']}</p>
    </div>
  </div>
  ";
}
