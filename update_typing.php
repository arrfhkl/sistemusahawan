<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) exit;

$user_id = $_SESSION['usahawan_id'];

$stmt = $conn->prepare("
  INSERT INTO user_online_status (user_id, last_active)
  VALUES (?, NOW())
  ON DUPLICATE KEY UPDATE last_active = NOW()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
