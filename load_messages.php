<?php
/* =========================================
   START SESSION & CONNECTION
========================================= */
session_start();
include "connection.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

/* =========================================
   1. AUTH CHECK
========================================= */
if (!isset($_SESSION['usahawan_id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int) $_SESSION['usahawan_id'];
$chat_id = isset($_GET['chat_id']) ? (int) $_GET['chat_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;

if ($chat_id <= 0) {
    http_response_code(400);
    exit;
}

/* =========================================
   2. VALIDATE USER IS PARTICIPANT
   (GUNA user_low / user_high)
========================================= */
$stmt = $conn->prepare("
    SELECT id
    FROM chat_rooms
    WHERE id = ?
      AND (user_low = ? OR user_high = ?)
    LIMIT 1
");

$stmt->bind_param("iii", $chat_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    exit;
}

/* =========================================
   3. LOAD NEW MESSAGES SAHAJA
========================================= */
$stmt = $conn->prepare("
    SELECT
        id,
        sender_id,
        message,
        message_type,
        created_at
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
        "id"           => (int) $row['id'],
        "sender_id"    => (int) $row['sender_id'],
        "message"      => $row['message'],
        "message_type" => $row['message_type'],
        "time"         => date("h:i A", strtotime($row['created_at'])),
        "is_me"        => ($row['sender_id'] == $user_id)
    ];
}

/* =========================================
   4. RETURN JSON
========================================= */
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($messages);