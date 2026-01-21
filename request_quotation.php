<?php
session_start();
include "connection.php";

$conn->query("SET time_zone = '+08:00'");

if (!isset($_SESSION['usahawan_id'])) {
  http_response_code(401);
  exit("Login dahulu");
}

$user_id = (int)$_SESSION['usahawan_id'];
$chat_id = (int)($_POST['chat_id'] ?? 0);

if ($chat_id <= 0) {
  exit("Chat tidak sah");
}

/* ===============================
   GET CHAT INFO
================================ */
$stmt = $conn->prepare("
  SELECT cr.servis_id, s.usahawan_id AS seller_id
  FROM chat_rooms cr
  JOIN servis s ON s.id = cr.servis_id
  WHERE cr.id = ?
    AND (cr.user_a = ? OR cr.user_b = ?)
  LIMIT 1
");
$stmt->bind_param("iii", $chat_id, $user_id, $user_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) exit("Chat tidak dijumpai");

$seller_id = (int)$info['seller_id'];
$servis_id = (int)$info['servis_id'];

/* ===============================
   BLOCK SELLER
================================ */
if ($user_id === $seller_id) {
  exit("Penjual tidak boleh minta quotation");
}

/* ===============================
   PREVENT DUPLICATE REQUEST
================================ */
$check = $conn->prepare("
  SELECT id FROM quotation
  WHERE chat_id = ?
    AND buyer_id = ?
    AND status = 'requested'
  LIMIT 1
");
$check->bind_param("ii", $chat_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
  exit("Permintaan quotation masih menunggu");
}

/* ===============================
   INSERT REQUEST
================================ */
$insert = $conn->prepare("
  INSERT INTO quotation
    (chat_id, servis_id, seller_id, buyer_id, status)
  VALUES (?,?,?,?, 'requested')
");
$insert->bind_param("iiii",
  $chat_id,
  $servis_id,
  $seller_id,
  $user_id
);

if (!$insert->execute()) {
  exit("Gagal hantar permintaan");
}

/* ===============================
   SYSTEM MESSAGE – QUOTATION REQUEST
================================ */
$systemMessage = "📄 <i>Permohonan quotation telah dihantar</i>";

$stmtMsg = $conn->prepare("
  INSERT INTO chat_messages
    (chat_id, sender_id, servis_id, message, created_at, is_deleted, is_read)
  SELECT ?, 0, ?, ?, NOW(), 0, 0
  WHERE NOT EXISTS (
    SELECT 1 FROM chat_messages
    WHERE chat_id = ?
      AND sender_id = 0
      AND servis_id = ?
  )
");

$stmtMsg->bind_param(
  "iisii",
  $chat_id,
  $servis_id,
  $systemMessage,
  $chat_id,
  $servis_id
);

$stmtMsg->execute();

echo "OK";
