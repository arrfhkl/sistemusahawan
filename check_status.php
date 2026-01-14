<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  exit("offline");
}

$user_id = $_SESSION['usahawan_id'];
$chat_id = (int)($_GET['chat_id'] ?? 0);

if ($chat_id <= 0) {
  exit("offline");
}

/* ===============================
   VALIDATE PARTICIPANT & GET OTHER USER
================================ */
$stmt = $conn->prepare("
  SELECT 
    CASE 
      WHEN user_a = ? THEN user_b
      WHEN user_b = ? THEN user_a
      ELSE NULL
    END AS other_user
  FROM chat_rooms
  WHERE id = ?
  LIMIT 1
");
$stmt->bind_param("iii", $user_id, $user_id, $chat_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !$row['other_user']) {
  exit("offline");
}

$other_user_id = (int)$row['other_user'];

/* ===============================
   CHECK ONLINE STATUS
================================ */
$stmt = $conn->prepare("
  SELECT last_active
  FROM user_online_status
  WHERE user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
  echo "offline";
  exit;
}

$last = strtotime($res['last_active']);
echo (time() - $last < 60) ? "online" : "offline";
