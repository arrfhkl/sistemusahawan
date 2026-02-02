<?php
session_start();
include "connection.php";

/* =======================
   SECURITY CHECK
======================= */
if (!isset($_SESSION['usahawan_id'])) {
    die("Sila login.");
}

$user_id   = $_SESSION['usahawan_id'];
$servis_id = isset($_POST['servis_id']) ? (int)$_POST['servis_id'] : 0;
$message   = isset($_POST['message']) ? trim($_POST['message']) : '';

/* =======================
   PREVENT EMPTY ACTION
======================= */
if ($servis_id <= 0 || $message === '') {
    exit; // no action → no chat
}

/* =======================
   GET TUKANG ID
======================= */
$stmt = $conn->prepare("SELECT usahawan_id FROM servis WHERE id=?");
$stmt->bind_param("i", $servis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit; // servis tak wujud
}

$tukang_id = $result->fetch_assoc()['usahawan_id'];

/* =======================
   CHECK EXISTING CHAT
======================= */
$stmt = $conn->prepare("
    SELECT id FROM chat_rooms
    WHERE servis_id=? AND user_a=? AND user_b=?
");
$stmt->bind_param("iii", $servis_id, $user_id, $tukang_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $chat_id = $res->fetch_assoc()['id'];
} else {
    /* =======================
       CREATE CHAT ROOM
       (ONLY WHEN MESSAGE SENT)
    ======================= */
    $stmt = $conn->prepare("
        INSERT INTO chat_rooms (servis_id, user_a, user_b, created_at)
        VALUES (?,?,?,NOW())
    ");
    $stmt->bind_param("iii", $servis_id, $user_id, $tukang_id);
    $stmt->execute();

    $chat_id = $stmt->insert_id;
}

/* =======================
   SAVE MESSAGE
======================= */
$stmt = $conn->prepare("
    INSERT INTO chat_messages (chat_id, sender_id, message, created_at)
    VALUES (?,?,?,NOW())
");
$stmt->bind_param("iis", $chat_id, $user_id, $message);
$stmt->execute();

/* =======================
   REDIRECT TO CHAT ROOM
======================= */
header("Location: chat_room.php?chat_id=".$chat_id);
exit;
