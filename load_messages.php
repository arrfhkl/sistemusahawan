<?php
session_start();
include "connection.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
  http_response_code(401);
  exit;
}

$user_id = (int)$_SESSION['usahawan_id'];
$chat_id = (int)($_GET['chat_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if ($chat_id <= 0) {
  http_response_code(400);
  exit;
}

/* ===============================
   VALIDATE USER IS CHAT PARTICIPANT
================================ */
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
  exit;
}

/* ===============================
   LOAD ONLY NEW MESSAGES
================================ */
$stmt = $conn->prepare("
  SELECT 
    id,
    sender_id,
    message,
    created_at,
    DATE(created_at) AS msg_date,
    DATE_FORMAT(created_at, '%H:%i') AS msg_time
  FROM chat_messages
  WHERE chat_id = ?
    AND id > ?
    AND is_deleted = 0
  ORDER BY id ASC
");
$stmt->bind_param("ii", $chat_id, $last_id);
$stmt->execute();

$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
  $messages[] = [
    "id"      => (int)$row['id'],
    "message" => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
    "date"    => $row['msg_date'], // YYYY-MM-DD
    "time"    => date("h:i A", strtotime($row['msg_time'])),
    "is_me"   => ($row['sender_id'] == $user_id)
  ];
}

/* ===============================
   RETURN JSON
================================ */
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($messages);
