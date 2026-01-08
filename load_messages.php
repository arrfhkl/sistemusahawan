<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) exit;

$chat_id = (int)($_GET['chat_id'] ?? 0);
$user_id = $_SESSION['usahawan_id'];

$stmt = $conn->prepare("
  SELECT sender_id, message, created_at
  FROM chat_messages
  WHERE chat_id = ?
  AND is_deleted = 0
  ORDER BY created_at ASC
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

  $isMe = ($row['sender_id'] == $user_id);
  $class = $isMe ? "me" : "other";

  echo "<div class='msg $class'>";
  echo nl2br(htmlspecialchars($row['message']));
  echo "<div class='meta'>" . date("H:i", strtotime($row['created_at'])) . "</div>";
  echo "</div>";
}
