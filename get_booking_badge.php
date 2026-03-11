<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['usahawan_id'])) {
    echo json_encode(['count' => 0, 'debug' => 'no session']);
    exit;
}

$usahawan_id = (int) $_SESSION['usahawan_id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM servis_booking
    WHERE usahawan_id = ?
      AND status NOT IN ('completed', 'rejected', 'cancelled')
");
$stmt->bind_param("i", $usahawan_id);
$stmt->execute();
$count = (int) $stmt->get_result()->fetch_assoc()['total'];

echo json_encode(['count' => $count]);