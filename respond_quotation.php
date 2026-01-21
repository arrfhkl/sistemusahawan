<?php
session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) exit("NO_SESSION");

$user_id = (int)$_SESSION['usahawan_id'];
$quotation_id = (int)($_POST['quotation_id'] ?? 0);
$action = $_POST['action']; // accept | reject

if (!in_array($action, ['accept','reject'])) exit("INVALID");

/* Validate buyer */
$stmt = $conn->prepare("
  SELECT buyer_id, status
  FROM quotation
  WHERE id = ?
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q || $q['buyer_id'] != $user_id) exit("FORBIDDEN");
if ($q['status'] !== 'sent') exit("INVALID_STATUS");

$newStatus = ($action === 'accept') ? 'accepted' : 'rejected';

$stmt = $conn->prepare("
  UPDATE quotation
  SET status = ?,
      responded_at = NOW()
  WHERE id = ?
");
$stmt->bind_param("si", $newStatus, $quotation_id);
$stmt->execute();

echo "OK";
