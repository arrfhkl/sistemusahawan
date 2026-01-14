<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  http_response_code(401);
  exit("NO_SESSION");
}

$user_id = (int)$_SESSION['usahawan_id'];
$chat_id = (int)($_POST['chat_id'] ?? 0);

if ($chat_id <= 0) {
  http_response_code(400);
  exit("INVALID_CHAT");
}

/* VALIDATE USER IS CHAT PARTICIPANT */
$stmt = $conn->prepare("
  SELECT id
  FROM chat_rooms
  WHERE id = ?
    AND (user_a = ? OR user_b = ?)
  LIMIT 1
");
$stmt->bind_param("iii", $chat_id, $user_id, $user_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
  http_response_code(403);
  exit("NOT_ALLOWED");
}

/* INSERT / UPDATE TYPING STATUS */
$stmt = $conn->prepare("
  INSERT INTO typing_status (chat_id, user_id, last_typing)
  VALUES (?, ?, NOW())
  ON DUPLICATE KEY UPDATE last_typing = NOW()
");
$stmt->bind_param("ii", $chat_id, $user_id);

if (!$stmt->execute()) {
  http_response_code(500);
  exit("DB_ERROR");
}

echo "OK";
