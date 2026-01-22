<?php
session_start();
include "connection.php";

$conn->query("SET time_zone = '+08:00'");

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['usahawan_id'])) {
  die("Login dahulu");
}

$seller_id = (int)$_SESSION['usahawan_id'];

/* ===============================
   INPUT
================================ */
$order_id = (int)($_POST['order_id'] ?? 0);
$action   = $_POST['action'] ?? '';

if ($order_id <= 0 || !in_array($action, ['start','complete'])) {
  die("Permintaan tidak sah");
}

/* ===============================
   LOAD ORDER (SELLER ONLY)
================================ */
$stmt = $conn->prepare("
  SELECT
    id,
    chat_id,
    servis_id,
    status
  FROM servis_order
  WHERE id = ?
    AND seller_id = ?
  LIMIT 1
");
$stmt->bind_param("ii", $order_id, $seller_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
  die("Order tidak dijumpai");
}

/* ===============================
   STATUS TRANSITION
================================ */
if ($action === 'start' && $order['status'] !== 'pending') {
  die("Status tidak sah");
}

if ($action === 'complete' && $order['status'] !== 'in_progress') {
  die("Status tidak sah");
}

/* ===============================
   UPDATE ORDER
================================ */
if ($action === 'start') {

  $stmt = $conn->prepare("
    UPDATE servis_order
    SET status = 'in_progress',
        started_at = NOW()
    WHERE id = ?
  ");
  $stmt->bind_param("i", $order_id);
  $stmt->execute();

  $systemText = "🚀 Servis telah dimulakan oleh penjual.";

} else {

  $stmt = $conn->prepare("
    UPDATE servis_order
    SET status = 'completed',
        completed_at = NOW()
    WHERE id = ?
  ");
  $stmt->bind_param("i", $order_id);
  $stmt->execute();

  $systemText = "✅ Servis telah disiapkan.";
}

/* ===============================
   SYSTEM MESSAGE (CHAT)
================================ */
$msg = $conn->prepare("
  INSERT INTO chat_messages
    (chat_id, sender_id, servis_id, message, created_at, is_deleted, is_read)
  VALUES (?, 0, ?, ?, NOW(), 0, 0)
");
$msg->bind_param(
  "iis",
  $order['chat_id'],
  $order['servis_id'],
  $systemText
);
$msg->execute();

/* ===============================
   REDIRECT BACK
================================ */
header("Location: seller_order_detail.php?order_id=".$order_id);
exit;
