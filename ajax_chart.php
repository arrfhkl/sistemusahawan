<?php
session_start();
include "../connection.php";

$usahawan_id = $_SESSION['usahawan_id'];

$q = $conn->query("
  SELECT MONTH(tarikh_pesanan) m, SUM(jumlah_bayaran) j
  FROM pesanan
  WHERE usahawan_id=$usahawan_id
  AND status_pesanan='selesai'
  GROUP BY m
");

$bulan = [];
$jumlah = [];

while($r = $q->fetch_assoc()){
  $bulan[] = date('M', mktime(0,0,0,$r['m'],1));
  $jumlah[] = $r['j'];
}

echo json_encode([
  'bulan'=>$bulan,
  'jumlah'=>$jumlah
]);
