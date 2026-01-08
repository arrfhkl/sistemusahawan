<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
  exit("NO_SESSION");
}

$chat_id = (int)($_POST['chat_id'] ?? 0);
$message = trim($_POST['message'] ?? "");
$user_id = $_SESSION['usahawan_id'];

if ($chat_id === 0 || $message === "") {
  exit("INVALID");
}

if (str_starts_with($message, "Hi, saya berminat")) {
  $stmt = $conn->prepare("
    SELECT id FROM chat_messages
    WHERE chat_id = ?
    AND sender_id = ?
    AND message LIKE 'Hi, saya berminat%'
    LIMIT 1
  ");
  $stmt->bind_param("ii", $chat_id, $user_id);
  $stmt->execute();
  if ($stmt->get_result()->num_rows > 0) {
    exit("AUTO_MESSAGE_EXIST");
  }
}

/* INSERT MESSAGE */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (chat_id, sender_id, message)
  VALUES (?,?,?)
");
$stmt->bind_param("iis", $chat_id, $user_id, $message);
$stmt->execute();

echo "OK";
