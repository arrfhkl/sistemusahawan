<?php
session_start();
include "connection.php";

$conn->query("SET time_zone = '+08:00'");

if (!isset($_SESSION['usahawan_id'])) {
    http_response_code(401);
    exit("Login dahulu");
}

$user_id   = (int) $_SESSION['usahawan_id'];
$chat_id   = (int) ($_POST['chat_id'] ?? 0);
$servis_id = (int) ($_POST['servis_id'] ?? 0);

if ($chat_id <= 0 || $servis_id <= 0) {
    exit("Data tidak sah");
}

/* ===============================
   VALIDATE CHAT & GET SELLER
================================ */
$stmt = $conn->prepare("
    SELECT 
        cr.user_a,
        cr.user_b,
        s.usahawan_id AS seller_id,
        s.nama AS servis_nama
    FROM chat_rooms cr
    JOIN servis s ON s.id = ?
    WHERE cr.id = ?
      AND (cr.user_a = ? OR cr.user_b = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $servis_id, $chat_id, $user_id, $user_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
    exit("Chat atau servis tidak sah");
}

$seller_id   = (int) $info['seller_id'];
$namaServis  = $info['servis_nama'];

/* ===============================
   BLOCK SELLER REQUEST
================================ */
if ($user_id === $seller_id) {
    exit("Penjual tidak boleh minta quotation");
}

/* ===============================
   PREVENT DUPLICATE (SAME SERVIS)
================================ */
$check = $conn->prepare("
    SELECT id 
    FROM quotation
    WHERE chat_id = ?
      AND servis_id = ?
      AND buyer_id = ?
      AND status = 'requested'
    LIMIT 1
");
$check->bind_param("iii", $chat_id, $servis_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    exit("Permintaan quotation untuk servis ini masih menunggu");
}

/* ===============================
   INSERT QUOTATION
================================ */
$insert = $conn->prepare("
    INSERT INTO quotation
      (chat_id, servis_id, seller_id, buyer_id, status)
    VALUES (?,?,?,?, 'requested')
");
$insert->bind_param(
    "iiii",
    $chat_id,
    $servis_id,
    $seller_id,
    $user_id
);

if (!$insert->execute()) {
    exit("Gagal hantar permintaan quotation");
}

/* ===============================
   SYSTEM MESSAGE (ATTACH SERVIS)
================================ */
$systemMessage = "📄 <i>Permohonan quotation untuk <b>{$namaServis}</b> telah dihantar</i>";

$stmtMsg = $conn->prepare("
    INSERT INTO chat_messages
      (chat_id, sender_id, servis_id, message, created_at, is_deleted, is_read)
    VALUES (?, 0, ?, ?, NOW(), 0, 0)
");
$stmtMsg->bind_param(
    "iis",
    $chat_id,
    $servis_id,
    $systemMessage
);
$stmtMsg->execute();

echo "OK";
