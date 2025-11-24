<?php
include "connection.php";

$id = $_GET['id'];
$sql = "DELETE FROM ruang_fizikal WHERE id = '$id'";

if ($conn->query($sql) === TRUE) {
  echo "<script>alert('Ruang berjaya dipadam!'); window.location='admin_list_ruang.php';</script>";
} else {
  echo "<script>alert('Ralat: " . $conn->error . "'); window.location='admin_list_ruang.php';</script>";
}
?>
