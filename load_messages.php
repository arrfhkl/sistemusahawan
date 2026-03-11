<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    http_response_code(401); echo json_encode([]); exit;
}

$user_id = (int)$_SESSION['usahawan_id'];
$chat_id = (int)($_GET['chat_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if ($chat_id <= 0) { echo json_encode([]); exit; }

// Verify user belongs to this chat
$chk = $conn->prepare("SELECT id FROM chat_rooms WHERE id=? AND (user_low=? OR user_high=?) LIMIT 1");
$chk->bind_param("iii", $chat_id, $user_id, $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) { echo json_encode([]); exit; }

// Mark as read (passive — runs on every poll)
$conn->query("
    UPDATE chat_messages SET is_read=1
    WHERE chat_id=$chat_id AND sender_id!=$user_id AND is_read=0
");

// Fetch messages after last_id
$stmt = $conn->prepare("
    SELECT
        id, sender_id, message, message_type,
        file_url, file_name, file_size, file_mime,
        created_at,
        DATE(created_at) AS date_raw
    FROM chat_messages
    WHERE chat_id = ?
      AND id > ?
      AND is_deleted = 0
    ORDER BY id ASC
    LIMIT 50
");
$stmt->bind_param("ii", $chat_id, $last_id);
$stmt->execute();
$rows = $stmt->get_result();

$output = [];

while ($row = $rows->fetch_assoc()) {
    $type    = $row['message_type'] ?? 'text';
    $is_me   = ((int)$row['sender_id'] === $user_id);
    $created = new DateTime($row['created_at']);

    $item = [
        'id'           => (int)$row['id'],
        'sender_id'    => (int)$row['sender_id'],
        'message'      => $row['message'] ?? '',
        'message_type' => $type,
        'is_me'        => $is_me,
        'time'         => $created->format('H:i'),
        'date_raw'     => $row['created_at'],
        'file_url'     => $row['file_url'],
        'file_name'    => $row['file_name'],
        'file_size'    => $row['file_size'] ? (int)$row['file_size'] : null,
        'file_mime'    => $row['file_mime'],
    ];

    // Parse card JSON
    if (($type === 'card' || $type === 'servis') && !empty($row['message'])) {
        $card = json_decode($row['message'], true);
        if ($card) $item['card'] = $card;
    }

    $output[] = $item;
}

header('Content-Type: application/json');
echo json_encode($output);