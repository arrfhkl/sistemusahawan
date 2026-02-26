<?php
/* =========================================
   START SESSION & CONNECTION
========================================= */
session_start();
include "connection.php";

/* =========================================
   1. CHECK LOGIN SESSION
========================================= */
if (!isset($_SESSION['usahawan_id'])) {
    exit("NO_SESSION");
}

$user_id = (int) $_SESSION['usahawan_id'];

/* =========================================
   2. VALIDATE INPUT
========================================= */
$chat_id = isset($_POST['chat_id']) ? (int) $_POST['chat_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($chat_id <= 0 || $message === '') {
    exit("INVALID");
}

/* =========================================
   3. VALIDATE USER IS PARTICIPANT
   (IMPORTANT - GUNA user_low / user_high)
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
    exit("FORBIDDEN");
}

/* =========================================
   4. OPTIONAL AUTO-MESSAGE CHECK
   (Prevent duplicate first auto message)
========================================= */
if (str_starts_with($message, "Hi, saya berminat")) {

    $stmt = $conn->prepare("
        SELECT id
        FROM chat_messages
        WHERE chat_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        exit("AUTO_MESSAGE_EXIST");
    }
}

/* =========================================
   5. INSERT MESSAGE
========================================= */
$stmt = $conn->prepare("
    INSERT INTO chat_messages (chat_id, sender_id, message)
    VALUES (?, ?, ?)
");

$stmt->bind_param("iis", $chat_id, $user_id, $message);

if (!$stmt->execute()) {
    exit("DB_ERROR");
}

/* =========================================
   6. SUCCESS RESPONSE
========================================= */
echo "OK";