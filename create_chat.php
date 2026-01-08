<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  die("Sila login.");
}

$servis_id = (int)$_GET['servis_id'];
$user_id  = $_SESSION['usahawan_id'];

// dapatkan tukang dari servis
$stmt = $conn->prepare("SELECT usahawan_id FROM servis WHERE id=?");
$stmt->bind_param("i", $servis_id);
$stmt->execute();
$tukang_id = $stmt->get_result()->fetch_assoc()['usahawan_id'];

// semak chat sedia ada
$stmt = $conn->prepare("
  SELECT id FROM chat_rooms
  WHERE servis_id=? AND user_a=? AND user_b=?
");
$stmt->bind_param("iii", $servis_id, $user_id, $tukang_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
  header("Location: chat_room.php?chat_id=".$res->fetch_assoc()['id']);
  exit;
}

// create baru
$stmt = $conn->prepare("
  INSERT INTO chat_rooms (servis_id, user_a, user_b)
  VALUES (?,?,?)
");
$stmt->bind_param("iii", $servis_id, $user_id, $tukang_id);
$stmt->execute();

header("Location: chat_room.php?chat_id=".$stmt->insert_id);
