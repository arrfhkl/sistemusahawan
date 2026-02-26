<?php
/* =========================================
   START SESSION & CONNECTION
========================================= */
session_start();
include "connection.php";

/* =========================================
   1. CHECK LOGIN
========================================= */
if (!isset($_SESSION['usahawan_id'])) {
    die("Sila login.");
}

$user_id    = (int) $_SESSION['usahawan_id'];
$partner_id = isset($_POST['partner_id']) ? (int) $_POST['partner_id'] : 0;
$message    = isset($_POST['message']) ? trim($_POST['message']) : '';

/* =========================================
   2. VALIDATE INPUT
========================================= */
if ($partner_id <= 0 || $message === '') {
    die("DATA_TIDAK_SAH");
}

/* =========================================
   3. BLOCK SELF CHAT
========================================= */
if ($partner_id === $user_id) {
    die("Anda tidak boleh chat diri sendiri");
}

/* =========================================
   4. SUSUN USER LOW / HIGH
========================================= */
$user_low  = min($user_id, $partner_id);
$user_high = max($user_id, $partner_id);

/* =========================================
   5. CHECK EXISTING CHAT
========================================= */
$stmt = $conn->prepare("
    SELECT id
    FROM chat_rooms
    WHERE user_low = ? AND user_high = ?
    LIMIT 1
");

if (!$stmt) {
    die("PREPARE_ERROR: " . $conn->error);
}

$stmt->bind_param("ii", $user_low, $user_high);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {

    // Chat sudah wujud
    $chat_id = (int) $res->fetch_assoc()['id'];

} else {

    /* =========================================
       6. CREATE NEW CHAT ROOM
    ========================================= */
    $stmt = $conn->prepare("
        INSERT INTO chat_rooms (user_low, user_high, created_at)
        VALUES (?, ?, NOW())
    ");

    if (!$stmt) {
        die("PREPARE_INSERT_ERROR: " . $conn->error);
    }

    $stmt->bind_param("ii", $user_low, $user_high);

    if (!$stmt->execute()) {
        die("INSERT_CHAT_ERROR: " . $stmt->error);
    }

    $chat_id = $stmt->insert_id;

    if ($chat_id <= 0) {
        die("CHAT_ID_GAGAL");
    }
}

/* =========================================
   7. INSERT FIRST MESSAGE
========================================= */
$stmt = $conn->prepare("
    INSERT INTO chat_messages
    (chat_id, sender_id, message, created_at)
    VALUES (?, ?, ?, NOW())
");

if (!$stmt) {
    die("PREPARE_MSG_ERROR: " . $conn->error);
}

$stmt->bind_param("iis", $chat_id, $user_id, $message);

if (!$stmt->execute()) {
    die("INSERT_MSG_ERROR: " . $stmt->error);
}

/* =========================================
   8. REDIRECT TO CHAT ROOM
========================================= */
header("Location: chat_room.php?chat_id=" . $chat_id);
exit;