<?php
session_start();
include "../connection.php";

$usahawan_id = $_SESSION['usahawan_id'];

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=laporan_pesanan.csv");

$out = fopen("php://output","w");
fputcsv($out,["No Pesanan","Pelanggan","Jumlah","Status","Tarikh"]);

$q = $conn->query("
  SELECT no_pesanan,nama_pelanggan,jumlah_bayaran,status_pesanan,tarikh_pesanan
  FROM pesanan
  WHERE usahawan_id=$usahawan_id
");

while($r = $q->fetch_assoc()){
  fputcsv($out,$r);
}
fclose($out);
