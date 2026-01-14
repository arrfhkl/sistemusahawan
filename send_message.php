<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  exit("NO_SESSION");
}



$user_id = $_SESSION['usahawan_id'];
$chat_id = (int)($_POST['chat_id'] ?? 0);
$message = trim($_POST['message'] ?? "");

if ($chat_id <= 0 || $message === "") {
  exit("INVALID");
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
  exit("FORBIDDEN");
}

/* ===============================
   AUTO MESSAGE (ONCE ONLY)
================================ */
if (str_starts_with($message, "Hi, saya berminat")) {

  $stmt = $conn->prepare("
    SELECT id 
    FROM chat_messages
    WHERE chat_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $chat_id);
  $stmt->execute();

  if ($stmt->get_result()->num_rows > 0) {
    exit("AUTO_MESSAGE_EXIST");
  }
}

/* ===============================
   INSERT MESSAGE
================================ */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (chat_id, sender_id, message)
  VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $chat_id, $user_id, $message);
$stmt->execute();

echo "OK";
