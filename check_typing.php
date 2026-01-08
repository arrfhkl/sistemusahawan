<?php
include "connection.php";

$user_id = (int)($_GET['user_id'] ?? 0);

$stmt = $conn->prepare("
  SELECT last_active
  FROM user_online_status
  WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
  echo "idle";
  exit;
}

$diff = time() - strtotime($result['last_active']);

echo ($diff <= 3) ? "typing" : "idle";
