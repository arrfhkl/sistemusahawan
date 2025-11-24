<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  echo "Sesi tamat. Sila log masuk semula.";
  exit;
}

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $user_id = $_SESSION['usahawan_id'];

  $sql = "DELETE FROM cart WHERE id = $id AND usahawan_id = $user_id";
  if ($conn->query($sql)) {
    echo "Item berjaya dipadam.";
  } else {
    echo "Ralat semasa memadam item.";
  }
} else {
  echo "ID tidak sah.";
}
?>
