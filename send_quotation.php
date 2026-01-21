<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) exit("NO_SESSION");

$user_id = (int)$_SESSION['usahawan_id'];
$quotation_id = (int)($_POST['quotation_id'] ?? 0);
$price = (float)$_POST['price'];
$duration = trim($_POST['duration']);
$valid_until = $_POST['valid_until'];

if ($quotation_id <= 0 || $price <= 0 || !$duration) exit("INVALID");

/* Validate seller */
$stmt = $conn->prepare("
  SELECT seller_id, status
  FROM quotations
  WHERE id = ?
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q || $q['seller_id'] != $user_id) exit("FORBIDDEN");
if ($q['status'] !== 'requested') exit("INVALID_STATUS");

/* Update quotation */
$stmt = $conn->prepare("
  UPDATE quotations
  SET price = ?,
      duration = ?,
      valid_until = ?,
      status = 'sent',
      sent_at = NOW()
  WHERE id = ?
");
$stmt->bind_param(
  "dssi",
  $price,
  $duration,
  $valid_until,
  $quotation_id
);
$stmt->execute();

echo "OK";
