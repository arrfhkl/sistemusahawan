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

if ($chat_id <= 0) {
    exit("INVALID");
}

// Mesti ada sama ada teks ATAU fail
$has_file = !empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
if ($message === '' && !$has_file) {
    exit("INVALID");
}

/* =========================================
   3. VALIDATE USER IS PARTICIPANT
========================================= */
$stmt = $conn->prepare("
    SELECT id FROM chat_rooms
    WHERE id = ? AND (user_low = ? OR user_high = ?)
    LIMIT 1
");
$stmt->bind_param("iii", $chat_id, $user_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    exit("FORBIDDEN");
}

/* =========================================
   4. AUTO-MESSAGE DUPLICATE CHECK
========================================= */
if (str_starts_with($message, "Hi, saya berminat")) {
    $stmt = $conn->prepare("SELECT id FROM chat_messages WHERE chat_id = ? LIMIT 1");
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        exit("AUTO_MESSAGE_EXIST");
    }
}

/* =========================================
   5. HANDLE FILE UPLOAD (jika ada)
========================================= */
$file_url  = null;
$file_name = null;
$file_size = null;
$file_mime = null;
$msg_type  = 'text';

if ($has_file) {

    $MAX_SIZE = 20 * 1024 * 1024; // 20MB

    if ($_FILES['file']['size'] > $MAX_SIZE) {
        exit("FILE_TOO_LARGE");
    }

    // Detect mime type sebenar — tidak bergantung pada $_FILES['type'] (boleh dipalsukan)
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $real_mime = $finfo->file($_FILES['file']['tmp_name']);
    $orig_name = basename($_FILES['file']['name']);

    // Sanitize nama fail
    $safe_name   = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
    $unique_name = time() . '_' . uniqid() . '_' . $safe_name;

    // Buat folder jika belum ada
    $targetDir = "uploads/chat/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $unique_name;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        exit("UPLOAD_FAILED");
    }

    $file_url  = $targetPath;
    $file_name = $orig_name;
    $file_size = (int)$_FILES['file']['size'];
    $file_mime = $real_mime;

    // Tentukan jenis: image atau file
    $imgMimes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $msg_type = in_array($real_mime, $imgMimes) ? 'image' : 'file';
}

/* =========================================
   6. INSERT MESSAGE
========================================= */
$stmt = $conn->prepare("
    INSERT INTO chat_messages
        (chat_id, sender_id, message, message_type, file_url, file_name, file_size, file_mime)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iissssss",
    $chat_id,
    $user_id,
    $message,
    $msg_type,
    $file_url,
    $file_name,
    $file_size,
    $file_mime
);

if (!$stmt->execute()) {
    exit("DB_ERROR");
}

/* =========================================
   7. SUCCESS
========================================= */
echo "OK";